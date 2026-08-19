<?php
/**
 * Immutable value object describing a single Elementor Dynamic Tag.
 *
 * Instances are returned by Field_Type_Contract::get_tag_descriptors().
 * The Elementor integration layer reads these to register tags without
 * needing to know the concrete tag class hierarchy.
 *
 * Using a value object here (rather than passing class names as raw strings)
 * gives us a typed, self-documenting API that can be extended with additional
 * metadata (e.g. icon, category overrides) in future without changing the contract.
 *
 * @package LupyxSyncDynamicFields\FieldProviders
 */

namespace LupyxSyncDynamicFields\FieldProviders;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tag_Descriptor
 *
 * Carries the minimal data the integration layer needs to register one
 * Elementor Dynamic Tag on behalf of a Field Type Provider.
 */
final class Tag_Descriptor {

	/**
	 * Fully-qualified class name of the Dynamic Tag to register.
	 *
	 * The class must exist in the autoloader map and must extend either
	 * \Elementor\Core\DynamicTags\Tag or \ElementorPro\Modules\DynamicTags\Tags\Base\Data_Tag.
	 *
	 * @var string
	 */
	private string $tag_class_name;

	/**
	 * Elementor group slug under which this tag appears in the tag picker.
	 *
	 * Use 'acf' to appear alongside Elementor Pro's own ACF tags, or a custom
	 * slug to group all tags from this plugin under a distinct heading.
	 *
	 * @var string
	 */
	private string $elementor_group;

	/**
	 * Constructor.
	 *
	 * @param string $tag_class_name  Fully-qualified class name of the Dynamic Tag.
	 * @param string $elementor_group Elementor group slug (e.g. 'acf').
	 */
	public function __construct( string $tag_class_name, string $elementor_group ) {
		$this->tag_class_name  = $tag_class_name;
		$this->elementor_group = $elementor_group;
	}

	/**
	 * Returns the fully-qualified Dynamic Tag class name.
	 *
	 * @return string
	 */
	public function get_tag_class_name(): string {
		return $this->tag_class_name;
	}

	/**
	 * Returns the Elementor group slug for this tag.
	 *
	 * @return string
	 */
	public function get_elementor_group(): string {
		return $this->elementor_group;
	}
}
