<?php
/**
 * LoginPress Customizer Core Trait.
 *
 * Purpose:
 * Core bootstrapping for the Customizer integration.
 * Registers all the settings in the loginpress customizer.
 *
 * Contains methods moved from `class-loginpress-customizer.php`:
 *
 * @package   LoginPress
 * @subpackage Traits\Customizer
 * @since     6.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'LoginPress_Customizer_Core' ) ) {
	/**
	 * LoginPress Customizer Core Trait.
	 *
	 * Handles core customizer functionality for LoginPress.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\Customizer
	 * @since     6.1.0
	 */
	trait LoginPress_Customizer_Core {

		/**
		 * Register plugin settings Panel in WP Customizer.
		 *
		 * @param WP_Customize_Manager $wp_customize The WordPress Customize object.
		 *
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		public function customize_login_panel( $wp_customize ) {

			$this->include_required_files();
			$this->setup_customizer_panel( $wp_customize );
			$this->setup_presets_section( $wp_customize );
			$this->setup_logo_section( $wp_customize );
			$this->setup_background_section( $wp_customize );
			$this->setup_form_section( $wp_customize );
			$this->setup_forget_form_section( $wp_customize );
			$this->setup_button_section( $wp_customize );
			$this->setup_error_section( $wp_customize );
			$this->setup_welcome_section( $wp_customize );
			$this->setup_footer_section( $wp_customize );
			$this->setup_custom_css_js_section( $wp_customize );
			if ( ! has_action( 'loginpress_pro_add_template' ) ) :
				include LOGINPRESS_ROOT_PATH . 'classes/customizer/loginpress-promo.php';
			endif;
		}

		/**
		 * Include required files for customizer.
		 *
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		private function include_required_files() {
			include LOGINPRESS_ROOT_PATH . 'classes/customizer/class-loginpress-presets.php';

			include LOGINPRESS_ROOT_PATH . 'classes/customizer/controls/class-loginpress-background-gallery-control.php';

			include LOGINPRESS_ROOT_PATH . 'classes/customizer/controls/class-loginpress-range-control.php';

			include LOGINPRESS_ROOT_PATH . 'classes/customizer/controls/class-loginpress-group-control.php';

			include LOGINPRESS_ROOT_PATH . 'classes/customizer/controls/class-loginpress-radio-control.php';

			include LOGINPRESS_ROOT_PATH . 'classes/customizer/controls/class-loginpress-misc-control.php';

			include LOGINPRESS_ROOT_PATH . 'classes/customizer/controls/class-loginpress-color-picker-alpha.php';

			include LOGINPRESS_ROOT_PATH . 'include/customizer-validation.php';

			include LOGINPRESS_ROOT_PATH . 'classes/customizer/controls/class-loginpress-spacing-control.php'; // Adjust path as necessary.
		}

		/**
		 * LoginPress Group Setting.
		 *
		 * @param WP_Customize_Manager $wp_customize The WordPress Customize object.
		 * @param array<string>        $group_control Group control array.
		 * @param array<string>        $group_label   Group label array.
		 * @param array<string>        $group_info    Group info array.
		 * @param string               $section       Section name.
		 * @param int                  $index         Index of the group.
		 * @param int                  $priority      Priority of the control.
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		private function loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, $section, $index, $priority ) {
			$wp_customize->add_setting(
				$group_control[ $index ],
				array(
					'default'           => '',
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'sanitize_text_field',
				)
			);

			$wp_customize->add_control(
				new LoginPress_Group_Control(
					$wp_customize,
					$group_control[ $index ],
					array(
						'label'       => $group_label[ $index ],
						'description' => $group_info[ $index ],
						'section'     => $section,
						'priority'    => $priority,
						'settings'    => $group_control[ $index ],
					)
				)
			);
		}

		/**
		 * LoginPress HR Setting.
		 *
		 * @param WP_Customize_Manager $wp_customize The WordPress Customize object.
		 * @param array<string>        $close_control Close control array.
		 * @param string               $section      Section name.
		 * @param int                  $index        Index of the control.
		 * @param int                  $priority     Priority of the control.
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		private function loginpress_hr_setting( $wp_customize, $close_control, $section, $index, $priority ) {
			$wp_customize->add_setting(
				$close_control[ $index ],
				array(
					'default'           => '',
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'sanitize_text_field',
				)
			);

			$wp_customize->add_control(
				new LoginPress_HR_Control(
					$wp_customize,
					$close_control[ $index ],
					array(
						'section'  => $section,
						'priority' => $priority,
						'settings' => $close_control[ $index ],
					)
				)
			);
		}

		/**
		 * LoginPress Range Setting.
		 *
		 * @param WP_Customize_Manager $wp_customize The WordPress Customize object.
		 * @param array<string>        $range_control Range control array.
		 * @param array<string>        $range_default Range default array.
		 * @param array<string>        $range_label   Range label array.
		 * @param array<array>         $range_attrs   Range attributes array.
		 * @param array<string>        $range_unit    Range unit array.
		 * @param string               $section       Section name.
		 * @param int                  $index         Index of the range.
		 * @param int                  $priority      Priority of the control.
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		private function loginpress_range_setting( $wp_customize, $range_control, $range_default, $range_label, $range_attrs, $range_unit, $section, $index, $priority ) {
			$wp_customize->add_setting(
				"loginpress_customization[{$range_control[$index]}]",
				array(
					'default'           => $range_default[ $index ],
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'absint',
				)
			);

			$wp_customize->add_control(
				new LoginPress_Range_Control(
					$wp_customize,
					"loginpress_customization[{$range_control[$index]}]",
					array(
						'label'       => $range_label[ $index ],
						'section'     => $section,
						'priority'    => $priority,
						'settings'    => "loginpress_customization[{$range_control[$index]}]",
						'input_attrs' => $range_attrs[ $index ],
					)
				)
			);
		}

		/**
		 * LoginPress Color Setting.
		 *
		 * @param WP_Customize_Manager $wp_customize The WordPress Customize object.
		 * @param array<string>        $color_control Color control array.
		 * @param array<string>        $color_label   Color label array.
		 * @param string               $section       Section name.
		 * @param int                  $index         Index of the color.
		 * @param int                  $priority      Priority of the control.
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		private function loginpress_color_setting( $wp_customize, $color_control, $color_label, $section, $index, $priority ) {
			$wp_customize->add_setting(
				"loginpress_customization[{$color_control[$index]}]",
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
					"loginpress_customization[{$color_control[$index]}]",
					array(
						'label'       => $color_label[ $index ],
						'section'     => $section,
						'priority'    => $priority,
						'settings'    => "loginpress_customization[{$color_control[$index]}]",
						'input_attrs' => array(
							'name'               => "loginpress_customization[{$color_control[$index]}]",
							'data-alpha-enabled' => 'true',
						),
					)
				)
			);
		}
	}
}
