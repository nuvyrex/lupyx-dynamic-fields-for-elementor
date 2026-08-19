<?php
/**
 * Uninstall handler for Lupyx Dynamic Fields for Elementor.
 *
 * This file is executed by WordPress when the plugin is deleted via the admin UI.
 * It removes every option and transient created by the plugin so no orphaned data
 * is left behind in the database.
 *
 * Direct access is blocked; the WP_UNINSTALL_PLUGIN constant is set only by the
 * WordPress uninstall routine.
 *
 * @package LupyxSyncDynamicFields
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

( static function () {
	// ---------------------------------------------------------------------------
	// Options created by the plugin.
	// ---------------------------------------------------------------------------

	$loop_dynamic_fields_options = array(
		'lpdfe_version',
		'lpdfe_install_date',
		'lpdfe_initial_version',
	);

	foreach ( $loop_dynamic_fields_options as $loop_dynamic_fields_option_name ) {
		delete_option( $loop_dynamic_fields_option_name );

		// Also clean up multisite per-site options if running in a network.
		if ( is_multisite() ) {
			delete_site_option( $loop_dynamic_fields_option_name );
		}
	}

	// ---------------------------------------------------------------------------
	// Transients created by the plugin.
	// ---------------------------------------------------------------------------

	$loop_dynamic_fields_transients = array(
		'lpdfe_acf_field_cache',
	);

	foreach ( $loop_dynamic_fields_transients as $loop_dynamic_fields_transient_name ) {
		delete_transient( $loop_dynamic_fields_transient_name );

		if ( is_multisite() ) {
			delete_site_transient( $loop_dynamic_fields_transient_name );
		}
	}
} )();
