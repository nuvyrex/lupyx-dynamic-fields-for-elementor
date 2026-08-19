<?php
/**
 * Field Type Provider contract.
 *
 * Every ACF field type handler (Repeater, Flexible Content, Gallery, etc.)
 * must implement this interface. The Elementor integration layer talks ONLY
 * to this contract — it never references a concrete provider class by name.
 *
 * Design principles:
 *  - Methods are minimal and single-purpose.
 *  - The interface carries no shared state and no shared logic; use an abstract
 *    class only when two or more providers genuinely share implementation.
 *  - PHP interfaces place no constraint on what the implementing class extends,
 *    so tag classes can still extend Elementor base classes as required.
 *
 * @package LupyxSyncDynamicFields\FieldProviders
 */

namespace LupyxSyncDynamicFields\FieldProviders;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Field_Type_Contract
 *
 * Contract that every field-type handler must satisfy.
 */
interface Field_Type_Contract {

	/**
	 * Returns the unique machine key identifying this provider.
	 *
	 * Must be unique across all registered providers.
	 * Used by Provider_Registry as the index key.
	 *
	 * Example: 'repeater', 'flexible_content', 'gallery'
	 *
	 * @return string
	 */
	public function get_provider_key(): string;

	/**
	 * Returns the ACF field type slug(s) this provider handles.
	 *
	 * Allows Provider_Registry::get_for_field_type() to route a field type to
	 * the correct provider without a switch/if chain in core code.
	 *
	 * Example: ['repeater'] or ['flexible_content']
	 *
	 * @return string[]
	 */
	public function get_handled_field_types(): array;

	/**
	 * Returns Tag_Descriptor objects for every Dynamic Tag this provider exposes.
	 *
	 * The Elementor integration layer iterates these and calls
	 * $dynamic_tags->register( new $descriptor->get_tag_class_name() ).
	 *
	 * Returning descriptors (value objects) instead of tag instances keeps this
	 * contract free from Elementor class inheritance requirements; the tag
	 * classes themselves extend Elementor base classes as needed.
	 *
	 * @return Tag_Descriptor[]
	 */
	public function get_tag_descriptors(): array;

	/**
	 * Registers provider-specific Elementor controls onto the Loop Grid element.
	 *
	 * Called once per element type during Elementor's element registration
	 * phase, not once per render. Providers own their own control labels and
	 * control keys — the integration layer never hard-codes them.
	 *
	 * @param \Elementor\Element_Base $element The Loop Grid widget element instance.
	 * @return void
	 */
	public function register_loop_controls( \Elementor\Element_Base $element ): void;

	/**
	 * Filters the WP_Query arguments for the Loop Grid query.
	 *
	 * Called by Loop_Grid_Controller for every registered provider on the
	 * elementor/query/query_args filter. Providers that add no query behaviour
	 * must return $args unmodified.
	 *
	 * @param array<string,mixed>    $args   Current query arguments.
	 * @param \Elementor\Widget_Base $widget The Loop Grid widget instance.
	 * @return array<string,mixed> Modified (or unmodified) query arguments.
	 */
	public function filter_query_args( array $args, \Elementor\Widget_Base $widget ): array;

	/**
	 * Filters the posts array after WP_Query has run.
	 *
	 * Called by Loop_Grid_Controller on the_posts filter. For Repeater this
	 * expands one post into N virtual posts (one per row). For Flexible Content
	 * it would expand by layout count, etc.
	 *
	 * Providers that add no post-list behaviour must return $posts unmodified.
	 *
	 * @param \WP_Post[]|\stdClass[] $posts All posts returned by WP_Query.
	 * @param \WP_Query              $query The current WP_Query instance.
	 * @return \WP_Post[]|\stdClass[] Modified (or unmodified) post array.
	 */
	public function filter_post_list( array $posts, \WP_Query $query ): array;
}
