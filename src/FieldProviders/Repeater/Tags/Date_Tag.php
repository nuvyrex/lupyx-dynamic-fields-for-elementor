<?php
/**
 * Repeater Date Dynamic Tag.
 *
 * Handles ACF sub-fields of types 'date_picker', 'date_time_picker', and
 * 'time_picker'. ACF returns these as pre-formatted strings according to the
 * field's "Return Format" setting — no additional formatting is applied here
 * so the user's ACF field configuration is respected.
 *
 * Output is escaped with esc_html() as the value is always a plain string.
 *
 * @package LoopDynamicFields\FieldProviders\Repeater\Tags
 */

namespace LoopDynamicFields\FieldProviders\Repeater\Tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Date_Tag
 *
 * Elementor Dynamic Tag for ACF Repeater date/time sub-fields.
 */
final class Date_Tag extends Abstract_Repeater_Tag {

	/**
	 * {@inheritDoc}
	 */
	public function get_name(): string {
		return 'ldf-repeater-date';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title(): string {
		return esc_html__( 'Repeater: Date / Time', 'loop-dynamic-fields-for-elementor' );
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
		return [ 'date_picker', 'date_time_picker', 'time_picker' ];
	}

	/**
	 * {@inheritDoc}
	 *
	 * Date strings are already formatted by ACF per the field's "Return Format".
	 * esc_html() is the appropriate escape for any date/time string.
	 *
	 * @param mixed $value
	 */
	protected function output_value( $value ): void {
		echo esc_html( (string) ( $value ?? '' ) );
	}
}
