<?php
/**
 * Dependency checker for Lupyx Dynamic Fields for Elementor.
 *
 * Validates that Elementor, Elementor Pro, and ACF Pro are active and meet
 * minimum version requirements. The Plugin singleton calls passes() before
 * booting any plugin logic; if it returns false, admin notices are queued
 * and nothing else runs — preventing fatal errors on misconfigured sites.
 *
 * @package LupyxSyncDynamicFields
 */

namespace LupyxSyncDynamicFields;

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
	private array $errors = array();

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
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
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
					array(
						'strong' => array(),
						'a'      => array(
							'href'   => array(),
							'target' => array(),
							'rel'    => array(),
						),
					)
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
				__( '%1$s requires %2$s to be installed and activated.', 'lupyx-dynamic-fields-for-elementor' ),
				'<strong>' . esc_html__( 'Lupyx Dynamic Fields for Elementor', 'lupyx-dynamic-fields-for-elementor' ) . '</strong>',
				'<strong>Elementor</strong>'
			);
			return;
		}

		if ( version_compare( ELEMENTOR_VERSION, LPDFE_MIN_ELEMENTOR, '<' ) ) {
			$this->errors[] = sprintf(
				/* translators: 1: This plugin's name, 2: Required plugin name, 3: Minimum version. */
				__( '%1$s requires %2$s version %3$s or higher. Please update Elementor.', 'lupyx-dynamic-fields-for-elementor' ),
				'<strong>' . esc_html__( 'Lupyx Dynamic Fields for Elementor', 'lupyx-dynamic-fields-for-elementor' ) . '</strong>',
				'<strong>Elementor</strong>',
				'<strong>' . esc_html( LPDFE_MIN_ELEMENTOR ) . '</strong>'
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
				__( '%1$s requires %2$s to be installed and activated.', 'lupyx-dynamic-fields-for-elementor' ),
				'<strong>' . esc_html__( 'Lupyx Dynamic Fields for Elementor', 'lupyx-dynamic-fields-for-elementor' ) . '</strong>',
				'<strong>Elementor Pro</strong>'
			);
			return;
		}

		if ( version_compare( ELEMENTOR_PRO_VERSION, LPDFE_MIN_ELEMENTOR_PRO, '<' ) ) {
			$this->errors[] = sprintf(
				/* translators: 1: This plugin's name, 2: Required plugin name, 3: Minimum version. */
				__( '%1$s requires %2$s version %3$s or higher. Please update Elementor Pro.', 'lupyx-dynamic-fields-for-elementor' ),
				'<strong>' . esc_html__( 'Lupyx Dynamic Fields for Elementor', 'lupyx-dynamic-fields-for-elementor' ) . '</strong>',
				'<strong>Elementor Pro</strong>',
				'<strong>' . esc_html( LPDFE_MIN_ELEMENTOR_PRO ) . '</strong>'
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
				__( '%1$s requires %2$s to be installed and activated.', 'lupyx-dynamic-fields-for-elementor' ),
				'<strong>' . esc_html__( 'Lupyx Dynamic Fields for Elementor', 'lupyx-dynamic-fields-for-elementor' ) . '</strong>',
				'<strong>Advanced Custom Fields Pro</strong>'
			);
			return;
		}

		if ( defined( 'ACF_VERSION' ) && version_compare( ACF_VERSION, LPDFE_MIN_ACF, '<' ) ) {
			$this->errors[] = sprintf(
				/* translators: 1: This plugin's name, 2: Required plugin name, 3: Minimum version. */
				__( '%1$s requires %2$s version %3$s or higher. Please update ACF Pro.', 'lupyx-dynamic-fields-for-elementor' ),
				'<strong>' . esc_html__( 'Lupyx Dynamic Fields for Elementor', 'lupyx-dynamic-fields-for-elementor' ) . '</strong>',
				'<strong>Advanced Custom Fields Pro</strong>',
				'<strong>' . esc_html( LPDFE_MIN_ACF ) . '</strong>'
			);
		}
	}
}
