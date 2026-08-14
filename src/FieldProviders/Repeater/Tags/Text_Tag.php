<?php
/**
 * Repeater Text Dynamic Tag.
 *
 * Handles ACF sub-fields of types 'text' and 'textarea'.
 * Output is plain-text escaped with esc_html().
 *
 * @package LoopDynamicFields\FieldProviders\Repeater\Tags
 */

namespace LoopDynamicFields\FieldProviders\Repeater\Tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Text_Tag
 *
 * Elementor Dynamic Tag for ACF Repeater text / textarea sub-fields.
 */
final class Text_Tag extends Abstract_Repeater_Tag {

	/**
	 * {@inheritDoc}
	 */
	public function get_name(): string {
		return 'ldf-repeater-text';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title(): string {
		return esc_html__( 'Repeater: Text / Textarea', 'loop-dynamic-fields-for-elementor' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_categories(): array {
		return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_supported_acf_types(): array {
		return [ 'text', 'textarea', 'email', 'password' ];
	}

	/**
	 * {@inheritDoc}
	 *
	 * Plain text — no HTML allowed. esc_html() is the correct escape function.
	 *
	 * @param mixed $value
	 */
	protected function output_value( $value ): void {
		echo esc_html( (string) ( $value ?? '' ) );
	}
}
