<?php
/**
 * LoginPress Customizer Messages Trait.
 *
 * Handles customizer messages functionality for LoginPress.
 *
 * @package   LoginPress
 * @subpackage Traits\Customizer
 * @since     6.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'LoginPress_Customizer_Messages' ) ) {
	/**
	 * LoginPress Customizer Messages Trait.
	 *
	 * Handles customizer messages functionality for LoginPress.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\Customizer
	 * @since     6.1.0
	 */
	trait LoginPress_Customizer_Messages {

		/**
		 * Setup error section.
		 *
		 * @param WP_Customize_Manager $wp_customize The WordPress Customize object.
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		private function setup_error_section( $wp_customize ) {
			// Include required variables.
			include LOGINPRESS_ROOT_PATH . 'include/customizer-strings.php';

			$wp_customize->add_section(
				'section_error',
				array(
					'title'       => __( 'Error Messages', 'loginpress' ),
					'description' => '',
					'priority'    => 30,
					'panel'       => 'loginpress_panel',
				)
			);

			$error = 0;
			while ( $error < 11 ) :
				$wp_customize->add_setting(
					"loginpress_customization[{$error_control[$error]}]",
					array(
						'default'           => $error_default[ $error ],
						'type'              => 'option',
						'capability'        => 'manage_options',
						'transport'         => 'postMessage',
						'sanitize_callback' => 'wp_kses_post',
					)
				);

				$wp_customize->add_control(
					"loginpress_customization[{$error_control[$error]}]",
					array(
						'label'    => $error_label[ $error ],
						'section'  => 'section_error',
						'priority' => 5,
						'settings' => "loginpress_customization[{$error_control[$error]}]",
					)
				);
				++$error;
			endwhile;
		}

		/**
		 * Setup welcome section.
		 *
		 * @param WP_Customize_Manager $wp_customize The WordPress Customize object.
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		private function setup_welcome_section( $wp_customize ) {
			// Include required variables.
			include LOGINPRESS_ROOT_PATH . 'include/customizer-strings.php';

			$wp_customize->add_section(
				'section_welcome',
				array(
					'title'       => __( 'Welcome Messages', 'loginpress' ),
					'description' => '',
					'priority'    => 35,
					'panel'       => 'loginpress_panel',
				)
			);

			$welcome = 0;
			while ( $welcome < 5 ) :

				$wp_customize->add_setting(
					"loginpress_customization[{$welcome_control[$welcome]}]",
					array(
						'type'              => 'option',
						'capability'        => 'manage_options',
						'transport'         => 'postMessage',
						'sanitize_callback' => $welcome_sanitization[ $welcome ],
					)
				);

				$wp_customize->add_control(
					"loginpress_customization[{$welcome_control[$welcome]}]",
					array(
						'label'       => $welcome_label[ $welcome ],
						'section'     => 'section_welcome',
						'priority'    => 5,
						'settings'    => "loginpress_customization[{$welcome_control[$welcome]}]",
						'input_attrs' => array(
							'placeholder' => $welcome_default[ $welcome ],
						),
					)
				);

				++$welcome;
			endwhile;

			$wp_customize->add_setting(
				'loginpress_customization[message_background_color]',
				array(
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'sanitize_text_field', // validates 3 or 6 digit HTML hex color code.
				)
			);

			$wp_customize->add_control(
				new LoginPress_Color_Picker_Alpha(
					$wp_customize,
					'loginpress_customization[message_background_color]',
					array(
						'label'       => __( 'Message Field Background Color:', 'loginpress' ),
						'section'     => 'section_welcome',
						'priority'    => 30,
						'settings'    => 'loginpress_customization[message_background_color]',
						'input_attrs' => array(
							'name'               => 'loginpress_customization[message_background_color]',
							'data-alpha-enabled' => 'true',
						),
					)
				)
			);
		}

		/**
		 * Setup footer section.
		 *
		 * @param WP_Customize_Manager $wp_customize The WordPress Customize object.
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		private function setup_footer_section( $wp_customize ) {
			// Include required variables.
			include LOGINPRESS_ROOT_PATH . 'include/customizer-strings.php';

			$wp_customize->add_section(
				'section_footer',
				array(
					'title'       => __( 'Form Footer', 'loginpress' ),
					'description' => '',
					'priority'    => 40,
					'panel'       => 'loginpress_panel',
				)
			);

			$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_footer', 3, 4 );

			$wp_customize->add_setting(
				'loginpress_customization[footer_display_text]',
				array(
					'default'           => true,
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'loginpress_sanitize_checkbox',
				)
			);

			$wp_customize->add_control(
				new LoginPress_Radio_Control(
					$wp_customize,
					'loginpress_customization[footer_display_text]',
					array(
						'settings' => 'loginpress_customization[footer_display_text]',
						'label'    => __( 'Enable Footer Text:', 'loginpress' ),
						'section'  => 'section_footer',
						'priority' => 5,
						'type'     => 'ios',
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[login_footer_text]',
				array(
					'default'           => __( 'Lost your password?', 'loginpress' ),
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'wp_kses_post',
				)
			);

			$wp_customize->add_control(
				'loginpress_customization[login_footer_text]',
				array(
					'label'    => __( 'Lost Password Text:', 'loginpress' ),
					'section'  => 'section_footer',
					'priority' => 10,
					'settings' => 'loginpress_customization[login_footer_text]',
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[login_footer_text_decoration]',
				array(
					'default'           => 'none',
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'loginpress_sanitize_select',
				)
			);

			$wp_customize->add_control(
				'loginpress_customization[login_footer_text_decoration]',
				array(
					'settings' => 'loginpress_customization[login_footer_text_decoration]',
					'label'    => __( 'Select Text Decoration:', 'loginpress' ),
					'section'  => 'section_footer',
					'priority' => 15,
					'type'     => 'select',
					'choices'  => array(
						'none'         => 'none',
						'overline'     => 'overline',
						'line-through' => 'line-through',
						'underline'    => 'underline',
					),
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[login_footer_color]',
				array(
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'sanitize_text_field',
				)
			);

			$wp_customize->add_control(
				new LoginPress_Color_Picker_Alpha(
					$wp_customize,
					'loginpress_customization[login_footer_color]',
					array(
						'label'       => __( 'Footer Text Color:', 'loginpress' ),
						'section'     => 'section_footer',
						'priority'    => 20,
						'settings'    => 'loginpress_customization[login_footer_color]',
						'input_attrs' => array(
							'name'               => 'loginpress_customization[login_footer_color]',
							'data-alpha-enabled' => 'true',
						),
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[login_footer_color_hover]',
				array(
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'sanitize_text_field', // validates 3 or 6 digit HTML hex color code.
				)
			);

			$wp_customize->add_control(
				new LoginPress_Color_Picker_Alpha(
					$wp_customize,
					'loginpress_customization[login_footer_color_hover]',
					array(
						'label'       => __( 'Footer Text Hover Color:', 'loginpress' ),
						'section'     => 'section_footer',
						'priority'    => 25,
						'settings'    => 'loginpress_customization[login_footer_color_hover]',
						'input_attrs' => array(
							'name'               => 'loginpress_customization[login_footer_color_hover]',
							'data-alpha-enabled' => 'true',
						),
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[login_footer_font_size]',
				array(
					'default'           => '13',
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'absint',
				)
			);

			$wp_customize->add_control(
				new LoginPress_Range_Control(
					$wp_customize,
					'loginpress_customization[login_footer_font_size]',
					array(
						'type'        => 'loginpress-range',
						'label'       => __( 'Text Font Size:', 'loginpress' ),
						'section'     => 'section_footer',
						'settings'    => 'loginpress_customization[login_footer_font_size]',
						'default'     => '13',
						'priority'    => 30,
						'input_attrs' => array(
							'min'    => 0,
							'max'    => 100,
							'step'   => 1,
							'suffix' => 'px',
						),
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[login_footer_bg_color]',
				array(
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'sanitize_text_field', // validates 3 or 6 digit HTML hex color code.
				)
			);

			$wp_customize->add_control(
				new LoginPress_Color_Picker_Alpha(
					$wp_customize,
					'loginpress_customization[login_footer_bg_color]',
					array(
						'label'       => __( 'Footer Background Color:', 'loginpress' ),
						'section'     => 'section_footer',
						'priority'    => 35,
						'settings'    => 'loginpress_customization[login_footer_bg_color]',
						'input_attrs' => array(
							'name'               => 'loginpress_customization[login_footer_bg_color]',
							'data-alpha-enabled' => 'true',
						),
					)
				)
			);

			$this->loginpress_hr_setting( $wp_customize, $close_control, 'section_footer', 0, 36 );

			$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_footer', 4, 40 );

			$wp_customize->add_setting(
				'loginpress_customization[back_display_text]',
				array(
					'default'           => true,
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'loginpress_sanitize_checkbox',
				)
			);

			/**
			 * [Enable / Disable Footer Text with LoginPress_Radio_Control]
			 *
			 * @since 1.0.1
			 * @version 1.0.23
			 */
			$wp_customize->add_control(
				new LoginPress_Radio_Control(
					$wp_customize,
					'loginpress_customization[back_display_text]',
					array(
						'settings' => 'loginpress_customization[back_display_text]',
						'label'    => __( 'Enable "Back to" Text:', 'loginpress' ),
						'section'  => 'section_footer',
						'priority' => 45,
						'type'     => 'ios', // light, ios, flat.
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[login_back_text_decoration]',
				array(
					'default'           => 'none',
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'loginpress_sanitize_select',

				)
			);

			$wp_customize->add_control(
				'loginpress_customization[login_back_text_decoration]',
				array(
					'settings' => 'loginpress_customization[login_back_text_decoration]',
					'label'    => __( '"Back to" Text Decoration:', 'loginpress' ),
					'section'  => 'section_footer',
					'priority' => 50,
					'type'     => 'select',
					'choices'  => array(
						'none'         => 'none',
						'overline'     => 'overline',
						'line-through' => 'line-through',
						'underline'    => 'underline',
					),
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[login_back_color]',
				array(
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'sanitize_text_field', // validates 3 or 6 digit HTML hex color code.
				)
			);

			$wp_customize->add_control(
				new LoginPress_Color_Picker_Alpha(
					$wp_customize,
					'loginpress_customization[login_back_color]',
					array(
						'label'       => __( '"Back to" Text Color:', 'loginpress' ),
						'section'     => 'section_footer',
						'priority'    => 55,
						'settings'    => 'loginpress_customization[login_back_color]',
						'input_attrs' => array(
							'name'               => 'loginpress_customization[login_back_color]',
							'data-alpha-enabled' => 'true',
						),
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[login_back_color_hover]',
				array(
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'sanitize_text_field', // validates 3 or 6 digit HTML hex color code.
				)
			);

			$wp_customize->add_control(
				new LoginPress_Color_Picker_Alpha(
					$wp_customize,
					'loginpress_customization[login_back_color_hover]',
					array(
						'label'       => __( '"Back to" Text Hover Color:', 'loginpress' ),
						'section'     => 'section_footer',
						'priority'    => 60,
						'settings'    => 'loginpress_customization[login_back_color_hover]',
						'input_attrs' => array(
							'name'               => 'loginpress_customization[login_back_color_hover]',
							'data-alpha-enabled' => 'true',
						),
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[login_back_font_size]',
				array(
					'default'           => '13',
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'absint',
				)
			);
			$wp_customize->add_setting(
				'loginpress_customization[loginpress_show_love]',
				array(
					'default'           => true,
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'loginpress_sanitize_checkbox',
				)
			);

			/**
			 * [ Change login_back_font_size Input fields with LoginPress_Range_Control ]
			 *
			 * @since 1.0.1
			 * @version 1.0.23
			 */
			$wp_customize->add_control(
				new LoginPress_Range_Control(
					$wp_customize,
					'loginpress_customization[login_back_font_size]',
					array(
						'type'        => 'loginpress-range',
						'label'       => __( '"Back to" Text Font Size:', 'loginpress' ),
						'section'     => 'section_footer',
						'settings'    => 'loginpress_customization[login_back_font_size]',
						'default'     => '13',
						'priority'    => 65,
						'input_attrs' => array(
							'min'    => 0,
							'max'    => 100,
							'step'   => 1,
							'suffix' => 'px',
						),
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[login_back_bg_color]',
				array(
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'sanitize_text_field', // validates 3 or 6 digit HTML hex color code.
				)
			);

			$wp_customize->add_control(
				new LoginPress_Color_Picker_Alpha(
					$wp_customize,
					'loginpress_customization[login_back_bg_color]',
					array(
						'label'       => __( '"Back to" Background Color:', 'loginpress' ),
						'section'     => 'section_footer',
						'priority'    => 70,
						'settings'    => 'loginpress_customization[login_back_bg_color]',
						'input_attrs' => array(
							'name'               => 'loginpress_customization[login_back_bg_color]',
							'data-alpha-enabled' => 'true',
						),
					)
				)
			);

			$this->loginpress_hr_setting( $wp_customize, $close_control, 'section_footer', 1, 71 );

			$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_footer', 5, 72 );

			/**
			 * [Enable / Disable Footer Text with LoginPress_Radio_Control]
			 *
			 * @since 1.1.3
			 */
			$wp_customize->add_setting(
				'loginpress_customization[login_copy_right_display]',
				array(
					'default'           => false,
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'loginpress_sanitize_checkbox',
				)
			);
			$wp_customize->add_control(
				new LoginPress_Radio_Control(
					$wp_customize,
					'loginpress_customization[login_copy_right_display]',
					array(
						'settings' => 'loginpress_customization[login_copy_right_display]',
						'section'  => 'section_footer',
						'priority' => 73,
						'type'     => 'ios', // light, ios, flat.
						'label'    => __( 'Enable Copyright Note:', 'loginpress' ),
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[copyright_background_color]',
				array(
					'default'           => '#efefef',
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'sanitize_text_field',
				)
			);

			$wp_customize->add_control(
				new LoginPress_Color_Picker_Alpha(
					$wp_customize,
					'loginpress_customization[copyright_background_color]',
					array(
						'label'       => __( '"Copyright" Background Color:', 'loginpress' ),
						'section'     => 'section_footer',
						'priority'    => 74,
						'settings'    => 'loginpress_customization[copyright_background_color]',
						'input_attrs' => array(
							'name'               => 'loginpress_customization[copyright_background_color]',
							'data-alpha-enabled' => 'true',
						),
					)
				)
			);

			// Form Footer Text Color.
			$wp_customize->add_setting(
				'loginpress_customization[copyright_text_color]',
				array(
					'default'           => '#000000',
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'sanitize_text_field',
				)
			);

			$wp_customize->add_control(
				new LoginPress_Color_Picker_Alpha(
					$wp_customize,
					'loginpress_customization[copyright_text_color]',
					array(
						'label'       => __( '"Copyright" Text Color:', 'loginpress' ),
						'section'     => 'section_footer',
						'priority'    => 75,
						'settings'    => 'loginpress_customization[copyright_text_color]',
						'input_attrs' => array(
							'name'               => 'loginpress_customization[copyright_text_color]',
							'data-alpha-enabled' => 'true',
						),
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[login_footer_copy_right]',
				array(
					'default'           => sprintf(
						// translators: Rights Reserved.
						__( '© %1$s %2$s, All Rights Reserved.', 'loginpress' ),
						date( 'Y' ), // phpcs:ignore
						get_bloginfo( 'name' )
					),
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'wp_kses_post',
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[login_footer_copy_right]',
				array(
					'default'           => sprintf(
						// translators: Rights Reserved.
						__( '© %1$s %2$s, All Rights Reserved.', 'loginpress' ),
						'$YEAR$',
						get_bloginfo( 'name' )
					),
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'wp_kses_post',
				)
			);
			$wp_customize->add_control(
				'loginpress_customization[login_footer_copy_right]',
				array(
					'label'       => __( 'Copyright Note:', 'loginpress' ),
					'description' => sprintf(
						// translators: Copyright Note.
						__( '%1$s will be replaced with the current year.', 'loginpress' ),
						'<code>$YEAR$</code>'
					),
					'type'        => 'textarea',
					'section'     => 'section_footer',
					'priority'    => 77,
					'settings'    => 'loginpress_customization[login_footer_copy_right]',
				)
			);

			/**
			 * [Enable / Disable Footer Text with LoginPress_Radio_Control]
			 *
			 * @since 1.0.1
			 * @version 1.0.23
			 */
			$wp_customize->add_control(
				new LoginPress_Radio_Control(
					$wp_customize,
					'loginpress_customization[loginpress_show_love]',
					array(
						'settings' => 'loginpress_customization[loginpress_show_love]',
						'section'  => 'section_footer',
						'priority' => 80,
						'type'     => 'ios', // light, ios, flat.
						'label'    => __( 'Show some Love. Please help others learn about this free plugin by placing small link in footer. Thank you very much!', 'loginpress' ),
					)
				)
			);

			/**
			 * [Love position on footer.]
			 *
			 * @since 1.1.3
			 */
			$wp_customize->add_setting(
				'loginpress_customization[show_love_position]',
				array(
					'default'    => 'right',
					'type'       => 'option',
					'capability' => 'manage_options',
					'transport'  => 'postMessage',
				)
			);

			$wp_customize->add_control(
				'loginpress_customization[show_love_position]',
				array(
					'label'    => __( 'Love Position:', 'loginpress' ),
					'section'  => 'section_footer',
					'priority' => 85,
					'settings' => 'loginpress_customization[show_love_position]',
					'type'     => 'radio',
					'choices'  => array(
						'left'  => __( 'Left', 'loginpress' ),
						'right' => __( 'Right', 'loginpress' ),
					),
				)
			);
			$this->loginpress_hr_setting( $wp_customize, $close_control, 'section_footer', 2, 90 );
		}

		/**
		 * Setup custom CSS/JS section.
		 *
		 * @param WP_Customize_Manager $wp_customize The WordPress Customize object.
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		private function setup_custom_css_js_section( $wp_customize ) {
			// Include required variables.
			include LOGINPRESS_ROOT_PATH . 'include/customizer-strings.php';

			$wp_customize->add_section(
				'loginpress_custom_css_js',
				array(
					'title'       => __( 'Custom CSS/JS', 'loginpress' ),
					'description' => '',
					'priority'    => 50,
					'panel'       => 'loginpress_panel',
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[loginpress_custom_css]',
				array(
					'type'       => 'option',
					'capability' => 'manage_options',
					'transport'  => 'postMessage',
				)
			);

			$wp_customize->add_control(
				'loginpress_customization[loginpress_custom_css]',
				array(
					'label'       => __( 'Customize CSS:', 'loginpress' ),
					'type'        => 'textarea',
					'section'     => 'loginpress_custom_css_js',
					'priority'    => 5,
					'settings'    => 'loginpress_customization[loginpress_custom_css]',
					'description' => sprintf(
						// translators: Customize CSS.
						__( 'Custom CSS does not make effect live. For preview please save the setting and visit %1$s login%2$s page or after save refresh the customizer.', 'loginpress' ),
						'<a href="' . wp_login_url() . '"title="Login" target="_blank">',
						'</a>'
					),
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[loginpress_custom_js]',
				array(
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'wp_strip_all_tags',
				)
			);

			$wp_customize->add_control(
				'loginpress_customization[loginpress_custom_js]',
				array(
					'label'       => __( 'Customize JS:', 'loginpress' ),
					'type'        => 'textarea',
					'section'     => 'loginpress_custom_css_js',
					'priority'    => 10,
					'settings'    => 'loginpress_customization[loginpress_custom_js]',
					'description' => sprintf(
						// translators: Customize JS.
						__( 'Custom JS does not make effect live. For preview please save the setting and visit %1$s login%2$s page or after save refresh the customizer.', 'loginpress' ),
						'<a href="' . wp_login_url() . '"title="Login" target="_blank">',
						'</a>'
					),
				)
			);
		}
	}
}
