<?php
/**
 * Runtime row context store.
 *
 * Solves the core problem of communicating "which repeater row am I inside?"
 * to Dynamic Tags during Loop Grid rendering — WITHOUT encoding row data into
 * a virtual post ID.
 *
 * ## Why a context store instead of ID encoding?
 *
 * The reference approach encoded (parentPostId + '999999' + rowIndex) into a
 * negative integer on the virtual post's ID field, then reversed the arithmetic
 * in every Dynamic Tag's render method. That breaks on post IDs ≥ 10 000 000
 * and distributes the decoding magic across every tag class.
 *
 * Row_Context is pushed once by Repeater_Provider::filter_post_list() as each
 * virtual post is prepared. Dynamic Tags call Row_Context::instance() to read
 * the current parent post ID and row index — a single, clean lookup.
 *
 * The virtual post's actual $post->ID is set to the real parent post ID (positive,
 * valid). WordPress core functions that read get_the_ID() inside the loop work
 * correctly without any ID arithmetic.
 *
 * ## Threading
 *
 * PHP is single-threaded per request. Multiple Loop Grids on the same page
 * render sequentially, not concurrently. Row_Context is stack-safe because
 * push() is called for each virtual post just before the_post() advances to it,
 * and clear() is called when the provider's post list is fully expanded.
 *
 * @package LoopDynamicFields\Runtime
 */

namespace LoopDynamicFields\Runtime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Row_Context
 *
 * Singleton context store for the current repeater (or other provider) row
 * being rendered inside a Loop Grid.
 */
final class Row_Context {

	/**
	 * The singleton instance.
	 *
	 * @var Row_Context|null
	 */
	private static ?Row_Context $instance = null;

	/**
	 * The real WordPress post ID that owns the current row's data.
	 *
	 * @var int|null
	 */
	private ?int $parent_post_id = null;

	/**
	 * The provider key that set this context (e.g. 'repeater').
	 *
	 * @var string|null
	 */
	private ?string $provider_key = null;

	/**
	 * The zero-based row index within the current field's data.
	 *
	 * @var int|null
	 */
	private ?int $row_index = null;

	/**
	 * Private constructor — use instance() instead.
	 */
	private function __construct() {}

	/**
	 * Returns (and lazily creates) the singleton instance.
	 *
	 * @return Row_Context
	 */
	public static function instance(): Row_Context {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Pushes a new row context.
	 *
	 * Called by a provider's filter_post_list() implementation just before
	 * each virtual post is added to the list. Dynamic Tags call the getters
	 * during their render() to know which row's data to read.
	 *
	 * Fires the loop_dynamic_fields/row_context/pushed action for third-party
	 * integrations (e.g. caching layers, debug toolbars).
	 *
	 * @param int    $parent_post_id The real WP post ID that owns the field data.
	 * @param string $provider_key   The key of the provider setting this context.
	 * @param int    $row_index      Zero-based index of the current row.
	 * @return void
	 */
	public function push( int $parent_post_id, string $provider_key, int $row_index ): void {
		$this->parent_post_id = $parent_post_id;
		$this->provider_key   = $provider_key;
		$this->row_index      = $row_index;

		/**
		 * Fires after a row context is pushed.
		 *
		 * @param int    $parent_post_id The real WP post ID.
		 * @param string $provider_key   The provider key (e.g. 'repeater').
		 * @param int    $row_index      Zero-based row index.
		 */
		do_action( 'loop_dynamic_fields/row_context/pushed', $parent_post_id, $provider_key, $row_index );
	}

	/**
	 * Clears the current row context.
	 *
	 * Called after the virtual post list is fully assembled and the row loop
	 * is complete, returning the context to a clean state.
	 *
	 * @return void
	 */
	public function clear(): void {
		$this->parent_post_id = null;
		$this->provider_key   = null;
		$this->row_index      = null;
	}

	/**
	 * Returns true when a row context is currently active.
	 *
	 * Tags should check this before attempting to read row data, to avoid
	 * rendering outside of a Loop Grid context.
	 *
	 * @return bool
	 */
	public function has_context(): bool {
		return null !== $this->parent_post_id;
	}

	/**
	 * Returns the parent post ID, or null if no context is active.
	 *
	 * @return int|null
	 */
	public function get_parent_post_id(): ?int {
		return $this->parent_post_id;
	}

	/**
	 * Returns the provider key that set this context, or null if no context is active.
	 *
	 * @return string|null
	 */
	public function get_provider_key(): ?string {
		return $this->provider_key;
	}

	/**
	 * Returns the zero-based row index, or null if no context is active.
	 *
	 * @return int|null
	 */
	public function get_row_index(): ?int {
		return $this->row_index;
	}
}
