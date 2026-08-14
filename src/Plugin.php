<?php
/**
 * Plugin singleton and main orchestrator.
 *
 * Responsibilities (single-responsibility):
 *  - Run the DependencyChecker; bail gracefully if dependencies are unmet.
 *  - On elementor/init, seed the Provider Registry via the public hook, then
 *    hand off to the Elementor integration Loader.
 *
 * This class contains NO field-type logic, NO Elementor widget logic, and
 * NO ACF API calls. It wires things together and nothing else.
 *
 * @package LoopDynamicFields
 */

namespace LoopDynamicFields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 *
 * Singleton entry point. Instantiated once from the main plugin file.
 */
final class Plugin {

	/**
	 * The singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Returns (and lazily creates) the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor — prevents direct instantiation.
	 * Hooks into plugins_loaded so all plugins are available before we check deps.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ] );
	}

	/**
	 * Fires on plugins_loaded.
	 *
	 * Runs dependency checks, and — only if all deps
	 * are satisfied — queues the Elementor bootstrap for elementor/init.
	 *
	 * @return void
	 */
	public function on_plugins_loaded(): void {
		$checker = new DependencyChecker();

		if ( ! $checker->passes() ) {
			$checker->register_admin_notices();
			return;
		}

		// All dependencies met; boot after Elementor itself has initialised.
		add_action( 'elementor/init', [ $this, 'boot_elementor' ] );

		// Record install date on first activation (idempotent; add_option no-ops if key exists).
		add_option( 'ldf_install_date', gmdate( 'Y-m-d H:i:s' ) );
		add_option( 'ldf_initial_version', LDF_VERSION );
		update_option( 'ldf_version', LDF_VERSION );
	}

	/**
	 * Fires on elementor/init.
	 *
	 * Initialises the Provider Registry, fires the public registration hook so
	 * both internal and third-party providers can register themselves, then
	 * hands off to the Elementor integration Loader.
	 *
	 * @return void
	 */
	public function boot_elementor(): void {
		$registry = FieldProviders\Provider_Registry::instance();

		// Register built-in providers at priority 5, BEFORE the do_action fires,
		// so third-party code at the default priority 10 can override them if needed.
		// Using the same public hook our own code uses proves the mechanism from day one.
		add_action(
			'loop_dynamic_fields/register_providers',
			static function ( FieldProviders\Provider_Registry $reg ): void {
				$reg->register( new FieldProviders\Repeater\Repeater_Provider() );
			},
			5
		);

		/**
		 * Fires once on elementor/init to collect Field Type Providers.
		 *
		 * Both the plugin's own bootstrap and any third-party code use this
		 * hook to register providers. Providers registered here are available
		 * for the lifetime of the request.
		 *
		 * @see  FieldProviders\Field_Type_Contract  The interface every provider must implement.
		 * @see  HOOKS.md                            Full parameter and usage documentation.
		 *
		 * @param FieldProviders\Provider_Registry $registry Pass your provider to $registry->register().
		 */
		do_action( 'loop_dynamic_fields/register_providers', $registry );

		// Boot the Elementor-facing integration. The Loader talks only to the
		// registry — never to a specific provider class by name.
		new Integrations\Elementor\Loader( $registry );
	}
}
