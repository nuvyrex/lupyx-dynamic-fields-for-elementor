<?php
/**
 * Repeater URL Dynamic Tag.
 *
 * Handles ACF sub-fields that store URL-like data: url, link, page_link, file.
 * Extends Data_Tag so this tag is selectable in Elementor's URL controls
 * (Button URL, Link, etc.) as well as TEXT_CATEGORY controls.
 *
 * ACF link-type fields return data in different shapes:
 *   - 'url'       → plain string URL
 *   - 'link'      → array {'url', 'title', 'target'}
 *   - 'page_link' → plain string URL
 *   - 'file'      → array {'ID', 'url', 'filename', ...} or plain string URL
 *
 * get_value() normalises all of these to a plain URL string.
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
 * Class Url_Tag
 *
 * Elementor Dynamic Tag for ACF Repeater URL-type sub-fields.
 */
final class Url_Tag extends \ElementorPro\Modules\DynamicTags\Tags\Base\Data_Tag {

	/**
	 * {@inheritDoc}
	 */
	public function get_name(): string {
		return 'lpdfe-repeater-url';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title(): string {
		return esc_html__( 'Repeater: URL / Link', 'lupyx-dynamic-fields-for-elementor' );
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
	 * URL_CATEGORY: usable in button/link URL controls.
	 * TEXT_CATEGORY: also usable as plain text output.
	 */
	public function get_categories(): array {
		return array(
			\Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
			\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
		);
	}

	/**
	 * Returns the ACF sub-field types this tag handles.
	 *
	 * @return string[]
	 */
	protected function get_supported_acf_types(): array {
		return array( 'url', 'link', 'page_link', 'file' );
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
				'label'              => esc_html__( 'URL / Link Sub-field', 'lupyx-dynamic-fields-for-elementor' ),
				'type'               => \Elementor\Controls_Manager::SELECT,
				'groups'             => $grouped_options,
				'default'            => '',
				'description'        => esc_html__( 'Select the URL, Link, Page Link, or File sub-field from your ACF Repeater.', 'lupyx-dynamic-fields-for-elementor' ),
				'frontend_available' => true,
			)
		);
	}

	// ------------------------------------------------------------------
	// Data_Tag implementation.
	// ------------------------------------------------------------------

	/**
	 * Returns the URL string for the current repeater row.
	 *
	 * Normalises ACF's URL/link/page_link/file return formats to a plain
	 * escaped URL string. Returns an empty string when no URL is resolvable.
	 *
	 * @param array<string, mixed> $options Unused; part of Data_Tag contract.
	 * @return string A sanitised URL, or '' if not resolvable.
	 */
	public function get_value( array $options = array() ): string {
		$sub_field_key = sanitize_key( $this->get_settings( Sub_Field_Resolver::CTRL_SUB_FIELD ) );
		if ( empty( $sub_field_key ) ) {
			return '';
		}

		$raw = Sub_Field_Resolver::resolve( $sub_field_key );
		if ( null === $raw ) {
			return '';
		}

		// ACF 'link' field returns array {'url', 'title', 'target'}.
		// ACF 'file' field (array format) returns array {'ID', 'url', ...}.
		if ( is_array( $raw ) && isset( $raw['url'] ) ) {
			return esc_url_raw( $raw['url'] );
		}

		// Plain string — 'url', 'page_link', or 'file' (URL format).
		if ( is_string( $raw ) && '' !== $raw ) {
			return esc_url_raw( $raw );
		}

		return '';
	}

	/**
	 * Renders the URL for front-end output and editor preview.
	 *
	 * @return void
	 */
	public function render(): void {
		$ctx = Row_Context::instance();

		do_action( 'loop_dynamic_fields/tag/render/before', $this->get_name(), $ctx->get_parent_post_id(), $ctx->get_row_index() );

		echo esc_url( $this->get_value() );

		do_action( 'loop_dynamic_fields/tag/render/after', $this->get_name(), $ctx->get_parent_post_id(), $ctx->get_row_index() );
	}
}
