<?php
/**
 * Repeater WYSIWYG Dynamic Tag.
 *
 * Handles ACF sub-fields of type 'wysiwyg' (WordPress-native TinyMCE editor).
 * Output is HTML — escaped with wp_kses_post() to allow safe HTML tags while
 * stripping any malicious markup.
 *
 * @package LoopDynamicFields\FieldProviders\Repeater\Tags
 */

namespace LoopDynamicFields\FieldProviders\Repeater\Tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Wysiwyg_Tag
 *
 * Elementor Dynamic Tag for ACF Repeater wysiwyg sub-fields.
 */
final class Wysiwyg_Tag extends Abstract_Repeater_Tag {

	/**
	 * {@inheritDoc}
	 */
	public function get_name(): string {
		return 'ldf-repeater-wysiwyg';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title(): string {
		return esc_html__( 'Repeater: WYSIWYG', 'loopsync-dynamic-fields-for-elementor' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_categories(): array {
		return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_supported_acf_types(): array {
		return array( 'wysiwyg', 'oembed' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * Rich text — wp_kses_post() strips unsafe tags while preserving safe HTML.
	 *
	 * @param mixed $value
	 */
	protected function output_value( $value ): void {
		echo wp_kses_post( (string) ( $value ?? '' ) );
	}
}
