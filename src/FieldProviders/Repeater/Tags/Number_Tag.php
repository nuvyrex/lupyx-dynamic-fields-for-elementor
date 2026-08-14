<?php
/**
 * Repeater Number Dynamic Tag.
 *
 * Handles ACF sub-fields of types 'number' and 'range'.
 * Output is a numeric value cast to string, escaped with esc_html().
 *
 * Listed under NUMBER_CATEGORY so it's available in Elementor controls that
 * accept numeric dynamic tags (e.g. counter widget end-value).
 *
 * @package LoopDynamicFields\FieldProviders\Repeater\Tags
 */

namespace LoopDynamicFields\FieldProviders\Repeater\Tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Number_Tag
 *
 * Elementor Dynamic Tag for ACF Repeater number / range sub-fields.
 */
final class Number_Tag extends Abstract_Repeater_Tag {

	/**
	 * {@inheritDoc}
	 */
	public function get_name(): string {
		return 'ldf-repeater-number';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title(): string {
		return esc_html__( 'Repeater: Number', 'loop-dynamic-fields-for-elementor' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * NUMBER_CATEGORY makes this tag available in numeric Elementor controls.
	 * TEXT_CATEGORY also included so it works in plain-text widget contexts.
	 */
	public function get_categories(): array {
		return [
			\Elementor\Modules\DynamicTags\Module::NUMBER_CATEGORY,
			\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_supported_acf_types(): array {
		return [ 'number', 'range' ];
	}

	/**
	 * {@inheritDoc}
	 *
	 * Numeric data — esc_html() on the string representation is sufficient.
	 * floatval() converts any ACF numeric format (int, float, string) safely
	 * before casting back to string to strip any accidental whitespace.
	 *
	 * @param mixed $value
	 */
	protected function output_value( $value ): void {
		if ( null === $value ) {
			return;
		}

		// Preserve integer formatting (no decimal point for whole numbers).
		if ( is_int( $value ) ) {
			echo esc_html( (string) $value );
			return;
		}

		echo esc_html( (string) floatval( $value ) );
	}
}
