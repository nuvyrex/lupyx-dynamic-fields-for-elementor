<?php
/**
 * Sub-field value resolver and control option builder for Repeater tags.
 *
 * Provides static utilities that all Repeater Dynamic Tag classes use,
 * regardless of which Elementor base class (Tag vs Data_Tag) they extend.
 * Centralising these methods here prevents code duplication across the tag
 * hierarchy and keeps Row_Context access in one place.
 *
 * @package LoopSyncDynamicFields\FieldProviders\Repeater
 */

namespace LoopSyncDynamicFields\FieldProviders\Repeater;

use LoopSyncDynamicFields\Runtime\Row_Context;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sub_Field_Resolver
 *
 * Resolves sub-field values using the current Row_Context and builds
 * the grouped SELECT options for Elementor Dynamic Tag controls.
 */
final class Sub_Field_Resolver {

	/**
	 * Elementor control key for the sub-field selector.
	 *
	 * Defined here (rather than on the tag base class) so it is accessible to
	 * both Abstract_Repeater_Tag subclasses and the Data_Tag subclasses
	 * (Image_Tag, Url_Tag) without requiring a shared inheritance chain.
	 *
	 * @var string
	 */
	const CTRL_SUB_FIELD = 'lsdfe_repeater_sub_field';

	/**
	 * Private constructor — this class is a static utility; never instantiate it.
	 */
	private function __construct() {}

	/**
	 * Resolves the value of an ACF repeater sub-field for the current row.
	 *
	 * Strategy:
	 *  1. If current global $post is our virtual post carrying pre-loaded lsdfe_row_data,
	 *     extract the value directly from memory (O(1), zero DB overhead).
	 *  2. Otherwise resolve via Row_Context (or fallback to get_the_ID() row 0 when
	 *     previewing inside the Elementor Loop Item Template editor).
	 *  3. Uses acf_get_field() and get_field() for robust, location-independent lookup.
	 *
	 * @param string $sub_field_key ACF field key or name (e.g. 'field_5f8b7d3c').
	 * @return mixed The raw sub-field value, or null if not resolvable.
	 */
	public static function resolve( string $sub_field_key ) {
		if ( empty( $sub_field_key ) ) {
			return null;
		}

		global $post;

		// 1. Direct in-memory lookup if global $post is our virtual post carrying row data
		if ( isset( $post->lsdfe_provider_key ) && 'repeater' === $post->lsdfe_provider_key && isset( $post->lsdfe_row_data ) && is_array( $post->lsdfe_row_data ) ) {
			// Resolve field name from key if needed
			$sub_field      = function_exists( 'acf_get_field' ) ? acf_get_field( $sub_field_key ) : null;
			$sub_field_name = $sub_field['name'] ?? $sub_field_key;

			if ( isset( $post->lsdfe_row_data[ $sub_field_name ] ) ) {
				return $post->lsdfe_row_data[ $sub_field_name ];
			}

			if ( isset( $post->lsdfe_row_data[ $sub_field_key ] ) ) {
				return $post->lsdfe_row_data[ $sub_field_key ];
			}
		}

		// 2. Resolve target post ID and row index from Row_Context or preview fallback
		$ctx = Row_Context::instance();
		if ( $ctx->has_context() ) {
			$parent_post_id = $ctx->get_parent_post_id();
			$row_index      = $ctx->get_row_index();
		} else {
			// Template preview fallback: preview first row of current post
			$parent_post_id = (int) ( get_the_ID() ?: get_queried_object_id() );
			$row_index      = 0;
		}

		if ( ! $parent_post_id ) {
			return null;
		}

		// 3. Resolve via acf_get_field()
		if ( function_exists( 'acf_get_field' ) ) {
			$sub_field = acf_get_field( $sub_field_key );
			if ( $sub_field && ! empty( $sub_field['name'] ) ) {
				$sub_field_name = $sub_field['name'];

				// Determine parent repeater name
				$parent_field  = ! empty( $sub_field['parent'] ) ? acf_get_field( $sub_field['parent'] ) : null;
				$repeater_name = ( $parent_field && 'repeater' === ( $parent_field['type'] ?? '' ) )
					? ( $parent_field['name'] ?? '' )
					: '';

				if ( $repeater_name && function_exists( 'get_field' ) ) {
					$rows = get_field( $repeater_name, $parent_post_id );
					if ( is_array( $rows ) && isset( $rows[ $row_index ][ $sub_field_name ] ) ) {
						return $rows[ $row_index ][ $sub_field_name ];
					}
				}
			}
		}

		// 4. Fallback search through get_field_objects()
		if ( function_exists( 'get_field_objects' ) ) {
			$field_objects = get_field_objects( $parent_post_id );
			if ( is_array( $field_objects ) ) {
				foreach ( $field_objects as $field ) {
					if ( 'repeater' !== ( $field['type'] ?? '' ) || empty( $field['sub_fields'] ) || ! is_array( $field['value'] ?? null ) ) {
						continue;
					}

					foreach ( $field['sub_fields'] as $sf ) {
						if ( ( $sf['key'] ?? '' ) === $sub_field_key || ( $sf['name'] ?? '' ) === $sub_field_key ) {
							$rows = $field['value'];
							return $rows[ $row_index ][ $sf['name'] ] ?? null;
						}
					}
				}
			}
		}

		return null;
	}

	/**
	 * Builds grouped SELECT options for the sub-field selector control.
	 *
	 * Returns an array in the format expected by Elementor's SELECT control
	 * 'groups' argument:
	 *   [ ['label' => 'Repeater label', 'options' => ['field_key' => 'Sub-field label']], ... ]
	 *
	 * Only repeater sub-fields whose ACF type is in $supported_types are included.
	 * Passing an empty $supported_types array includes all sub-field types.
	 *
	 * @param string[] $supported_types ACF field type slugs to allow (e.g. ['text', 'textarea']).
	 *                                  Pass an empty array to include all types.
	 * @return array<int, array{label: string, options: array<string, string>}>
	 */
	public static function build_sub_field_options( array $supported_types = array() ): array {
		$grouped = array();

		if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'acf_get_fields' ) ) {
			return $grouped;
		}

		$acf_groups = acf_get_field_groups();

		foreach ( $acf_groups as $group ) {
			$fields = acf_get_fields( $group['key'] ?? '' );
			if ( ! $fields ) {
				continue;
			}

			foreach ( $fields as $field ) {
				if ( 'repeater' !== ( $field['type'] ?? '' ) || empty( $field['sub_fields'] ) ) {
					continue;
				}

				$sub_options = array();

				foreach ( $field['sub_fields'] as $sf ) {
					if ( ! empty( $supported_types )
						&& ! in_array( $sf['type'] ?? '', $supported_types, true ) ) {
						continue;
					}

					// Value = ACF field key (globally unique). Label = human-readable.
					$sub_options[ $sf['key'] ] = esc_html( $sf['label'] );
				}

				if ( ! empty( $sub_options ) ) {
					$grouped[] = array(
						'label'   => esc_html( $field['label'] ),
						'options' => $sub_options,
					);
				}
			}
		}

		return $grouped;
	}
}
