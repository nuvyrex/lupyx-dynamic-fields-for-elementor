<?php
/**
 * Repeater Image Dynamic Tag.
 *
 * Handles ACF sub-fields of type 'image'. Extends Elementor Pro's Data_Tag
 * (rather than the base Tag) because Elementor's Image widget reads structured
 * data — ['id' => ..., 'url' => ...] — from its dynamic tag, not a plain string.
 *
 * ACF image fields can return data in three formats depending on the field's
 * "Return Format" setting:
 *   - "Image Array"  → array with 'ID', 'url', 'alt', 'width', 'height', ...
 *   - "Image ID"     → integer attachment ID
 *   - "Image URL"    → URL string
 *
 * get_value() normalises all three into the standard Elementor image array.
 *
 * Note: This class duplicates the register_controls() scaffolding that would
 * otherwise live in Abstract_Repeater_Tag. The duplication is deliberate and
 * minimal (~25 lines): it avoids imposing a trait or a second inheritance chain
 * just to share 25 lines. Document clearly and revisit in v2 if more Data_Tag
 * subclasses are added.
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
 * Class Image_Tag
 *
 * Elementor Dynamic Tag for ACF Repeater 'image' sub-fields.
 */
final class Image_Tag extends \ElementorPro\Modules\DynamicTags\Tags\Base\Data_Tag {

	/**
	 * {@inheritDoc}
	 */
	public function get_name(): string {
		return 'lpdfe-repeater-image';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title(): string {
		return esc_html__( 'Repeater: Image', 'lupyx-dynamic-fields-for-elementor' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_group(): string {
		return 'acf';
	}

	/**
	 * {@inheritDoc}
	 *
	 * IMAGE_CATEGORY allows this tag to be used in Image widget's Image Source
	 * control and any other control that accepts an image dynamic tag.
	 */
	public function get_categories(): array {
		return array( \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY );
	}

	/**
	 * Returns the ACF sub-field types this tag handles.
	 *
	 * @return string[]
	 */
	protected function get_supported_acf_types(): array {
		return array( 'image' );
	}

	// ------------------------------------------------------------------
	// Control registration — mirrors Abstract_Repeater_Tag::register_controls().
	// ------------------------------------------------------------------

	/**
	 * Registers the sub-field selector onto this Dynamic Tag.
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
				'label'              => esc_html__( 'Image Sub-field', 'lupyx-dynamic-fields-for-elementor' ),
				'type'               => \Elementor\Controls_Manager::SELECT,
				'groups'             => $grouped_options,
				'default'            => '',
				'description'        => esc_html__( 'Select the image sub-field from your ACF Repeater.', 'lupyx-dynamic-fields-for-elementor' ),
				'frontend_available' => true,
			)
		);
	}

	// ------------------------------------------------------------------
	// Data_Tag implementation.
	// ------------------------------------------------------------------

	/**
	 * Returns the image data array for the current repeater row.
	 *
	 * Normalises ACF's three image return formats into a single structure:
	 *   ['id' => int|null, 'url' => string]
	 *
	 * @param array<string, mixed> $options Unused; part of Data_Tag contract.
	 * @return array{id: int|null, url: string}
	 */
	public function get_value( array $options = array() ): array {
		$empty = array(
			'id'  => null,
			'url' => '',
		);

		$sub_field_key = sanitize_key( $this->get_settings( Sub_Field_Resolver::CTRL_SUB_FIELD ) );
		if ( empty( $sub_field_key ) ) {
			return $empty;
		}

		$raw = Sub_Field_Resolver::resolve( $sub_field_key );
		if ( null === $raw ) {
			return $empty;
		}

		// ACF "Image Array" return format.
		if ( is_array( $raw ) && isset( $raw['ID'], $raw['url'] ) ) {
			return array(
				'id'  => (int) $raw['ID'],
				'url' => esc_url_raw( $raw['url'] ),
			);
		}

		// ACF "Image ID" return format.
		if ( is_numeric( $raw ) ) {
			$url = wp_get_attachment_url( (int) $raw );
			return array(
				'id'  => (int) $raw,
				'url' => $url ? esc_url_raw( $url ) : '',
			);
		}

		// ACF "Image URL" return format.
		if ( is_string( $raw ) && '' !== $raw ) {
			return array(
				'id'  => null,
				'url' => esc_url_raw( $raw ),
			);
		}

		return $empty;
	}

	/**
	 * Renders the image URL for front-end output and editor preview.
	 *
	 * Fires the standard before/after hooks so third-party code can intercept.
	 *
	 * @return void
	 */
	public function render(): void {
		$ctx = Row_Context::instance();

		do_action( 'loop_dynamic_fields/tag/render/before', $this->get_name(), $ctx->get_parent_post_id(), $ctx->get_row_index() );

		$value = $this->get_value();
		echo esc_url( $value['url'] );

		do_action( 'loop_dynamic_fields/tag/render/after', $this->get_name(), $ctx->get_parent_post_id(), $ctx->get_row_index() );
	}
}
