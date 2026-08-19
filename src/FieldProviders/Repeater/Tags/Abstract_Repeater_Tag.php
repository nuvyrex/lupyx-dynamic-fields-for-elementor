<?php
/**
 * Abstract base for all text-category Repeater Dynamic Tags.
 *
 * Extends Elementor's base Tag class (appropriate for TEXT_CATEGORY,
 * NUMBER_CATEGORY outputs). Image and URL tags extend Data_Tag instead and
 * call Sub_Field_Resolver directly — this base class is not suitable for them.
 *
 * ## Responsibilities
 *  - register_controls(): builds the sub-field SELECT (grouped by repeater,
 *    filtered to types declared by get_supported_acf_types()).
 *  - render(): gets the sub-field key from settings, calls Sub_Field_Resolver,
 *    fires before/after hooks, delegates to output_value().
 *
 * ## Extending
 *  Subclasses must implement:
 *   - get_name()                  — Elementor tag slug (e.g. 'lpdfe-repeater-text')
 *   - get_title()                 — Human-readable label in the tag picker
 *   - get_categories()            — Elementor category constants array
 *   - get_supported_acf_types()   — ACF field type slugs for control filtering
 *   - output_value( $value )      — Echo the value with correct escaping
 *
 * @package LupyxSyncDynamicFields\FieldProviders\Repeater\Tags
 */

namespace LupyxSyncDynamicFields\FieldProviders\Repeater\Tags;

use LupyxSyncDynamicFields\FieldProviders\Repeater\Sub_Field_Resolver;
use LupyxSyncDynamicFields\Runtime\Row_Context;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract class Abstract_Repeater_Tag
 *
 * Shared scaffolding for text-category Repeater Dynamic Tags.
 */
abstract class Abstract_Repeater_Tag extends \Elementor\Core\DynamicTags\Tag {

	// ------------------------------------------------------------------
	// Abstract interface for subclasses.
	// ------------------------------------------------------------------

	/**
	 * Returns the ACF sub-field type slugs this tag handles.
	 *
	 * Used by Sub_Field_Resolver::build_sub_field_options() to filter the
	 * grouped SELECT to only compatible sub-fields.
	 *
	 * Example: ['text', 'textarea']
	 *
	 * @return string[]
	 */
	abstract protected function get_supported_acf_types(): array;

	/**
	 * Echoes the resolved sub-field value with appropriate escaping.
	 *
	 * Subclasses choose the correct escaping function for their field type:
	 *   - esc_html()   for plain text
	 *   - wp_kses_post() for rich text / WYSIWYG
	 *   - esc_url()    for URL values
	 *
	 * @param mixed $value The raw value returned by Sub_Field_Resolver::resolve().
	 * @return void
	 */
	abstract protected function output_value( $value ): void;

	// ------------------------------------------------------------------
	// Elementor tag identity — common to all Repeater tags.
	// ------------------------------------------------------------------

	/**
	 * Returns the Elementor group slug. Using 'acf' groups our tags alongside
	 * Elementor Pro's native ACF tags, giving a consistent editor experience.
	 *
	 * @return string
	 */
	public function get_group(): string {
		return 'acf';
	}

	// ------------------------------------------------------------------
	// Elementor control registration.
	// ------------------------------------------------------------------

	/**
	 * Registers the sub-field selector control onto this Dynamic Tag.
	 *
	 * Builds a grouped SELECT from all ACF repeater sub-fields whose type is
	 * declared by get_supported_acf_types(). If no matching sub-fields exist
	 * (e.g. the site has no ACF field groups configured), the section is skipped.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		$grouped_options = Sub_Field_Resolver::build_sub_field_options(
			$this->get_supported_acf_types()
		);

		if ( empty( $grouped_options ) ) {
			return;
		}

		$this->add_control(
			Sub_Field_Resolver::CTRL_SUB_FIELD,
			array(
				'label'              => esc_html__( 'Sub-field', 'lupyx-dynamic-fields-for-elementor' ),
				'type'               => \Elementor\Controls_Manager::SELECT,
				'groups'             => $grouped_options,
				'default'            => '',
				'description'        => esc_html__( 'Select the repeater sub-field to display. Set the Loop Grid Preview Settings to a post of this type to see changes.', 'lupyx-dynamic-fields-for-elementor' ),
				'frontend_available' => true,
			)
		);
	}

	// ------------------------------------------------------------------
	// Rendering.
	// ------------------------------------------------------------------

	/**
	 * Renders the sub-field value.
	 *
	 * Reads the selected sub-field key from Elementor settings, resolves the
	 * value via Sub_Field_Resolver (which reads Row_Context), fires the
	 * before/after hooks, and delegates output to the subclass's output_value().
	 *
	 * Returns early (empty output) if:
	 *  - No sub-field key is selected in the tag settings.
	 *  - Row_Context has no active context (tag is outside a Loop Grid).
	 *  - Sub_Field_Resolver cannot find the value.
	 *
	 * @return void
	 */
	public function render(): void {
		$sub_field_key = sanitize_key( $this->get_settings( Sub_Field_Resolver::CTRL_SUB_FIELD ) );

		if ( empty( $sub_field_key ) ) {
			return;
		}

		$ctx = Row_Context::instance();

		/**
		 * Fires before a Repeater Dynamic Tag renders its value.
		 *
		 * @param string   $tag_name       Elementor tag name slug.
		 * @param int|null $parent_post_id Real post ID from Row_Context.
		 * @param int|null $row_index      Zero-based row index from Row_Context.
		 */
		do_action(
			'loop_dynamic_fields/tag/render/before',
			$this->get_name(),
			$ctx->get_parent_post_id(),
			$ctx->get_row_index()
		);

		$value = Sub_Field_Resolver::resolve( $sub_field_key );

		$this->output_value( $value );

		/**
		 * Fires after a Repeater Dynamic Tag renders its value.
		 *
		 * @param string   $tag_name       Elementor tag name slug.
		 * @param int|null $parent_post_id Real post ID from Row_Context.
		 * @param int|null $row_index      Zero-based row index from Row_Context.
		 */
		do_action(
			'loop_dynamic_fields/tag/render/after',
			$this->get_name(),
			$ctx->get_parent_post_id(),
			$ctx->get_row_index()
		);
	}
}
