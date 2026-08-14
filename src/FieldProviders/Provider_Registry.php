<?php
/**
 * Field Type Provider Registry.
 *
 * Holds all registered Field_Type_Contract implementations for the lifetime of
 * a request. Every consumer of provider data goes through this class — nothing
 * else in the codebase should reference a concrete provider class by name.
 *
 * Providers register themselves via the loop_dynamic_fields/register_providers
 * action hook (see Plugin::boot_elementor and HOOKS.md).
 *
 * @package LoopDynamicFields\FieldProviders
 */

namespace LoopDynamicFields\FieldProviders;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Provider_Registry
 *
 * Singleton registry. Provides typed lookup by provider key or by ACF field type.
 */
final class Provider_Registry {

	/**
	 * The singleton instance.
	 *
	 * @var Provider_Registry|null
	 */
	private static ?Provider_Registry $instance = null;

	/**
	 * Map of provider_key => provider instance.
	 *
	 * @var array<string, Field_Type_Contract>
	 */
	private array $providers = [];

	/**
	 * Private constructor — use instance() instead.
	 */
	private function __construct() {}

	/**
	 * Returns (and lazily creates) the singleton instance.
	 *
	 * @return Provider_Registry
	 */
	public static function instance(): Provider_Registry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers a Field Type Provider.
	 *
	 * If a provider with the same key is already registered it will be
	 * overwritten (last registration wins — intentional to allow overrides).
	 *
	 * @param Field_Type_Contract $provider The provider instance to register.
	 * @return void
	 */
	public function register( Field_Type_Contract $provider ): void {
		$this->providers[ $provider->get_provider_key() ] = $provider;
	}

	/**
	 * Returns all registered providers, keyed by their provider key.
	 *
	 * @return array<string, Field_Type_Contract>
	 */
	public function all(): array {
		return $this->providers;
	}

	/**
	 * Returns a single provider by its key, or null if not found.
	 *
	 * @param string $key The provider key (e.g. 'repeater').
	 * @return Field_Type_Contract|null
	 */
	public function get( string $key ): ?Field_Type_Contract {
		return $this->providers[ $key ] ?? null;
	}

	/**
	 * Returns the first provider that declares it handles the given ACF field type.
	 *
	 * Eliminates switch/if chains in the integration layer; callers can route
	 * any ACF type to its handler without knowing provider class names.
	 *
	 * @param string $acf_field_type The ACF field type slug (e.g. 'repeater').
	 * @return Field_Type_Contract|null Null if no registered provider handles this type.
	 */
	public function get_for_field_type( string $acf_field_type ): ?Field_Type_Contract {
		foreach ( $this->providers as $provider ) {
			if ( in_array( $acf_field_type, $provider->get_handled_field_types(), true ) ) {
				return $provider;
			}
		}

		return null;
	}

	/**
	 * Returns true if at least one provider is registered.
	 *
	 * Useful for early-exit guards in the integration layer.
	 *
	 * @return bool
	 */
	public function has_providers(): bool {
		return ! empty( $this->providers );
	}
}
