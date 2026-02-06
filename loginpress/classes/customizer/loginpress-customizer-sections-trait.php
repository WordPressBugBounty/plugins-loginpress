<?php
/**
 * LoginPress Customizer Sections Trait
 *
 * Handles customizer sections functionality for LoginPress.
 *
 * @package   LoginPress
 * @subpackage Traits\Customizer
 * @since     6.0.1
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'LoginPress_Customizer_Sections' ) ) {
	/**
	 * LoginPress Customizer Sections Trait.
	 *
	 * Handles customizer sections functionality for LoginPress.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\Customizer
	 * @since     6.0.1
	 */
	trait LoginPress_Customizer_Sections {

		/**
		 * Setup customizer panel.
		 *
		 * @param WP_Customize_Manager $wp_customize The WordPress Customize object.
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		private function setup_customizer_panel( $wp_customize ) {
			// Panel for the LoginPress.
			$wp_customize->add_panel(
				'loginpress_panel',
				array(
					'title'       => __( 'LoginPress', 'loginpress' ),
					'description' => __( 'Customize Your WordPress Login Page with LoginPress :)', 'loginpress' ),
					'priority'    => 30,
				)
			);
		}

		/**
		 * Setup presets section.
		 *
		 * @param WP_Customize_Manager $wp_customize The WordPress Customize object.
		 * @since 1.0.9
		 * @version 3.0.3
		 * @return void
		 */
		private function setup_presets_section( $wp_customize ) {
			/**
			 * Section for Presets.
			 *
			 * @since   1.0.9
			 * @version 3.0.3
			 */
			$wp_customize->add_section(
				'customize_presets',
				array(
					'title'       => __( 'Themes', 'loginpress' ),
					'description' => __( 'Choose Theme', 'loginpress' ),
					'priority'    => 1,
					'panel'       => 'loginpress_panel',
				)
			);

			$loginpress_default_theme = true === $this->loginpress_preset && ( empty( $this->loginpress_key ) && empty( $this->loginpress_setting ) ) ? 'minimalist' : 'default1';

			$wp_customize->add_setting(
				'customize_presets_settings',
				array(
					'default'    => $loginpress_default_theme,
					'type'       => 'option',
					'capability' => 'manage_options',
				)
			);

			$loginpress_free_templates = array();
			$loginpress_theme_name     = array(
				'',
				'',
				__( 'Company', 'loginpress' ),
				__( 'Persona', 'loginpress' ),
				__( 'Corporate', 'loginpress' ),
				__( 'Corporate', 'loginpress' ),
				__( 'Startup', 'loginpress' ),
				__( 'Wedding', 'loginpress' ),
				__( 'Wedding #2', 'loginpress' ),
				__( 'Company', 'loginpress' ),
				__( 'Bikers', 'loginpress' ),
				__( 'Fitness', 'loginpress' ),
				__( 'Shopping', 'loginpress' ),
				__( 'Writers', 'loginpress' ),
				__( 'Persona', 'loginpress' ),
				__( 'Geek', 'loginpress' ),
				__( 'Innovation', 'loginpress' ),
				__( 'Photographers', 'loginpress' ),
				__( 'Animated Wapo', 'loginpress' ),
				__( 'Animated Wapo 2', 'loginpress' ),
			);

			// 1st template that is default.
			$loginpress_free_templates['default1'] = array(
				'img'       => esc_url( apply_filters( 'loginpress_default_bg', plugins_url( 'img/minimalist.jpg', LOGINPRESS_PLUGIN_BASENAME ) ) ),
				'thumbnail' => esc_url( apply_filters( 'loginpress_default_bg', plugins_url( 'img/thumbnail/default-1.png', LOGINPRESS_ROOT_FILE ) ) ),
				'id'        => 'default1',
				'name'      => 'Default',
			);

			// 1st template that is default.
				$loginpress_free_templates['minimalist'] = array(
					'img'       => esc_url( apply_filters( 'loginpress_default_bg', plugins_url( 'img/bg-default.jpg', LOGINPRESS_PLUGIN_BASENAME ) ) ),
					'thumbnail' => esc_url( apply_filters( 'loginpress_default_bg', plugins_url( 'img/thumbnail/free-minimalist.png', LOGINPRESS_ROOT_FILE ) ) ),
					'id'        => 'minimalist',
					'name'      => 'Minimalist',
				);

				// Loop through the templates.
				$template_count = 2;
				while ( $template_count <= 18 ) :

					$loginpress_free_templates[ "default{$template_count}" ] = array(
						'thumbnail' => plugins_url( "img/thumbnail/default-{$template_count}.png", LOGINPRESS_ROOT_FILE ),
						'id'        => "default{$template_count}",
						'name'      => $loginpress_theme_name[ $template_count ],
						'pro'       => 'yes',
					);
					++$template_count;
			endwhile;

				// 18th template for custom design.
				$loginpress_free_templates['default19'] = array(
					'img'       => plugins_url( 'loginpress/img/bg17.jpg', LOGINPRESS_ROOT_PATH ),
					'thumbnail' => plugins_url( 'loginpress/img/thumbnail/custom-design.png', LOGINPRESS_ROOT_PATH ),
					'id'        => 'default19',
					'name'      => __( 'Custom Design', 'loginpress' ),
					'link'      => 'yes',
				);
				$loginpress_templates                   = apply_filters( 'loginpress_pro_add_template', $loginpress_free_templates );

				$wp_customize->add_control(
					new LoginPress_Presets(
						$wp_customize,
						'customize_presets_settings',
						array(
							'section' => 'customize_presets',
							'choices' => $loginpress_templates,
						)
					)
				);
		}

		/**
		 * Setup logo section.
		 *
		 * @param WP_Customize_Manager $wp_customize The WordPress Customize object.
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		private function setup_logo_section( $wp_customize ) {
			// Include required variables.
			include LOGINPRESS_ROOT_PATH . 'include/customizer-strings.php';

			// Section for Login Logo.
			$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'customize_logo_section', 8, 4 );
			$wp_customize->add_section(
				'customize_logo_section',
				array(
					'title'       => __( 'Logo', 'loginpress' ),
					'description' => __( 'Customize Your Logo Section', 'loginpress' ),
					'priority'    => 5,
					'panel'       => 'loginpress_panel',
				)
			);

			/**
			* Enable / Disable Logo Image with LoginPress_Radio_Control.
			*
			* @since 1.1.3
			*/

			$wp_customize->add_setting(
				'loginpress_customization[setting_logo_display]',
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
					'loginpress_customization[setting_logo_display]',
					array(
						'settings' => 'loginpress_customization[setting_logo_display]',
						'label'    => __( 'Disable Logo:', 'loginpress' ),
						'section'  => 'customize_logo_section',
						'priority' => 4,
						'type'     => 'ios', // light, ios, flat.
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[setting_logo]',
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
					'loginpress_customization[setting_logo]',
					array(
						'label'    => __( 'Logo Image:', 'loginpress' ),
						'section'  => 'customize_logo_section',
						'priority' => 5,
						'settings' => 'loginpress_customization[setting_logo]',
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[login_favicon]',
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
					'loginpress_customization[login_favicon]',
					array(
						'label'       => __( 'Login Favicon:', 'loginpress' ),
						'section'     => 'customize_logo_section',
						'description' => __( 'Add a custom favicon specific for your login page', 'loginpress' ),
						'priority'    => 30,
						'settings'    => 'loginpress_customization[login_favicon]',
					)
				)
			);

			/**
			 * Change CSS Properties Input fields with LoginPress_Range_Control.
			 *
			 * @since 1.0.1
			 * @version 3.0.0
			 */

			$this->loginpress_range_setting( $wp_customize, $logo_range_control, $logo_range_default, $logo_range_label, $logo_range_attrs, $logo_range_unit, 'customize_logo_section', 0, 10 );
			$this->loginpress_range_setting( $wp_customize, $logo_range_control, $logo_range_default, $logo_range_label, $logo_range_attrs, $logo_range_unit, 'customize_logo_section', 1, 15 );
			$this->loginpress_range_setting( $wp_customize, $logo_range_control, $logo_range_default, $logo_range_label, $logo_range_attrs, $logo_range_unit, 'customize_logo_section', 2, 20 );

			/**
			 * Login Page meta and form logo options.
			 *
			 * @version 3.0.0
			 */
			if ( version_compare( $GLOBALS['wp_version'], '5.2', '<' ) ) {
				$loginpress_logo_title = __( 'Logo Hover Title:', 'loginpress' );
			} else {
				$loginpress_logo_title = __( 'Logo Title:', 'loginpress' );
			}
			$logo_control      = array( 'customize_logo_hover', 'customize_logo_hover_title', 'customize_login_page_title' );
			$logo_default      = array( '', '', '' );
			$logo_label        = array( __( 'Logo URL:', 'loginpress' ), $loginpress_logo_title, __( 'Login Page Title:', 'loginpress' ) );
			$logo_sanitization = array( 'esc_url_raw', 'wp_strip_all_tags', 'wp_strip_all_tags' );
			$logo_desc         = array( '', '', __( 'Login page title that is shown on WordPress login page.', 'loginpress' ) );

			$logo = 0;
			while ( $logo < 3 ) :

				$wp_customize->add_setting(
					"loginpress_customization[{$logo_control[$logo]}]",
					array(
						'default'           => $logo_default[ $logo ],
						'type'              => 'option',
						'capability'        => 'manage_options',
						'transport'         => 'postMessage',
						'sanitize_callback' => $logo_sanitization[ $logo ],
					)
				);

				$wp_customize->add_control(
					"loginpress_customization[{$logo_control[$logo]}]",
					array(
						'label'       => $logo_label[ $logo ],
						'section'     => 'customize_logo_section',
						'priority'    => 25,
						'settings'    => "loginpress_customization[{$logo_control[$logo]}]",
						'description' => $logo_desc[ $logo ],
					)
				);
				if ( 1 === $logo ) {
					$this->loginpress_hr_setting( $wp_customize, $close_control, 'customize_logo_section', 9, 25 );
					$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'customize_logo_section', 9, 25 );
				}
				++$logo;
			endwhile;
		}
	}
}
