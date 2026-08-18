<?php
/**
 * Elementor integration bootstrapper.
 *
 * Instantiated once by Plugin::boot_elementor() after the Provider Registry
 * has been seeded with all providers (built-in + third-party).
 *
 * Responsibilities:
 *  1. Wire Tag_Registrar onto elementor/dynamic_tags/register so Elementor's
 *     editor loads all Dynamic Tags from every registered provider.
 *  2. Wire Loop_Grid_Controller onto the three Loop Grid hooks (element controls,
 *     query args, the_posts expansion).
 *  3. Enqueue editor-only assets (CSS for the grouped SELECT control styling).
 *
 * This class talks exclusively to the Provider_Registry — it never references
 * any concrete provider or tag class by name. Adding a new Field Type Provider
 * (e.g. a "Flexible Content" provider in v2) requires zero changes here.
 *
 * @package LoopSyncDynamicFields\Integrations\Elementor
 */

namespace LoopSyncDynamicFields\Integrations\Elementor;

use LoopSyncDynamicFields\FieldProviders\Provider_Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Loader
 *
 * Single entry point for all Elementor hook wiring. Keeps Plugin.php free of
 * Elementor-specific coupling — Plugin only instantiates this class.
 */
final class Loader {

	/**
	 * The shared Provider Registry instance.
	 *
	 * @var Provider_Registry
	 */
	private Provider_Registry $registry;

	/**
	 * Constructor. Wires all Elementor hooks immediately.
	 *
	 * Bails silently if the registry has no providers (nothing to wire).
	 *
	 * @param Provider_Registry $registry The fully-seeded provider registry.
	 */
	public function __construct( Provider_Registry $registry ) {
		$this->registry = $registry;

		if ( ! $registry->has_providers() ) {
			return;
		}

		$this->register_dynamic_tags();
		$this->register_loop_grid_controls();
		$this->enqueue_editor_assets();
	}

	// ------------------------------------------------------------------
	// Private wiring helpers.
	// ------------------------------------------------------------------

	/**
	 * Hooks Tag_Registrar::register_all() onto elementor/dynamic_tags/register.
	 *
	 * Tag_Registrar walks every provider's Tag_Descriptors and hands each
	 * Dynamic Tag class to Elementor's tag manager. No class names are
	 * resolved here — that happens inside Tag_Registrar.
	 *
	 * @return void
	 */
	private function register_dynamic_tags(): void {
		$registrar = new Tag_Registrar( $this->registry );

		add_action(
			'elementor/dynamic_tags/register',
			array( $registrar, 'register_all' )
		);
	}

	/**
	 * Instantiates Loop_Grid_Controller, which wires its own hooks.
	 *
	 * Loop_Grid_Controller handles the three hooks:
	 *  - elementor/element/loop-grid/section_query/before_section_end
	 *  - elementor/query/query_args
	 *  - the_posts (front-end only)
	 *
	 * @return void
	 */
	private function register_loop_grid_controls(): void {
		new Loop_Grid_Controller( $this->registry );
	}

	/**
	 * Enqueues a lightweight editor stylesheet.
	 *
	 * The Elementor editor renders the grouped SELECT control for sub-field
	 * selection. A small stylesheet improves the visual grouping of those
	 * SELECT options and labels the plugin's controls clearly.
	 *
	 * The stylesheet is generated in the assets/ directory (Checkpoint 5).
	 * The hook fires only in the Elementor editor context.
	 *
	 * @return void
	 */
	private function enqueue_editor_assets(): void {
		add_action(
			'elementor/editor/before_enqueue_styles',
			static function (): void {
				$editor_css = LDF_DIR . 'assets/editor.css';

				// Only enqueue if the compiled stylesheet exists.
				// During development it may not exist yet.
				if ( ! file_exists( $editor_css ) ) {
					return;
				}

				wp_enqueue_style(
					'ldf-editor',
					LDF_URL . 'assets/editor.css',
					array(),
					LDF_VERSION
				);
			}
		);
	}
}
