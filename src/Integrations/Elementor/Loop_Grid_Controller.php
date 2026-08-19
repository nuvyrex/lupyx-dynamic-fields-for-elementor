<?php
/**
 * Loop Grid controller.
 *
 * Bridges Elementor's Loop Grid widget hooks to the Provider Registry. Every
 * provider declared in the registry participates automatically — adding a new
 * field type requires zero changes to this file.
 *
 * ## Hooks owned by this class
 *
 * elementor/element/loop-grid/section_query/before_section_end
 *   Fires in the Elementor editor when the Loop Grid Query panel is rendered.
 *   Used to inject per-provider controls (toggles, field selectors, etc.).
 *   Guarded by is_admin() so it never runs on public front-end requests.
 *
 * elementor/query/query_args  (filter, priority 10)
 *   Fires inside ElementorPro\Modules\QueryControl\Classes\Elementor_Post_Query
 *   with ($query_args, $widget). The second argument IS the widget instance
 *   (confirmed from elementor-post-query.php source:
 *     apply_filters('elementor/query/query_args', $this->query_args, $this->widget)).
 *   Each provider's filter_query_args() may stamp custom query vars to mark
 *   queries it owns.
 *
 * the_posts  (filter, priority 10)
 *   Fires for every WP_Query on the page. Scoped to front-end, non-admin,
 *   non-REST, non-doing-ajax requests. Providers identify their own queries
 *   by checking for their custom query var (set in filter_query_args).
 *
 * ## Design constraints
 *  - The word "Repeater" appears nowhere in this file.
 *  - No concrete provider class is ever named.
 *  - All dispatch goes through Field_Type_Contract.
 *
 * @package LupyxSyncDynamicFields\Integrations\Elementor
 */

namespace LupyxSyncDynamicFields\Integrations\Elementor;

use LupyxSyncDynamicFields\FieldProviders\Provider_Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Loop_Grid_Controller
 *
 * Wires three Elementor/WordPress hooks and dispatches each to every registered
 * Field Type Provider via the Provider_Registry.
 */
final class Loop_Grid_Controller {

	/**
	 * The shared Provider Registry instance.
	 *
	 * @var Provider_Registry
	 */
	private Provider_Registry $registry;

	/**
	 * Constructor. Registers all hooks immediately.
	 *
	 * @param Provider_Registry $registry The fully-seeded provider registry.
	 */
	public function __construct( Provider_Registry $registry ) {
		$this->registry = $registry;

		// Inject provider controls into the Loop Grid and Loop Carousel Query sections.
		add_action(
			'elementor/element/loop-grid/section_query/before_section_end',
			array( $this, 'inject_provider_controls' )
		);
		add_action(
			'elementor/element/loop-carousel/section_query/before_section_end',
			array( $this, 'inject_provider_controls' )
		);

		// Route query args through every registered provider.
		add_filter(
			'elementor/query/query_args',
			array( $this, 'dispatch_query_args' ),
			10,
			2
		);

		// Route the posts array through every registered provider.
		// Protected by each provider checking its own custom query var.
		add_filter(
			'the_posts',
			array( $this, 'dispatch_post_list' ),
			10,
			2
		);
	}

	// ------------------------------------------------------------------
	// Hook callbacks.
	// ------------------------------------------------------------------

	/**
	 * Calls register_loop_controls() on every registered provider.
	 *
	 * Hooked onto: elementor/element/loop-grid/section_query/before_section_end
	 *
	 * Each provider injects its own controls (switchers, field selectors, etc.)
	 * directly onto the Elementor element. The controller does not know what
	 * controls any specific provider adds.
	 *
	 * @param \Elementor\Element_Base $element The Loop Grid widget element.
	 * @return void
	 */
	public function inject_provider_controls( \Elementor\Element_Base $element ): void {
		foreach ( $this->registry->all() as $provider ) {
			$provider->register_loop_controls( $element );
		}
	}

	/**
	 * Dispatches WP_Query args through every registered provider.
	 *
	 * Hooked onto: elementor/query/query_args (priority 10)
	 *
	 * The second parameter is the Elementor widget instance (confirmed from
	 * Elementor Pro source: $this->widget in Elementor_Post_Query::get_query_args()).
	 * Each provider reads its own widget controls from $widget->get_settings_for_display()
	 * and stamps custom query vars when its feature is enabled.
	 *
	 * Returns $args immediately if the widget is not a 'loop-grid' widget,
	 * preventing unnecessary processing on other Elementor query widgets.
	 *
	 * @param array<string,mixed>    $args   Current WP_Query arguments.
	 * @param \Elementor\Widget_Base $widget The Loop Grid widget instance.
	 * @return array<string,mixed>
	 */
	public function dispatch_query_args( array $args, \Elementor\Widget_Base $widget ): array {
		// Bail early for non-Loop widgets (Posts, Portfolio, etc.).
		if ( 'loop-grid' !== $widget->get_name() && 'loop-carousel' !== $widget->get_name() ) {
			return $args;
		}

		foreach ( $this->registry->all() as $provider ) {
			$args = $provider->filter_query_args( $args, $widget );
		}

		return $args;
	}

	/**
	 * Dispatches the posts array through every registered provider.
	 *
	 * Hooked onto: the_posts (priority 10) — front-end only.
	 *
	 * Each provider inspects the query for its own custom var (stamped in
	 * filter_query_args) and passes through unchanged if the query doesn't
	 * belong to it. This makes the chain safe even with multiple providers.
	 *
	 * @param \WP_Post[]|\stdClass[] $posts All posts from the current WP_Query.
	 * @param \WP_Query              $query The current WP_Query instance.
	 * @return \WP_Post[]|\stdClass[]
	 */
	public function dispatch_post_list( array $posts, \WP_Query $query ): array {
		foreach ( $this->registry->all() as $provider ) {
			$posts = $provider->filter_post_list( $posts, $query );
		}

		return $posts;
	}

	// ------------------------------------------------------------------
	// Private helpers.
	// ------------------------------------------------------------------

	/**
	 * Returns true when the current request is a public front-end page load.
	 *
	 * We scope the_posts filter here because virtual posts must not bleed into:
	 *  - wp-admin list tables (is_admin check)
	 *  - WP REST API responses (REST_REQUEST constant)
	 *  - wp-cron background requests (DOING_CRON constant)
	 *  - Elementor's own Ajax preview (DOING_AJAX + referrer check would be
	 *    unreliable; handled instead by the QUERY_VAR_ACTIVE guard inside
	 *    each provider's filter_post_list)
	 *
	 * @return bool
	 */
	private function is_public_front_end(): bool {
		if ( is_admin() ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return false;
		}

		return true;
	}
}
