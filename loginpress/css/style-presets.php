<?php
/**
 * Get the Template and implement it's design.
 *
 * @package LoginPress
 * @since  1.0.9
 * @version 6.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set the default preset value.
 *
 * @var string $loginpress_preset Set the default preset value.
 * @since 3.0.5
 * @version 6.2.0
 */
$loginpress_preset        = get_option( 'customize_presets_settings', true );
$loginpress_default_theme = true === $loginpress_preset && ( empty( $this->loginpress_key ) && empty( $this->loginpress_setting ) ) ? 'minimalist' : 'default1';

$loginpress_selected_preset = get_option( 'customize_presets_settings', $loginpress_default_theme );

if ( 'default1' === $loginpress_selected_preset ) {
	include_once LOGINPRESS_ROOT_PATH . 'classes/customizer/css/themes/default-1.php';
	echo first_presets(); // phpcs:ignore
} elseif ( 'minimalist' === $loginpress_selected_preset ) {
	include_once LOGINPRESS_ROOT_PATH . 'classes/customizer/css/themes/free-minimalist.php';
	echo free_minimalist_presets(); // phpcs:ignore
} else {
	do_action( 'loginpress_add_pro_theme', $loginpress_selected_preset );
}
