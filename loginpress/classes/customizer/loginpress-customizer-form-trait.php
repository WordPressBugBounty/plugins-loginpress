<?php
/**
 * LoginPress Customizer Form Trait.
 *
 * Handles customizer form functionality for LoginPress.
 *
 * @package   LoginPress
 * @subpackage Traits\Customizer
 * @since     6.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! trait_exists( 'LoginPress_Customizer_Form' ) ) {
	/**
	 * LoginPress Customizer Form Trait.
	 *
	 * Handles customizer form functionality for LoginPress.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\Customizer
	 * @since     6.1.0
	 */
	trait LoginPress_Customizer_Form {

		/**
		 * Setup form section.
		 *
		 * @param WP_Customize_Manager $wp_customize The WordPress Customize object.
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		private function setup_form_section( $wp_customize ) {
			// Include required variables.
			include LOGINPRESS_ROOT_PATH . 'include/customizer-strings.php';

			// Section for Form Beauty.
			$wp_customize->add_section(
				'section_form',
				array(
					'title'       => __( 'Customize Login Form', 'loginpress' ),
					'description' => '',
					'priority'    => 15,
					'panel'       => 'loginpress_panel',
				)
			);

			$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_form', 2, 4 );

			/**
			 * Enable / Disable Form Background Image with LoginPress_Radio_Control.
			 *
			 * @since 1.1.3
			 */

			$wp_customize->add_setting(
				'loginpress_customization[setting_form_display_bg]',
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
					'loginpress_customization[setting_form_display_bg]',
					array(
						'settings' => 'loginpress_customization[setting_form_display_bg]',
						'label'    => __( 'Enable Form Transparency:', 'loginpress' ),
						'section'  => 'section_form',
						'priority' => 5,
						'type'     => 'ios',
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[setting_form_background]',
				array(
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'loginpress_sanitize_image',
				)
			);

			$wp_customize->add_control(
				new WP_Customize_Image_Control(
					$wp_customize,
					'loginpress_customization[setting_form_background]',
					array(
						'label'    => __( 'Form Background Image:', 'loginpress' ),
						'section'  => 'section_form',
						'priority' => 6,
						'settings' => 'loginpress_customization[setting_form_background]',
					)
				)
			);

			$wp_customize->register_control_type( LoginPress_Color_Picker_Alpha::class );

			$this->loginpress_color_setting( $wp_customize, $form_color_control, $form_color_label, 'section_form', 0, 7 );

			$this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 0, 15 );
			$this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 1, 20 );
			$this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 2, 25 );
			$this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 3, 30 );
			$this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 4, 35 );
			// Add settings for padding and margin.
			$wp_customize->add_setting(
				'loginpress_customization[padding]',
				array(
					'default'           => array(
						'top'    => 0,
						'left'   => 0,
						'right'  => 0,
						'bottom' => 0,
						'unit'   => 'px',
						'lock'   => 0,
					),
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'sanitize_text_field', // Update with appropriate sanitization function.
				)
			);

			$wp_customize->add_control(
				new LoginPress_Spacing_Control(
					$wp_customize,
					'loginpress_customization-customize_form_padding_controls',
					array(
						'label'            => __( 'Padding', 'loginpress' ),
						'description'      => __( 'Set the padding values.', 'loginpress' ),
						'section'          => 'section_form',
						'settings'         => 'loginpress_customization[padding]',
						'is_margin'        => false, // For padding.
						'priority'         => 40,
						'loginpresstarget' => 'customize-control-loginpress_customization-customize_form_padding',
					)
				)
			);
			$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_button', 0, 44 );
			$form_padding = 0;
			while ( $form_padding < 2 ) :

				$wp_customize->add_setting(
					"loginpress_customization[{$form_control[$form_padding]}]",
					array(
						'default'           => $form_default[ $form_padding ],
						'type'              => 'option',
						'capability'        => 'manage_options',
						'transport'         => 'postMessage',
						'sanitize_callback' => $form_sanitization[ $form_padding ],
					)
				);

				$wp_customize->add_control(
					"loginpress_customization[{$form_control[$form_padding]}]",
					array(
						'label'    => $form_label[ $form_padding ],
						'section'  => 'section_form',
						'priority' => 40,
						'settings' => "loginpress_customization[{$form_control[$form_padding]}]",
					)
				);

				++$form_padding;
			endwhile;

			$this->loginpress_hr_setting( $wp_customize, $close_control, 'section_form', 3, 41 );

			$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_form', 0, 45 );
			// Add settings for margin.
			$wp_customize->add_setting(
				'loginpress_customization[margin]',
				array(
					'default'           => array(
						'top'    => 0,
						'left'   => 0,
						'right'  => 0,
						'bottom' => 0,
						'unit'   => 'px',
						'lock'   => 0,
					),
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'sanitize_text_field', // Update with appropriate sanitization function.
				)
			);

			// Add control for margin.
			$wp_customize->add_control(
				new LoginPress_Spacing_Control(
					$wp_customize,
					'loginpress_customization-customize_form_margin',
					array(
						'label'            => __( 'Margin', 'loginpress' ),
						'description'      => __( 'Set the margin values.', 'loginpress' ),
						'section'          => 'section_form',
						'settings'         => 'loginpress_customization[margin]',
						'is_margin'        => true, // For margin.
						'priority'         => 49, // Adjust priority as needed.
						'loginpresstarget' => 'customize-control-loginpress_customization-textfield_margin',
					)
				)
			);

			$this->loginpress_color_setting( $wp_customize, $form_color_control, $form_color_label, 'section_form', 1, 50 );
			$this->loginpress_color_setting( $wp_customize, $form_color_control, $form_color_label, 'section_form', 2, 55 );

			$this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 5, 60 );

			$input_padding = 2;
			while ( $input_padding < 3 ) :

				$wp_customize->add_setting(
					"loginpress_customization[{$form_control[$input_padding]}]",
					array(
						'default'           => $form_default[ $input_padding ],
						'type'              => 'option',
						'capability'        => 'manage_options',
						'transport'         => 'postMessage',
						'sanitize_callback' => $form_sanitization[ $input_padding ],
					)
				);

				$wp_customize->add_control(
					"loginpress_customization[{$form_control[$input_padding]}]",
					array(
						'label'    => $form_label[ $input_padding ],
						'section'  => 'section_form',
						'priority' => 85,
						'settings' => "loginpress_customization[{$form_control[$input_padding]}]",
					)
				);

				++$input_padding;
			endwhile;

			$this->loginpress_hr_setting( $wp_customize, $close_control, 'section_form', 4, 86 );
			$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_form', 1, 90 );

			$this->loginpress_color_setting( $wp_customize, $form_color_control, $form_color_label, 'section_form', 3, 95 );
			$this->loginpress_color_setting( $wp_customize, $form_color_control, $form_color_label, 'section_form', 4, 100 );

			// customize_form_label.
			$this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 9, 105 );
			// remember_me_font_size.
			$this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 10, 110 );
			$this->loginpress_hr_setting( $wp_customize, $close_control, 'section_form', 5, 111 );
		}

		/**
		 * Setup forget form section.
		 *
		 * @param WP_Customize_Manager $wp_customize The WordPress Customize object.
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		private function setup_forget_form_section( $wp_customize ) {
			// Include required variables.
			include LOGINPRESS_ROOT_PATH . 'include/customizer-strings.php';

			// Section for Forget Form.
			$wp_customize->add_section(
				'section_forget_form',
				array(
					'title'       => __( 'Customize Forget Form', 'loginpress' ),
					'description' => '',
					'priority'    => 20,
					'panel'       => 'loginpress_panel',
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[forget_form_background]',
				array(
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'loginpress_sanitize_image',
				)
			);

			$wp_customize->add_control(
				new WP_Customize_Image_Control(
					$wp_customize,
					'loginpress_customization[forget_form_background]',
					array(
						'label'    => __( 'Forget Form Background Image:', 'loginpress' ),
						'section'  => 'section_forget_form',
						'priority' => 5,
						'settings' => 'loginpress_customization[forget_form_background]',
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[forget_form_background_color]',
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
					'loginpress_customization[forget_form_background_color]',
					array(
						'label'       => __( 'Forget Form Background Color:', 'loginpress' ),
						'section'     => 'section_forget_form',
						'priority'    => 10,
						'settings'    => 'loginpress_customization[forget_form_background_color]',
						'input_attrs' => array(
							'name'               => 'loginpress_customization[forget_form_background_color]',
							'data-alpha-enabled' => 'true',
						),
					)
				)
			);
		}

		/**
		 * Setup button section.
		 *
		 * @param WP_Customize_Manager $wp_customize The WordPress Customize object.
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		private function setup_button_section( $wp_customize ) {
			// Include required variables.
			include LOGINPRESS_ROOT_PATH . 'include/customizer-strings.php';

			// Section for Button Style.
			$wp_customize->add_section(
				'section_button',
				array(
					'title'       => __( 'Button Beauty', 'loginpress' ),
					'description' => '',
					'priority'    => 25,
					'panel'       => 'loginpress_panel',
				)
			);

			$this->loginpress_color_setting( $wp_customize, $button_control, $button_label, 'section_button', 0, 5 );
			$this->loginpress_color_setting( $wp_customize, $button_control, $button_label, 'section_button', 1, 10 );
			$this->loginpress_color_setting( $wp_customize, $button_control, $button_label, 'section_button', 2, 15 );
			$this->loginpress_color_setting( $wp_customize, $button_control, $button_label, 'section_button', 3, 20 );
			$this->loginpress_color_setting( $wp_customize, $button_control, $button_label, 'section_button', 4, 25 );
			$this->loginpress_color_setting( $wp_customize, $button_control, $button_label, 'section_button', 5, 30 );
			$this->loginpress_color_setting( $wp_customize, $button_control, $button_label, 'section_button', 6, 35 );

			$this->loginpress_range_setting( $wp_customize, $button_range_control, $button_range_default, $button_range_label, $button_range_attrs, $button_range_unit, 'section_button', 0, 35 );
			$this->loginpress_range_setting( $wp_customize, $button_range_control, $button_range_default, $button_range_label, $button_range_attrs, $button_range_unit, 'section_button', 1, 40 );
			$this->loginpress_range_setting( $wp_customize, $button_range_control, $button_range_default, $button_range_label, $button_range_attrs, $button_range_unit, 'section_button', 2, 45 );
			$this->loginpress_range_setting( $wp_customize, $button_range_control, $button_range_default, $button_range_label, $button_range_attrs, $button_range_unit, 'section_button', 3, 50 );
			$this->loginpress_range_setting( $wp_customize, $button_range_control, $button_range_default, $button_range_label, $button_range_attrs, $button_range_unit, 'section_button', 4, 55 );
			$this->loginpress_range_setting( $wp_customize, $button_range_control, $button_range_default, $button_range_label, $button_range_attrs, $button_range_unit, 'section_button', 5, 60 );
			$this->loginpress_range_setting( $wp_customize, $button_range_control, $button_range_default, $button_range_label, $button_range_attrs, $button_range_unit, 'section_button', 6, 65 );
		}
	}
}
