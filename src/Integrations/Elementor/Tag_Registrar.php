<?php
/**
 * Dynamic Tag registrar.
 *
 * Iterates every registered Field Type Provider, collects their Tag_Descriptor
 * objects, and registers the corresponding Dynamic Tag classes with Elementor's
 * Dynamic Tag manager (elementor/dynamic_tags/register hook).
 *
 * Design constraints (all verified by code):
 *  - Never names a concrete provider class.
 *  - Never names a concrete tag class.
 *  - Driven entirely by Field_Type_Contract::get_tag_descriptors()
 *    and Tag_Descriptor::get_tag_class_name().
 *
 * @package LoopDynamicFields\Integrations\Elementor
 */

namespace LoopDynamicFields\Integrations\Elementor;

use LoopDynamicFields\FieldProviders\Provider_Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tag_Registrar
 *
 * Hooked onto elementor/dynamic_tags/register. Collects Tag_Descriptors from
 * every registered provider and hands them to Elementor's tag manager.
 */
final class Tag_Registrar {

	/**
	 * The shared Provider Registry instance.
	 *
	 * @var Provider_Registry
	 */
	private Provider_Registry $registry;

	/**
	 * Tracks tag names that have already been registered in this request.
	 * Guards against duplicate registration if the hook fires more than once.
	 *
	 * @var array<string, true>
	 */
	private array $registered = [];

	/**
	 * Constructor.
	 *
	 * @param Provider_Registry $registry The fully-seeded provider registry.
	 */
	public function __construct( Provider_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Registers all Dynamic Tags from all providers with Elementor.
	 *
	 * Called by Loader via:
	 *   add_action( 'elementor/dynamic_tags/register', [ $registrar, 'register_all' ] )
	 *
	 * Workflow per provider per descriptor:
	 *  1. Resolve the tag class name from the Tag_Descriptor.
	 *  2. Verify the class actually exists in the autoloader.
	 *  3. Guard against duplicate registration.
	 *  4. Instantiate and hand to $dynamic_tags->register().
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags Elementor's tag manager instance.
	 * @return void
	 */
	public function register_all( \Elementor\Core\DynamicTags\Manager $dynamic_tags ): void {
		foreach ( $this->registry->all() as $provider ) {
			foreach ( $provider->get_tag_descriptors() as $descriptor ) {
				$class_name = $descriptor->get_tag_class_name();

				// Skip if the class is not loadable (avoids fatal errors on misconfigured sites).
				if ( ! class_exists( $class_name ) ) {
					continue;
				}

				// Prevent double-registration if this hook fires more than once.
				if ( isset( $this->registered[ $class_name ] ) ) {
					continue;
				}

				$dynamic_tags->register( new $class_name() );

				$this->registered[ $class_name ] = true;
			}
		}
	}
}
