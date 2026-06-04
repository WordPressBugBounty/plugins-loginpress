<?php
/**
 * Loads WPBrigade SDK core classes (single runtime).
 *
 * @package wpbrigade_sdk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/config.php';

$wpb_sdk_runtime_includes = __DIR__ . '/includes';

// Full runtime already loaded from this or another bundle path.
if ( class_exists( 'WPBRIGADE_Opt_Manager', false ) ) {
	return;
}

// Merge new helpers (function_exists guards) and load classes missing from legacy bundles.
require_once $wpb_sdk_runtime_includes . '/wpb-sdk-essential-functions.php';

if ( ! class_exists( 'WPBRIGADE_Optin_Verification', false ) ) {
	require_once $wpb_sdk_runtime_includes . '/class-wpb-sdk-optin-verification.php';
}

if ( ! class_exists( 'WPBRIGADE_Opt_Manager', false ) ) {
	require_once $wpb_sdk_runtime_includes . '/class-wpb-opt-manager.php';
}

// Legacy LoginPress may have loaded Logger from a different path already.
if ( ! class_exists( 'WPBRIGADE_Logger', false ) ) {
	require_once $wpb_sdk_runtime_includes . '/class-wpb-sdk-logger.php';
}
