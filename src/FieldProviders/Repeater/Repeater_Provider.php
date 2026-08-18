<?php
/**
 * Repeater Field Type Provider.
 *
 * Implements Field_Type_Contract for ACF Repeater fields. This is the reference
 * implementation that proves the abstraction works — adding it required zero
 * changes to any integration class (Loader, Tag_Registrar, Loop_Grid_Controller).
 *
 * ## Virtual Post Strategy
 *
 * For each real post that has a repeater field, filter_post_list() produces N
 * stdClass "virtual posts" — one per repeater row. Each virtual post:
 *   - Carries the real parent post ID as $post->ID (positive, valid WP ID)
 *   - Has filter = 'raw' so WP_Post wraps it without a DB round-trip
 *   - Carries lsdfe_provider_key, lsdfe_row_index, lsdfe_parent_post_id as custom
 *     properties (preserved by WP_Post::__construct via get_object_vars())
 *
 * The the_post action (sync_row_context) reads these properties and pushes
 * Row_Context so Dynamic Tags can resolve sub-field values without any
 * negative-ID encoding / decoding magic.
 *
 * @package LoopSyncDynamicFields\FieldProviders\Repeater
 */

namespace LoopSyncDynamicFields\FieldProviders\Repeater;

use LoopSyncDynamicFields\FieldProviders\Field_Type_Contract;
use LoopSyncDynamicFields\FieldProviders\Tag_Descriptor;
use LoopSyncDynamicFields\Runtime\Row_Context;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Repeater_Provider
 *
 * Handles ACF Repeater fields end-to-end: injects Loop Grid controls, modifies
 * the WP_Query, expands posts into virtual rows, and declares six Dynamic Tags
 * (Text, WYSIWYG, Image, URL, Number, Date).
 */
final class Repeater_Provider implements Field_Type_Contract {

	// ------------------------------------------------------------------
	// Constants — all prefixed lsdfe_ to avoid collisions.
	// ------------------------------------------------------------------

	/** @var string Provider key — used by Provider_Registry as the index. */
	const PROVIDER_KEY = 'repeater';

	/** @var string WP_Query var marking a query as repeater-expanded. */
	const QUERY_VAR_ACTIVE = 'lsdfe_repeater_active';

	/** @var string WP_Query var carrying the repeater field name. */
	const QUERY_VAR_FIELD = 'lsdfe_repeater_field';

	/** @var string WP_Query var carrying the scope setting. */
	const QUERY_VAR_SCOPE = 'lsdfe_repeater_scope';

	/** @var string Elementor control key — enable/disable toggle on Loop Grid. */
	const CTRL_ENABLED = 'lsdfe_repeater_enabled';

	/** @var string Elementor control key — repeater field selector on Loop Grid. */
	const CTRL_FIELD = 'lsdfe_repeater_field_name';

	/** @var string Elementor control key — scope toggle on Loop Grid. */
	const CTRL_SCOPE = 'lsdfe_repeater_current_only';

	// ------------------------------------------------------------------
	// Constructor.
	// ------------------------------------------------------------------

	/**
	 * Hooks the_post to synchronise Row_Context as Elementor iterates the loop.
	 * Adding the hook here (in the constructor) means it fires for the lifetime
	 * of this provider instance — once per request.
	 */
	public function __construct() {
		add_action( 'the_post', array( $this, 'sync_row_context' ) );
	}

	// ------------------------------------------------------------------
	// Field_Type_Contract implementation.
	// ------------------------------------------------------------------

