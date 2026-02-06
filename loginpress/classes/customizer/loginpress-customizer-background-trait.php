<?php
/**
 * LoginPress Customizer Background Trait.
 *
 * Handles customizer background functionality for LoginPress.
 *
 * @package   LoginPress
 * @subpackage Traits\Customizer
 * @since     6.1.0
 * @version   6.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'LoginPress_Customizer_Background' ) ) {
	/**
	 * LoginPress Customizer Background Trait.
	 *
	 * Handles customizer background functionality for LoginPress.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\Customizer
	 * @since     6.1.0
	 */
	trait LoginPress_Customizer_Background {

		/**
		 * Setup background section.
		 *
		 * @param WP_Customize_Manager $wp_customize The WordPress Customize object.
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		private function setup_background_section( $wp_customize ) {
			// Include required variables.
			include LOGINPRESS_ROOT_PATH . 'include/customizer-strings.php';

			// Section for Background.
			$wp_customize->add_section(
				'section_background',
				array(
					'title'       => __( 'Background', 'loginpress' ),
					'description' => '',
					'priority'    => 10,
					'panel'       => 'loginpress_panel',
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[setting_background_color]',
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
					'loginpress_customization[setting_background_color]',
					array(
						'label'       => __( 'Background Color:', 'loginpress' ),
						'section'     => 'section_background',
						'priority'    => 5,
						'settings'    => 'loginpress_customization[setting_background_color]',
						'input_attrs' => array(
							'name'               => 'loginpress_customization[setting_background_color]',
							'data-alpha-enabled' => 'true',
						),
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[loginpress_display_bg]',
				array(
					'default'           => true,
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'loginpress_sanitize_checkbox',
				)
			);

			$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_background', 6, 6 );

			/**
			 * Enable / Disable Background Image with LoginPress_Radio_Control.
			 *
			 * @since 1.0.1
			 * @version 1.0.23
			 */
			$wp_customize->add_control(
				new LoginPress_Radio_Control(
					$wp_customize,
					'loginpress_customization[loginpress_display_bg]',
					array(
						'settings' => 'loginpress_customization[loginpress_display_bg]',
						'label'    => __( 'Enable Background Image?', 'loginpress' ),
						'section'  => 'section_background',
						'priority' => 10,
						'type'     => 'ios', // light, ios, flat.
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[setting_background]',
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
					'loginpress_customization[setting_background]',
					array(
						'label'         => __( 'Background Image:', 'loginpress' ),
						'section'       => 'section_background',
						'priority'      => 15,
						'settings'      => 'loginpress_customization[setting_background]',
						'button_labels' => array(
							'select' => __( 'Select Image', 'loginpress' ),
						),
					)
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[mobile_background]',
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
					'loginpress_customization[mobile_background]',
					array(
						'label'         => __( 'Mobile Background Image:', 'loginpress' ),
						'section'       => 'section_background',
						'priority'      => 17,
						'settings'      => 'loginpress_customization[mobile_background]',
						'button_labels' => array(
							'select' => __( 'Select Image', 'loginpress' ),
						),
					)
				)
			);

			/**
			 * Add Background Gallery.
			 *
			 * @since 1.1.0
			 */
			$wp_customize->add_setting(
				'loginpress_customization[gallery_background]',
				array(
					'default'    => plugins_url( 'img/gallery/img-1.jpg', LOGINPRESS_ROOT_FILE ),
					'type'       => 'option',
					'capability' => 'manage_options',
					'transport'  => 'postMessage',
				)
			);

			$loginpress_free_background = array();
			$loginpress_background_name = array(
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
			);

			// Loop through the backgrounds.
			$bg_count = 1;
			while ( $bg_count <= 9 ) :

				$thumbnail                                = plugins_url( "img/gallery/img-{$bg_count}.jpg", LOGINPRESS_ROOT_FILE );
				$loginpress_free_background[ $thumbnail ] = array(
					'thumbnail' => plugins_url( "img/thumbnail/gallery-img-{$bg_count}.jpg", LOGINPRESS_ROOT_FILE ),
					'id'        => $thumbnail,
					'name'      => $loginpress_background_name[ $bg_count ],
				);
				++$bg_count;
			endwhile;

			$loginpress_background = apply_filters( 'loginpress_pro_add_background', $loginpress_free_background );

			$wp_customize->add_control(
				new LoginPress_Background_Gallery_Control(
					$wp_customize,
					'loginpress_customization[gallery_background]',
					array(
						'section' => 'section_background',
						'label'   => __( 'Background Gallery:', 'loginpress' ),
						'choices' => $loginpress_background,
					)
				)
			);

			// version 1.1.21.
			$wp_customize->add_setting(
				'loginpress_customization[background_repeat_radio]',
				array(
					'default'    => 'no-repeat',
					'type'       => 'option',
					'capability' => 'manage_options',
					'transport'  => 'postMessage',
				)
			);

			$wp_customize->add_control(
				'loginpress_customization[background_repeat_radio]',
				array(
					'label'    => __( 'Background Repeat:', 'loginpress' ),
					'section'  => 'section_background',
					'priority' => 20,
					'settings' => 'loginpress_customization[background_repeat_radio]',
					'type'     => 'select',
					'choices'  => array(
						'repeat'    => 'repeat',
						'repeat-x'  => 'repeat-x',
						'repeat-y'  => 'repeat-y',
						'no-repeat' => 'no-repeat',
						'initial'   => 'initial',
						'inherit'   => 'inherit',
					),
				)
			);

			// version 1.1.21.
			$wp_customize->add_setting(
				'loginpress_customization[background_position]',
				array(
					'default'           => 'center',
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'loginpress_sanitize_select',
				)
			);
			$wp_customize->add_control(
				'loginpress_customization[background_position]',
				array(
					'settings' => 'loginpress_customization[background_position]',
					'label'    => __( 'Select Position:', 'loginpress' ),
					'section'  => 'section_background',
					'priority' => 25,
					'type'     => 'select',
					'choices'  => array(
						'left-top'      => 'left top',
						'left-center'   => 'left center',
						'left-bottom'   => 'left bottom',
						'right-top'     => 'right top',
						'right-center'  => 'right center',
						'right-bottom'  => 'right bottom',
						'center-top'    => 'center top',
						'center'        => 'center',
						'center-bottom' => 'center bottom',
					),
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[background_image_size]',
				array(
					'default'           => 'cover',
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'loginpress_sanitize_select',
				)
			);

			$wp_customize->add_control(
				'loginpress_customization[background_image_size]',
				array(
					'label'    => __( 'Background Image Size: ', 'loginpress' ),
					'section'  => 'section_background',
					'priority' => 30,
					'settings' => 'loginpress_customization[background_image_size]',
					'type'     => 'select',
					'choices'  => array(
						'auto'    => 'auto',
						'cover'   => 'cover',
						'contain' => 'contain',
						'initial' => 'initial',
						'inherit' => 'inherit',
					),
				)
			);

			$this->loginpress_hr_setting( $wp_customize, $close_control, 'section_form', 10, 31 );
			$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_background', 10, 31 );

			/**
			 * Enable / Disable Radom Background Images with LoginPress_Radio_Control.
			 *
			 * @since 6.0.0
			 */
			$wp_customize->add_setting(
				'loginpress_customization[lp_random_bg_img_check]',
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
					'loginpress_customization[lp_random_bg_img_check]',
					array(
						'settings' => 'loginpress_customization[lp_random_bg_img_check]',
						'label'    => __( 'Enable Random Background Images?', 'loginpress' ),
						'section'  => 'section_background',
						'priority' => 32,
						'type'     => 'ios', // light, ios, flat.
					)
				)
			);
			$loginpress_default_backgrounds = array();
			for ( $bg_count = 4; $bg_count <= 9; $bg_count++ ) {
				$loginpress_default_backgrounds[] = plugins_url( "img/gallery/img-{$bg_count}.jpg", LOGINPRESS_ROOT_FILE );
			}

			$default_backgrounds_string = implode( ',', $loginpress_default_backgrounds );
			$wp_customize->add_setting(
				'loginpress_customization[lp_random_bg_img_upload]',
				array(
					'type'      => 'option',
					'default'   => $default_backgrounds_string,
					'transport' => 'postMessage',
				)
			);

			$wp_customize->add_control(
				new LoginPress_Random_BG_Gallery_Control(
					$wp_customize,
					'loginpress_customization[lp_random_bg_img_upload]',
					array(
						'label'    => __( 'Random Background Images', 'loginpress' ),
						'section'  => 'section_background',
						'settings' => 'loginpress_customization[lp_random_bg_img_upload]',
						'priority' => 33,
					)
				)
			);

			$this->loginpress_hr_setting( $wp_customize, $close_control, 'section_form', 7, 35 );
			$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_background', 7, 35 );
			/**
			 * Enable / Disable Background Video with LoginPress_Radio_Control.
			 *
			 * @since 1.1.22
			 */
			$wp_customize->add_setting(
				'loginpress_customization[loginpress_display_bg_video]',
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
					'loginpress_customization[loginpress_display_bg_video]',
					array(
						'settings' => 'loginpress_customization[loginpress_display_bg_video]',
						'label'    => __( 'Enable Background Video?', 'loginpress' ),
						'section'  => 'section_background',
						'priority' => 40,
						'type'     => 'ios', // light, ios, flat.
					)
				)
			);

			/**
			 * Background Video Medium.
			 *
			 * @since 3.0.0
			 */
			$wp_customize->add_setting(
				'loginpress_customization[bg_video_medium]',
				array(
					'default'    => 'media',
					'type'       => 'option',
					'capability' => 'manage_options',
					'transport'  => 'postMessage',
				)
			);

			$wp_customize->add_control(
				'loginpress_customization[bg_video_medium]',
				array(
					'label'    => __( 'Medium', 'loginpress' ),
					'section'  => 'section_background',
					'priority' => 41,
					'settings' => 'loginpress_customization[bg_video_medium]',
					'type'     => 'radio',
					'choices'  => array(
						'media'   => __( 'Media', 'loginpress' ),
						'youtube' => __( 'YouTube', 'loginpress' ),
					),
				)
			);

			/**
			 * Launch Background Video feature with WP_Customize_Media_Control.
			 *
			 * @since 1.1.22
			 */
			$wp_customize->add_setting(
				'loginpress_customization[background_video]',
				array(
					'type'       => 'option',
					'capability' => 'manage_options',
					'transport'  => 'postMessage',
				)
			);

			$wp_customize->add_control(
				new WP_Customize_Media_Control(
					$wp_customize,
					'loginpress_customization[background_video]',
					array(
						'label'         => __( 'Background Video:', 'loginpress' ),
						'section'       => 'section_background',
						'mime_type'     => 'video', // Required. Can be image, audio, video, application, text.
						'priority'      => 45,
						'button_labels' => array(
							'select'       => __( 'Select Video', 'loginpress' ),
							'change'       => __( 'Change Video', 'loginpress' ),
							'default'      => __( 'Default', 'loginpress' ),
							'remove'       => __( 'Remove', 'loginpress' ),
							'frame_title'  => __( 'Select File', 'loginpress' ),
							'frame_button' => __( 'Choose File', 'loginpress' ),
						),
					)
				)
			);

			/**
			 * Field settings for the error message.
			 *
			 * @since 3.0.0
			 */
			$wp_customize->add_setting(
				'loginpress_customization[background_video_error]',
				array(
					'sanitize_callback' => 'sanitize_text_field',
				)
			);
			/**
			 * Field for Add a control for the error message.
			 *
			 * @since 3.0.0
			 */
			$wp_customize->add_control(
				new WP_Customize_Control(
					$wp_customize,
					'loginpress_customization[background_video_error]',
					array(
						'label'       => __( 'Error Message:', 'loginpress' ),
						'description' => 'Wrong Selection, Please select MP4, webm or ogg file only',
						'section'     => 'section_background',
						'priority'    => 46, // Place it after the video control.
						'type'        => 'hidden', // Using hidden type to display only the description.
					)
				)
			);

			/**
			 * Field for YouTube video ID.
			 *
			 * @since 3.0.0
			 * @version 6.0.0
			 */
			$wp_customize->add_setting(
				'loginpress_customization[yt_video_id]',
				array(
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'sanitize_text_field',
				)
			);

			$wp_customize->add_control(
				'loginpress_customization[yt_video_id]',
				array(
					'label'       => __( 'ID of the YouTube video', 'loginpress' ),
					// translators: Live preview is not supported.
					'description' => sprintf( __( 'YouTube video ID is correct though the Live Preview is not supported. The video on the %1$slogin page%2$s can be checked, once it is published.', 'loginpress' ), '<a href="' . wp_login_url() . '" target="_blank">', '</a>' ),
					'section'     => 'section_background',
					'priority'    => 46,
					'settings'    => 'loginpress_customization[yt_video_id]',
					'input_attrs' => array(
						'placeholder' => 'GMAwsHomJlE',
					),
				)
			);

			// version 1.1.21.
			$wp_customize->add_setting(
				'loginpress_customization[background_video_object]',
				array(
					'default'           => 'cover',
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'loginpress_sanitize_select',
				)
			);
			$wp_customize->add_control(
				'loginpress_customization[background_video_object]',
				array(
					'settings' => 'loginpress_customization[background_video_object]',
					// translators: Video Size.
					'label'    => __( 'Video Size:', 'loginpress' ),
					'section'  => 'section_background',
					'priority' => 50,
					'type'     => 'select',
					'choices'  => array(
						'fill'       => 'fill',
						'contain'    => 'contain',
						'cover'      => 'cover',
						'scale-down' => 'scale-down',
						'none'       => 'none',
					),
				)
			);

			$wp_customize->add_setting(
				'loginpress_customization[video_obj_position]',
				array(
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'wp_strip_all_tags',
				)
			);

			$wp_customize->add_control(
				'loginpress_customization[video_obj_position]',
				array(
					'label'       => __( 'Object Position:', 'loginpress' ),
					'section'     => 'section_background',
					'priority'    => 55,
					'settings'    => 'loginpress_customization[video_obj_position]',
					'input_attrs' => array(
						'placeholder' => __( '50% 50%', 'loginpress' ),
					),
				)
			);

			/**
			 * Enable / Disable Background Video with LoginPress_Radio_Control.
			 *
			 * @since 1.1.22
			 */
			$wp_customize->add_setting(
				'loginpress_customization[background_video_muted]',
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
					'loginpress_customization[background_video_muted]',
					array(
						'settings' => 'loginpress_customization[background_video_muted]',
						'label'    => __( 'Muted Video?', 'loginpress' ),
						'section'  => 'section_background',
						'priority' => 60,
						'type'     => 'ios', // light, ios, flat.
					)
				)
			);
		}
	}
}
