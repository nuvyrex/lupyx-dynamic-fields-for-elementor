<?php
/**
 * Dependency checker for Loop Dynamic Fields for Elementor.
 *
 * Validates that Elementor, Elementor Pro, and ACF Pro are active and meet
 * minimum version requirements. The Plugin singleton calls passes() before
 * booting any plugin logic; if it returns false, admin notices are queued
 * and nothing else runs — preventing fatal errors on misconfigured sites.
 *
 * @package LoopDynamicFields
 */

namespace LoopDynamicFields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DependencyChecker
 *
 * Collects human-readable error messages for each unmet dependency and
 * exposes them as dismissible WP admin notices.
 */
final class DependencyChecker {

	/**
	 * Human-readable descriptions of every unmet requirement.
	 *
	 * @var string[]
	 */
	private array $errors = [];

	/**
	 * Runs all dependency checks.
	 *
	 * Populates $this->errors for any unmet requirement.
	 * Call register_admin_notices() afterwards if this returns false.
	 *
	 * @return bool True when every dependency is satisfied.
	 */
	public function passes(): bool {
		$this->check_elementor();
		$this->check_elementor_pro();
		$this->check_acf();

		return empty( $this->errors );
	}

	/**
	 * Registers WP admin notices for every unmet requirement.
	 *
	 * Only call this after passes() has returned false.
	 *
	 * @return void
	 */
	public function register_admin_notices(): void {
		add_action( 'admin_notices', [ $this, 'render_notices' ] );
	}

	/**
	 * Renders all collected error messages as WP admin notices.
	 *
	 * Hooked onto admin_notices by register_admin_notices().
	 *
	 * @return void
	 */
	public function render_notices(): void {
		foreach ( $this->errors as $message ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				wp_kses(
					$message,
					[
						'strong' => [],
						'a'      => [
							'href'   => [],
							'target' => [],
							'rel'    => [],
						],
					]
				)
			);
		}
	}

	// ------------------------------------------------------------------
	// Private check methods — one per dependency.
	// ------------------------------------------------------------------

	/**
	 * Checks that Elementor (free) is active and meets the minimum version.
	 *
	 * @return void
	 */
	private function check_elementor(): void {
		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			$this->errors[] = sprintf(
				/* translators: 1: This plugin's name, 2: Required plugin name. */
				__( '%1$s requires %2$s to be installed and activated.', 'loop-dynamic-fields-for-elementor' ),
				'<strong>' . esc_html__( 'Loop Dynamic Fields for Elementor', 'loop-dynamic-fields-for-elementor' ) . '</strong>',
				'<strong>Elementor</strong>'
			);
			return;
		}

		if ( version_compare( ELEMENTOR_VERSION, LDF_MIN_ELEMENTOR, '<' ) ) {
			$this->errors[] = sprintf(
				/* translators: 1: This plugin's name, 2: Required plugin name, 3: Minimum version. */
				__( '%1$s requires %2$s version %3$s or higher. Please update Elementor.', 'loop-dynamic-fields-for-elementor' ),
				'<strong>' . esc_html__( 'Loop Dynamic Fields for Elementor', 'loop-dynamic-fields-for-elementor' ) . '</strong>',
				'<strong>Elementor</strong>',
				'<strong>' . esc_html( LDF_MIN_ELEMENTOR ) . '</strong>'
			);
		}
	}

	/**
	 * Checks that Elementor Pro is active and meets the minimum version.
	 *
	 * @return void
	 */
	private function check_elementor_pro(): void {
		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			$this->errors[] = sprintf(
				/* translators: 1: This plugin's name, 2: Required plugin name. */
				__( '%1$s requires %2$s to be installed and activated.', 'loop-dynamic-fields-for-elementor' ),
				'<strong>' . esc_html__( 'Loop Dynamic Fields for Elementor', 'loop-dynamic-fields-for-elementor' ) . '</strong>',
				'<strong>Elementor Pro</strong>'
			);
			return;
		}

		if ( version_compare( ELEMENTOR_PRO_VERSION, LDF_MIN_ELEMENTOR_PRO, '<' ) ) {
			$this->errors[] = sprintf(
				/* translators: 1: This plugin's name, 2: Required plugin name, 3: Minimum version. */
				__( '%1$s requires %2$s version %3$s or higher. Please update Elementor Pro.', 'loop-dynamic-fields-for-elementor' ),
				'<strong>' . esc_html__( 'Loop Dynamic Fields for Elementor', 'loop-dynamic-fields-for-elementor' ) . '</strong>',
				'<strong>Elementor Pro</strong>',
				'<strong>' . esc_html( LDF_MIN_ELEMENTOR_PRO ) . '</strong>'
			);
		}
	}

	/**
	 * Checks that Advanced Custom Fields Pro is active and meets the minimum version.
	 *
	 * @return void
	 */
	private function check_acf(): void {
		if ( ! class_exists( 'ACF' ) ) {
			$this->errors[] = sprintf(
				/* translators: 1: This plugin's name, 2: Required plugin name. */
				__( '%1$s requires %2$s to be installed and activated.', 'loop-dynamic-fields-for-elementor' ),
				'<strong>' . esc_html__( 'Loop Dynamic Fields for Elementor', 'loop-dynamic-fields-for-elementor' ) . '</strong>',
				'<strong>Advanced Custom Fields Pro</strong>'
			);
			return;
		}

		if ( defined( 'ACF_VERSION' ) && version_compare( ACF_VERSION, LDF_MIN_ACF, '<' ) ) {
			$this->errors[] = sprintf(
				/* translators: 1: This plugin's name, 2: Required plugin name, 3: Minimum version. */
				__( '%1$s requires %2$s version %3$s or higher. Please update ACF Pro.', 'loop-dynamic-fields-for-elementor' ),
				'<strong>' . esc_html__( 'Loop Dynamic Fields for Elementor', 'loop-dynamic-fields-for-elementor' ) . '</strong>',
				'<strong>Advanced Custom Fields Pro</strong>',
				'<strong>' . esc_html( LDF_MIN_ACF ) . '</strong>'
			);
		}
	}
}