	/**
	 * {@inheritDoc}
	 */
	public function get_provider_key(): string {
		return self::PROVIDER_KEY;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_handled_field_types(): array {
		return array( 'repeater' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * Returns six Tag_Descriptor objects — one per supported ACF sub-field type.
	 * The Elementor integration layer registers these without knowing class names.
	 */
	public function get_tag_descriptors(): array {
		return array(
			new Tag_Descriptor( Tags\Text_Tag::class, 'acf' ),
			new Tag_Descriptor( Tags\Wysiwyg_Tag::class, 'acf' ),
			new Tag_Descriptor( Tags\Image_Tag::class, 'acf' ),
			new Tag_Descriptor( Tags\Url_Tag::class, 'acf' ),
			new Tag_Descriptor( Tags\Number_Tag::class, 'acf' ),
			new Tag_Descriptor( Tags\Date_Tag::class, 'acf' ),
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * Injects three controls into the Loop Grid widget's Query section:
	 *  1. Toggle — enable ACF Repeater mode.
	 *  2. Select — which repeater field to loop over.
	 *  3. Toggle — limit to the current post only (vs all posts in the query).
	 */
	public function register_loop_controls( \Elementor\Element_Base $element ): void {
		$element->add_control(
			self::CTRL_ENABLED,
			array(
				'label'        => esc_html__( 'Use ACF Repeater', 'loopsync-dynamic-fields-for-elementor' ),
				'description'  => esc_html__( 'Loop over ACF Repeater rows instead of posts.', 'loopsync-dynamic-fields-for-elementor' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
			)
		);

		$element->add_control(
			self::CTRL_FIELD,
			array(
				'label'       => esc_html__( 'ACF Repeater Field', 'loopsync-dynamic-fields-for-elementor' ),
				'description' => esc_html__( 'Select the Repeater field whose rows become Loop Grid items.', 'loopsync-dynamic-fields-for-elementor' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => $this->get_repeater_field_options(),
				'default'     => '',
				'condition'   => array( self::CTRL_ENABLED => 'yes' ),
			)
		);

		$element->add_control(
			self::CTRL_SCOPE,
			array(
				'label'        => esc_html__( 'Current Post Only', 'loopsync-dynamic-fields-for-elementor' ),
				'description'  => esc_html__( 'Show rows from the currently viewed post. Disable to aggregate rows from all posts matched by the Loop Grid query.', 'loopsync-dynamic-fields-for-elementor' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( self::CTRL_ENABLED => 'yes' ),
			)
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * When the repeater toggle is on and a valid field is selected, stamps three
	 * custom query vars onto the WP_Query args so filter_post_list() can identify
	 * the query and know which field to expand.
	 */
	public function filter_query_args( array $args, \Elementor\Widget_Base $widget ): array {
		$settings = $widget->get_settings();
		if ( empty( $settings[ self::CTRL_ENABLED ] ) ) {
			$settings = $widget->get_settings_for_display();
		}

		if ( 'yes' !== ( $settings[ self::CTRL_ENABLED ] ?? '' ) ) {
			return $args;
		}

		$field_name = sanitize_key( $settings[ self::CTRL_FIELD ] ?? '' );
		if ( empty( $field_name ) ) {
			return $args;
		}

		$args[ self::QUERY_VAR_ACTIVE ] = 1;
		$args[ self::QUERY_VAR_FIELD ]  = $field_name;
		$args[ self::QUERY_VAR_SCOPE ]  = sanitize_key( $settings[ self::CTRL_SCOPE ] ?? 'yes' );

		// When scope = current post only, restrict the WP_Query to the current post.
		// Set post_type = 'any' so it matches whether the current post is a Post, Page, or CPT.
		if ( 'yes' === $args[ self::QUERY_VAR_SCOPE ] ) {
			$current_id = get_the_ID();
			if ( ! $current_id ) {
				$current_id = get_queried_object_id();
			}
			if ( $current_id ) {
				$args['post__in']  = array( $current_id );
				$args['post_type'] = 'any';
			}
		}

		return $args;
	}

	/**
	 * {@inheritDoc}
	 *
	 * Expands each post in the query result into N virtual stdClass objects —
	 * one per repeater row. Virtual posts carry unique negative IDs (to prevent
	 * Elementor loop item deduplication) and pre-loaded row data.
	 *
	 * Returns $posts unmodified if the query was not stamped by filter_query_args().
	 */
	public function filter_post_list( array $posts, \WP_Query $query ): array {
		if ( empty( $query->get( self::QUERY_VAR_ACTIVE ) ) ) {
			return $posts;
		}

		$field_name = sanitize_key( $query->get( self::QUERY_VAR_FIELD ) );
		if ( empty( $field_name ) ) {
			return $posts;
		}

		$virtual_posts = array();

		foreach ( $posts as $post ) {
			// get_field() is ACF's safe API; no raw SQL.
			$repeater_rows = get_field( $field_name, $post->ID );

			if ( ! $repeater_rows || ! is_array( $repeater_rows ) ) {
				continue;
			}

			foreach ( $repeater_rows as $index => $row ) {
				$virtual_posts[] = $this->make_virtual_post( $post, $field_name, $index, is_array( $row ) ? $row : array() );
			}
		}

		return $virtual_posts;
	}

	// ------------------------------------------------------------------
	// Public action callback.
	// ------------------------------------------------------------------

	/**
	 * Hooked onto the_post. Pushes or clears Row_Context as the loop advances.
	 *
	 * WP_Post::__construct() copies ALL properties from the stdClass (via
	 * get_object_vars()), including our custom lsdfe_* properties, so we can
	 * read them from the WP_Post object that the_post fires with.
	 *
	 * @param \WP_Post $post The post being set up by setup_postdata().
	 * @return void
	 */
	public function sync_row_context( $post ): void {
		// Only act on our virtual posts.
		if ( ! isset( $post->lsdfe_provider_key )
			|| self::PROVIDER_KEY !== $post->lsdfe_provider_key ) {

			// If we previously had a context (loop just exited a repeater block),
			// clear it so stale data doesn't bleed into subsequent normal posts.
			if ( Row_Context::instance()->has_context() ) {
				Row_Context::instance()->clear();
			}
			return;
		}

		Row_Context::instance()->push(
			(int) $post->lsdfe_parent_post_id,
			self::PROVIDER_KEY,
			(int) $post->lsdfe_row_index
		);
	}

	// ------------------------------------------------------------------
	// Private helpers.
	// ------------------------------------------------------------------

	/**
	 * Builds the flat options array for the repeater field SELECT control.
	 *
	 * Queries all ACF field groups and collects top-level Repeater fields.
	 * Nested repeaters (repeater inside group) are also discovered.
	 *
	 * @return array<string, string> field_name => label map.
	 */
	private function get_repeater_field_options(): array {
		$options = array( '' => esc_html__( '— Select a Repeater Field —', 'loopsync-dynamic-fields-for-elementor' ) );

		if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'acf_get_fields' ) ) {
			return $options;
		}

		$groups = acf_get_field_groups();

		foreach ( $groups as $group ) {
			$fields = acf_get_fields( $group['key'] ?? '' );
			if ( ! $fields ) {
				continue;
			}
			$this->collect_repeater_fields( $fields, $options );
		}

		return $options;
	}

	/**
	 * Recursively collects Repeater field names from a fields array.
	 *
	 * Descends into Group fields so that repeaters nested inside a group are
	 * also discoverable.
	 *
	 * @param array<int, array<string, mixed>> $fields     ACF fields array.
	 * @param array<string, string>            $options    Result map (passed by reference).
	 * @param string                           $prefix     Dot-notation prefix for nested paths.
	 * @return void
	 */
	private function collect_repeater_fields( array $fields, array &$options, string $prefix = '' ): void {
		foreach ( $fields as $field ) {
			if ( ! isset( $field['type'], $field['name'], $field['label'] ) ) {
				continue;
			}

			$full_name = $prefix ? $prefix . '_' . $field['name'] : $field['name'];

			if ( 'repeater' === $field['type'] ) {
				$options[ $full_name ] = esc_html( $field['label'] );
			}

			// Descend into Group sub-fields.
			if ( 'group' === $field['type'] && ! empty( $field['sub_fields'] ) ) {
				$this->collect_repeater_fields( $field['sub_fields'], $options, $full_name );
			}
		}
	}

	/**
	 * Creates a virtual stdClass post representing one repeater row.
	 *
	 * All standard WP_Post fields are populated from the parent so that core
	 * template tags (the_title(), get_the_ID(), etc.) work correctly inside
	 * the Loop Item Template.
	 *
	 * Setting filter = 'raw' tells get_post() to wrap this stdClass as a
	 * WP_Post without a DB round-trip, and WP_Post::__construct() copies
	 * ALL object vars — including our custom lsdfe_* properties.
	 *
	 * @param \WP_Post|\stdClass $post       The real parent post.
	 * @param string             $field_name The ACF repeater field name.
	 * @param int                $index      Zero-based row index.
	 * @return \stdClass
	 */
	private function make_virtual_post( $post, string $field_name, int $index, array $row = array() ): \stdClass {
		$v = new \stdClass();

		// Unique negative ID for each virtual row to prevent Elementor from skipping duplicate post IDs.
		$v->ID                    = -1 * (int) ( (string) $post->ID . '9999' . str_pad( (string) $index, 2, '0', STR_PAD_LEFT ) );
		$v->post_author           = $post->post_author ?? 0;
		$v->post_date             = $post->post_date ?? '';
		$v->post_date_gmt         = $post->post_date_gmt ?? '';
		$v->post_content          = '';
		$v->post_title            = $post->post_title ?? '';
		$v->post_excerpt          = '';
		$v->post_status           = 'publish';
		$v->comment_status        = 'closed';
		$v->ping_status           = 'closed';
		$v->post_name             = ( $post->post_name ?? 'post' ) . '-row-' . $index;
		$v->post_modified         = $post->post_modified ?? '';
		$v->post_modified_gmt     = $post->post_modified_gmt ?? '';
		$v->post_content_filtered = '';
		$v->post_parent           = $post->ID;
		$v->guid                  = '';
		$v->menu_order            = $index;
		$v->post_type             = $post->post_type ?? 'post';
		$v->post_mime_type        = '';
		$v->comment_count         = 0;

		// Tells get_post() → new WP_Post($v) to skip the DB fetch.
		$v->filter = 'raw';

		// ---- Custom properties preserved by WP_Post::__construct() ----
		$v->lsdfe_provider_key   = self::PROVIDER_KEY;
		$v->lsdfe_parent_post_id = $post->ID;
		$v->lsdfe_row_index      = $index;
		$v->lsdfe_repeater_field = $field_name;
		$v->lsdfe_row_data       = $row;

		return $v;
	}
}
