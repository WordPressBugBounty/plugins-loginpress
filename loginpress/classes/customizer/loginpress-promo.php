<?php
/**
 * LoginPress Promo Controls
 *
 * Create controller for promotion.
 *
 * @package LoginPress
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
require_once ( defined( 'LOGINPRESS_ROOT_PATH' ) ? LOGINPRESS_ROOT_PATH : '' ) . 'classes/customizer/class-loginpress-promo.php'; //phpcs:ignore

$wp_customize->add_section(
	'lpcustomize_google_font',
	array(
		'title'    => __( 'Google Fonts', 'loginpress' ),
		'priority' => 49,
		'panel'    => 'loginpress_panel',
	)
);

$wp_customize->add_setting(
	'loginpress_customization[google_font]',
	array(
		'default'    => '',
		'type'       => 'option',
		'capability' => 'manage_options',
		'transport'  => 'postMessage',
	)
);

$wp_customize->add_control(
	new LoginPress_Promo(
		$wp_customize,
		'loginpress_customization[google_font]',
		array(
			'section'    => 'lpcustomize_google_font',
			'thumbnail'  => plugins_url( 'img/promo/font_promo.png', defined( 'LOGINPRESS_ROOT_FILE' ) ? LOGINPRESS_ROOT_FILE : '' ),
			'promo_text' => __( 'Unlock Premium Feature', 'loginpress' ),
			'link'       => 'https://loginpress.pro/pricing/?utm_source=loginpress-lite&utm_medium=customizer-google-fonts&utm_campaign=pro-upgrade&utm_content=Unlock+Premium+Feature+CTA',
		)
	)
);

$wp_customize->add_section(
	'customize_recaptcha',
	array(
		'title'    => __( 'reCAPTCHA', 'loginpress' ),
		'priority' => 24,
		'panel'    => 'loginpress_panel',
	)
);

$wp_customize->add_setting(
	'loginpress_customization[recaptcha_error_message]',
	array(
		'type'       => 'option',
		'capability' => 'manage_options',
		'transport'  => 'postMessage',
	)
);

$wp_customize->add_control(
	new LoginPress_Promo(
		$wp_customize,
		'loginpress_customization[recaptcha_error_message]',
		array(
			'section'    => 'customize_recaptcha',
			'thumbnail'  => plugins_url( 'img/promo/recaptcha_option_promo.png', defined( 'LOGINPRESS_ROOT_FILE' ) ? LOGINPRESS_ROOT_FILE : '' ),
			'promo_text' => __( 'Unlock Premium Feature', 'loginpress' ),
			'link'       => 'https://loginpress.pro/pricing/?utm_source=loginpress-lite&utm_medium=customizer-recaptcha&utm_campaign=pro-upgrade&utm_content=Unlock+Premium+Feature+CTA',
		)
	)
);
