<?php

/**
 * LoginPress Settings API
 *
 * @since 1.0.9
 * @version 4.0.0
 */
if ( ! class_exists( 'LoginPress_Settings_API' ) ) :

	class LoginPress_Settings_API {

		/**
		 * settings sections array
		 *
		 * @var array
		 */
		protected $settings_sections = array();

		/**
		 * Settings fields array
		 *
		 * @var array
		 */
		protected $settings_fields = array();

		/**
		 * Captcha settings
		 *
		 * @since 5.0.0
		 */
		public $loginpress_captcha_settings;

		/**
		 * loginpress pro addons status
		 *
		 * @since 5.0.0
		 */
		public $loginpress_pro_addons;

		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			$this->loginpress_captcha_settings = get_option( 'loginpress_captcha_settings' );
			$this->loginpress_pro_addons = get_option( 'loginpress_pro_addons' );
		}

		/**
		 * Enqueue scripts and styles
		 */
		function admin_enqueue_scripts( $hook ) {

			if ( $hook != 'toplevel_page_loginpress-settings' ) {
				return;
			}
			// wp_enqueue_style( 'wp-color-picker' );
			// wp_enqueue_script( 'wp-color-picker' );

			// wp_enqueue_media();
			wp_enqueue_script( 'jquery' );
		}

		/**
		 * Set settings sections
		 *
		 * @param array $sections setting sections array
		 */
		function set_sections( $sections ) {

			$this->settings_sections = $sections;

			return $this;
		}

		/**
		 * Add a single section or multiple sections.
		 *
		 * @param array $section section array to add.
		 */
		function add_section( $section ) {

			$this->settings_sections[] = $section;

			return $this;
		}

		/**
		 * Set settings fields
		 *
		 * @param array $fields settings fields array
		 */
		function set_fields( $fields ) {

			$this->settings_fields = $fields;

			return $this;
		}

		/**
		 * Add settings field
		 *
		 * @param string $section settings section name
		 * @param string $field  settings field name
		 *
		 * @return array settings fields
		 */
		function add_field( $section, $field ) {

			$defaults = array(
				'name'  => '',
				'label' => '',
				'desc'  => '',
				'type'  => 'text',
			);

			$arg                                 = wp_parse_args( $field, $defaults );
			$this->settings_fields[ $section ][] = $arg;

			return $this;
		}

		/**
		 * Initialize and registers the settings sections and fileds to WordPress
		 *
		 * Usually this should be called at `admin_init` hook.
		 *
		 * This function gets the initiated settings sections and fields. Then
		 * registers them to WordPress and ready for use.
		 */
		function admin_init() {

			// register settings sections
			foreach ( $this->settings_sections as $section ) {
				if ( false == get_option( $section['id'] ) ) {
					add_option( $section['id'] );
				}
				$video_link = '';
				if ( isset( $section['video_link'] ) && ! empty( $section['video_link'] ) ) {
					$video_link = '<div class="video"><a href="https://www.youtube.com/watch?v=' . $section['video_link'] . '" target="_blank">' . __( 'How to Setup', 'loginpress' ) . '</a></div>';
				}
				if ( isset( $section['desc'] ) && ! empty( $section['desc'] ) ) {
					$section['desc'] = '<div class="inside"><div class="desc">' . $section['desc'] . '</div>' . $video_link . '</div>';
					$callback        = call_user_func( array( $this, 'get_description' ), $section['desc'] );
				} elseif ( isset( $section['callback'] ) ) {
					$callback = $section['callback'];
				} else {
					$callback = null;
				}

				add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
			}

			/**
			 * register settings fields
			 */
			foreach ( $this->settings_fields as $section => $field ) {
				foreach ( $field as $option ) {

					$name     = $option['name'];
					$type     = isset( $option['type'] ) ? $option['type'] : 'text';
					$label    = isset( $option['label'] ) ? $option['label'] : '';
					$callback = isset( $option['callback'] ) ? $option['callback'] : array( $this, 'callback_' . $type );

					$args = array(
						'id'                => $name,
						'class'             => isset( $option['class'] ) ? $option['class'] : $name,
						'label_for'         => "{$section}[{$name}]",
						'desc'              => isset( $option['desc'] ) ? $option['desc'] : '',
						'name'              => $label,
						'section'           => $section,
						'size'              => isset( $option['size'] ) ? $option['size'] : null,
						'options'           => isset( $option['options'] ) ? $option['options'] : '',
						'std'               => isset( $option['default'] ) ? $option['default'] : '',
						'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
						'type'              => $type,
						'placeholder'       => isset( $option['placeholder'] ) ? $option['placeholder'] : '',
						'min'               => isset( $option['min'] ) ? $option['min'] : '',
						'max'               => isset( $option['max'] ) ? $option['max'] : '',
						'step'              => isset( $option['step'] ) ? $option['step'] : '',
						'multiple'          => isset( $option['multiple'] ) ? $option['multiple'] : '',
					);

					add_settings_field( "{$section}[{$name}]", $label, $callback, $section, $section, $args );
				}
			}

			// creates our settings in the options table
			foreach ( $this->settings_sections as $section ) {
				register_setting( $section['id'], $section['id'], array( $this, 'sanitize_options' ) );
			}
		}

		/**
		 * Get field description for display.
		 *
		 * @param array $args settings field args.
		 */
		public function get_field_description( $args ) {
			if ( ! empty( $args['desc'] ) ) {
				$desc = sprintf( '<p class="description">%s</p>', $args['desc'] );
			} else {
				$desc = '';
			}

			return $desc;
		}

		/**
		 * Displays a text field for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_text( $args ) {

			$value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type        = isset( $args['type'] ) ? $args['type'] : 'text';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

			$html  = sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a text field for a settings field with type email.
		 *
		 * @param array $args settings field args
		 * @since 1.2.5
		 */
		function callback_email( $args ) {

			$value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type        = isset( $args['type'] ) ? $args['type'] : 'email';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
			$multiple    = empty( $args['multiple'] ) ? '' : 'multiple';

			$html  = sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s%7$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder, $multiple );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a url field for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_url( $args ) {
			$value       = esc_url( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type        = isset( $args['type'] ) ? $args['type'] : 'text';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

			$html  = sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a number field for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_number( $args ) {
			$value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type        = isset( $args['type'] ) ? $args['type'] : 'number';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
			$min         = ! isset( $args['min'] ) ? '' : ' min="' . $args['min'] . '"';
			$max         = empty( $args['max'] ) ? '' : ' max="' . $args['max'] . '"';
			$step        = empty( $args['max'] ) ? '' : ' step="' . $args['step'] . '"';
			$required    = isset( $args['min'] ) && $args['min'] > 0 ? 'required' : '';
			$html        = sprintf( '<input type="%1$s" class="%2$s-number" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s%7$s%8$s%9$s%10$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder, $min, $max, $step, $required );
			$html       .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a checkbox for a settings field
		 *
		 * @param array $args settings field args
		 * @version 5.0.0
		 */
		function callback_checkbox( $args ) {
			
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );

			// Get all LoginPress Pro addons
			$addons = $this->loginpress_pro_addons;

			// Check if Social Login addon is active
			$is_social_login_active = isset( $addons['social-login']['is_active'] ) && $addons['social-login']['is_active'];

			// Conditionally disable and uncheck specific fields
			$should_disable = false;

			// Add more IDs here if needed
			$disabled_ids = array( 'enable_social_woo_lf', 'enable_social_woo_rf', 'enable_social_woo_co', 'enable_social_llms_lf', 'enable_social_llms_rf', 'enable_social_llms_co', 'enable_social_ld_lf', 'enable_social_ld_rf', 'enable_social_ld_qf', 'enable_social_login_links_bp', 'enable_social_login_links_bb', 'enable_social_edd_lf', 'enable_social_edd_rf', 'enable_social_edd_co' );
			$html = '';
			if ( in_array( $args['id'], $disabled_ids ) && ! $is_social_login_active ) {
				$should_disable = true;
				$value = 'off'; // Force uncheck
				if ( $args['id'] == 'enable_social_woo_lf' || $args['id'] == 'enable_social_llms_lf' || $args['id'] == 'enable_social_ld_lf' || $args['id'] == 'enable_social_edd_lf' || $args['id'] == 'enable_social_login_links_bp' || $args['id'] == 'enable_social_login_links_bb' ){
					$html  .= '<div class="message warning">' . __( 'Activate Social Login addon to use following settings.', 'loginpress' ) . '</div>';
				}
			}

			$html  .= '<fieldset>';
			$html .= sprintf( '<label for="wpb-%1$s[%2$s]">', $args['section'], $args['id'] );
			$html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id'] );
			$html .= sprintf( '<input type="checkbox" class="checkbox loginpress-check-hidden" id="wpb-%1$s[%2$s]" name="%1$s[%2$s]" value="on" %3$s %4$s />', $args['section'], $args['id'], checked( $value, 'on', false ), $should_disable ? 'disabled' : '' );
			$html .= sprintf( '%2$s%3$s%1$s%4$s</label>', $args['desc'], '<span class="loginpress-checkbox"></span>', '<p>', '</p>' );
			$html .= '</fieldset>';

			echo $html;
		}

		/**
		 * Displays a multi-checkbox settings field.
		 *
		 * @param array $args settings field args
		 * @version 5.0.0
		 */
		function callback_multicheck( $args ) {
			$captcha_settings = $this->loginpress_captcha_settings;
			$captcha_enabled = isset( $captcha_settings['enable_captchas'] ) ? $captcha_settings['enable_captchas'] : 'off';
			// Check if the whole field should be disabled
			// For example: disable if it's the WooCommerce-related setting and WooCommerce is not active
			$disabled_attr = '';
			$html = '';
			if ( ($args['id'] === 'enable_captcha_woo' || $args['id'] === 'enable_captcha_ld' || $args['id'] === 'enable_captcha_llms' || $args['id'] === 'enable_captcha_bp' || $args['id'] === 'enable_captcha_bb' || $args['id'] === 'enable_captcha_edd') && $captcha_enabled === 'off' ) {
				$disabled_attr = 'disabled';
				$html  .= '<div class="message warning">' . __( 'Please enable Captcha first.', 'loginpress' ) . '</div>';
			}
			$br    = ( 'roles_for_password_reset' == $args['id'] || 'exclude_roles' == $args['id']) ? '' : '<br>';
			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
			$html  .= '<fieldset>';
			$html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="" />', $args['section'], $args['id'] );

			foreach ( $args['options'] as $key => $label ) {
				$checked = isset( $value[ $key ] ) ? $value[ $key ] : '0';
				$html   .= sprintf( '<label for="wpb-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
				$html   .= sprintf( '<input type="checkbox" class="checkbox loginpress-check-hidden" id="wpb-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s %5$s/>', $args['section'], $args['id'], $key, checked( $checked, $key, false ), $disabled_attr );
				$html   .= sprintf( '%2$s%1$s</label>%3$s', $label, '<span class="loginpress-checkbox"></span>', $br );
			}

			$html .= $this->get_field_description( $args );
			$html .= '</fieldset>';

			echo $html;
		}

		/**
		 * Displays a multi-checkbox settings field.
		 *
		 * @param array $args settings field args
		 */
		function callback_radio( $args ) {

			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
			$html  = '<fieldset>';

			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<label for="wpb-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
				$html .= sprintf( '<input type="radio" class="radio" id="wpb-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $value, $key, false ) );
				$html .= sprintf( '%1$s</label><br>', $label );
			}

			$html .= $this->get_field_description( $args );
			$html .= '</fieldset>';

			echo $html;
		}

		/**
		 * Displays a select box for a settings field.
		 *
		 * @param array $args settings field args
		 */
		function callback_select( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$html  = sprintf( '<select class="%1$s" name="%2$s[%3$s]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] );

			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
			}

			$html .= sprintf( '</select>' );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a textarea for a settings field.
		 *
		 * @param array $args settings field args
		 */
		function callback_textarea( $args ) {

			$value       = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

			$html  = sprintf( '<textarea rows="5" cols="55" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]"%4$s>%5$s</textarea>', $size, $args['section'], $args['id'], $placeholder, $value );
			$html .= $this->get_field_description( $args );

			echo $html;
		}


		/**
		 * Displays a textarea for a settings field.
		 *
		 * @param array $args settings field args
		 * @return string $html the html to be displayed.
		 * @since 1.0.0
		 */
		function callback_html( $args ) {

			echo $this->get_field_description( $args );
		}

		/**
		 * Displays a rich text textarea for a settings field
		 *
		 * @param array $args settings field args
		 * @return string $html the html to be displayed.
		 */
		function callback_wysiwyg( $args ) {

			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : '500px';

			echo '<div style="max-width: ' . $size . ';">';

			$editor_settings = array(
				'teeny'         => true,
				'textarea_name' => $args['section'] . '[' . $args['id'] . ']',
				'textarea_rows' => 10,
				'media_buttons' => false,
			);

			if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
				$editor_settings = array_merge( $editor_settings, $args['options'] );
			}

			wp_editor( $value, $args['section'] . '-' . $args['id'], $editor_settings );

			echo '</div>';

			echo $this->get_field_description( $args );
		}

		/**
		 * Displays a file upload field for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_file( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$id    = $args['section'] . '[' . $args['id'] . ']';
			$label = isset( $args['options']['button_label'] ) ? $args['options']['button_label'] : __( 'Choose File', 'loginpress' );

			$html  = sprintf( '<input type="text" class="%1$s-text wpsa-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
			$html .= '<input type="button" class="button wpsa-browse" value="' . $label . '" />';
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a password field for a settings field.
		 *
		 * @param array $args settings field args.
		 */
		function callback_password( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html  = sprintf( '<input type="password" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a color picker field for a settings field.
		 *
		 * @param array $args settings field args.
		 */
		function callback_color( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html  = sprintf( '<input type="color" class="%1$s-color wp-color-picker-field" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" data-default-color="%5$s" />', $size, $args['section'], $args['id'], $value, $args['std'] );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a search field for a autologin field.
		 *
		 * @param array $args settings field args.
		 */
		function callback_autologin( $args ) {

			$html  = apply_filters( 'loginpress_autologin', $args );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a text field for a hidelogin field
		 *
		 * @param array $args settings field args
		 */
		function callback_hidelogin( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$html  = apply_filters( 'loginpress_hidelogin', $args, $value );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a search field for a login redirects.
		 *
		 * @param array $args settings field args
		 * @since 1.0.23
		 */
		function callback_login_redirect( $args ) {

			$html  = apply_filters( 'loginpress_login_redirects', $args );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a search field for a Register Fields.
		 *
		 * @param array $args settings field args
		 * @since 1.0.23
		 */
		function callback_register_fields( $args ) {

			$html  = apply_filters( 'loginpress_register_fields', $args );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Sanitize callback for Settings API
		 *
		 * @param array $options The options array.
		 * @return mixed
		 */
		function sanitize_options( $options ) {

			if ( ! $options ) {
				return $options;
			}

			foreach ( $options as $option_slug => $option_value ) {
				$sanitize_callback = $this->get_sanitize_callback( $option_slug );

				// If callback is set and not false returned, call the sanitization function accordingly
				if ( $sanitize_callback !== false ) {
					$options[ $option_slug ] = call_user_func( $sanitize_callback, $option_value );
					continue;
				}
			}

			return $options;
		}

		/**
		 * Get sanitization callback for given option slug
		 *
		 * @param string $slug option slug
		 *
		 * @return mixed string or bool false
		 */
		function get_sanitize_callback( $slug = '' ) {
			if ( empty( $slug ) ) {
				return false;
			}

			// Iterate over registered fields and see if we can find proper callback
			foreach ( $this->settings_fields as $section => $options ) {
				foreach ( $options as $option ) {
					if ( $option['name'] != $slug ) {
						continue;
					}

					// Return the callback name
					return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : false;
				}
			}

			return false;
		}

		/**
		 * Get the value of a settings field
		 *
		 * @param string $option  settings field name
		 * @param string $section the section name this field belongs to
		 * @param string $default default text if it's not found
		 * @return string
		 */
		function get_option( $option, $section, $default = '' ) {

			$options = get_option( $section );

			if ( isset( $options[ $option ] ) ) {
				return $options[ $option ];
			}

			return $default;
		}

		/**
		 * Show navigation as tab
		 *
		 * Shows all the settings section labels as tab.
		 *
		 * @since 1.0.0
		 * @version 3.0.8
		 * @return $html The html output.
		 */
		function show_navigation() {

			$html = '<div class="loginpress-tabs-main"><span class="tabs-toggle">Menu</span><ul class="nav-tab-wrapper loginpress-tabs-wrapper">';

			foreach ( $this->settings_sections as $tab ) {
				if ( 'loginpress_premium' != $tab['id'] ) {
					$sub_title = isset( $tab['sub-title'] ) ? $tab['sub-title'] : '';
					// Define the end date for showing the "New" tag
					$end_date     = strtotime( '2025-03-25' ); // Timestamp for 17th February 2025
					$current_date = time(); // Current timestamp
					$new_tag      = '';
					if ( ( $tab['id'] == 'loginpress_captcha_settings' || $tab['id'] == 'loginpress_social_logins' ) ) {
						$new_tag = $this->loginpress_new_tag( $end_date );
					}

					$html .= sprintf( '<li class="settings-tabs-list"><a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s<span>%3$s</span>' . $new_tag . '</a></li>', $tab['id'], $tab['title'], $sub_title );
				}
				if ( 'loginpress_premium' == $tab['id'] ) {
					$html .= sprintf( '<a href="%1$s" class="loginpress-premium" target="_blank"><span class="dashicons dashicons-star-filled"></span>%2$s</a>', 'https://loginpress.pro/pricing/?utm_source=loginpress-lite&utm_medium=settings-tab&utm_campaign=pro-upgrade&utm_content=Upgrade+to+Pro+for+More+Features+CTA', $tab['title'] );
				}
			}

			$html .= '</ul></div>';

			echo $html;
		}
		/**
		 * Add "NEW" tag to any tab
		 *
		 * @since 4.0.0
		 * @return $html The html output.
		 */
		function loginpress_new_tag( $time = null ) {
			// Check if $time is set and is a valid timestamp
			if ( is_null( $time ) || ! is_numeric( $time ) ) {
				return; // Exit early if no valid time is provided
			}
			// Compare the provided time with the current time
			if ( time() <= $time ) {
				return '<strong class="loginpress-new-tag">New</strong>';
			}
		}

		/**
		 * Show the section settings forms
		 *
		 * This function displays every sections in a different form.
		 *
		 * @since 1.0.9
		 * @version 1.1.6
		 */
		function show_forms() {
			?>

			<div class="metabox-holder loginpress-settings">
				<?php foreach ( $this->settings_sections as $form ) : ?>
					<div id="<?php echo $form['id']; ?>" class="group" style="display: none;">
						<form method="post" action="options.php">
							<?php
							$remove_submit = array( 'loginpress_autologin', 'loginpress_login_redirects', 'loginpress_register_fields' );
							do_action( 'wsa_form_top_' . $form['id'], $form );
							settings_fields( $form['id'] );
							$this->do_settings_sections( $form['id'] );
							do_action( 'wsa_form_bottom_' . $form['id'], $form );
							if ( isset( $this->settings_fields[ $form['id'] ] ) ) :
								if ( ! in_array( $form['id'], $remove_submit ) ) : // Remove submit button from Autologin & Redirects tab.
									?>
								<div>
									<?php submit_button(); ?>
								</div>
								<?php endif; ?>
							<?php endif; ?>
						</form>
						<?php
						/**
						 * Add Autologin Addon Action Hook.
						 *
						 * @since 1.0.9
						 * @version 1.0.23
						 * @return string
						 */
						if ( $form['id'] == 'loginpress_autologin' ) :
							do_action( 'loginpress_autologin_script' );
						endif;
						/**
						 * Add Login Redirects Addon Action Hook.
						 *
						 * @since 1.0.23
						 * @return string
						 */
						if ( $form['id'] == 'loginpress_login_redirects' ) :
							do_action( 'loginpress_login_redirect_script' );
						endif;
						/**
						 * Add Limit Login Attempts Addon Action Hook.
						 *
						 * @since 1.0.23
						 * @return string
						 */
						if ( $form['id'] == 'loginpress_limit_login_attempts' ) :
							do_action( 'loginpress_limit_login_attempts_log_script' );
							do_action( 'loginpress_limit_login_attempts_whitelist_script' );
							do_action( 'loginpress_limit_login_attempts_blacklist_script' );
						endif;
						/**
						 * Add Register Custom Fields Addon Action Hook.
						 *
						 * @since 1.1.3
						 * @return string
						 */
						if ( $form['id'] == 'loginpress_register_fields' ) :
							do_action( 'loginpress_register_fields_script' );
						endif;
						/**
						 * Add Social Login Addon Action Hook.
						 *
						 * @since 1.1.6
						 * @return string
						 */
						if ( $form['id'] == 'loginpress_social_logins' ) :
							do_action( 'loginpress_social_login_help_tab_script' );
						endif;
						?>
					</div>
				<?php endforeach; ?>

			</div>

			<?php
			$this->script();
		}

		/**
		 * Tabulable JavaScript codes & Initiate Color Picker
		 *
		 * This code uses local-storage for displaying active tabs
		 */
		function script() {
			?>

			<script>
				jQuery(document).ready(function($) {
					//Initiate Color Picker
					// $('.wp-color-picker-field').wpColorPicker();

					let searchParams = new URLSearchParams(window.location.search);
					if(searchParams.has('tab')){
						localStorage.setItem("activetab", '#loginpress_' + searchParams.get('tab'));
					}
					// Switches option sections
					$('.group').hide();
					var activetab = '';
					if (typeof(localStorage) != 'undefined' ) {
						activetab = localStorage.getItem("activetab");
					}
					if ( activetab != '' && $(activetab).length ) {
						$(activetab).fadeIn();
					} else {
						$('.group:first').fadeIn();
					}
					$('.group .collapsed').each(function(){
					$(this).find('input:checked').parent().parent().parent().nextAll().each(
						function(){
							if ($(this).hasClass('last')) {
								$(this).removeClass('hidden');
								return false;
							}
							$(this).filter('.hidden').removeClass('hidden');
						});
					});

					if ( activetab != '' && $( activetab + '-tab' ).length ) {
						$( activetab + '-tab' ).addClass('nav-tab-active');
					} else {
						$('.nav-tab-wrapper a:first').addClass('nav-tab-active');
					}
					$('.nav-tab-wrapper a:not(".loginpress-premium")').click(function(evt) {
						$('.nav-tab-wrapper a').removeClass('nav-tab-active');
						$(this).addClass('nav-tab-active').blur();
						var clicked_group = $(this).attr('href');
						if (typeof(localStorage) != 'undefined' ) {
							localStorage.setItem("activetab", $(this).attr('href'));
						}
						$('.group').hide();
						$(clicked_group).fadeIn();
						evt.preventDefault();
					});

					$('.wpsa-browse').on('click', function (event) {
						event.preventDefault();

						var self = $(this);

						// Create the media frame.
						var file_frame = wp.media.frames.file_frame = wp.media({
							title: self.data('uploader_title'),
							button: {
								text: self.data('uploader_button_text'),
							},
							multiple: false
						});

						file_frame.on('select', function () {
							attachment = file_frame.state().get('selection').first().toJSON();
							self.prev('.wpsa-url').val(attachment.url).change();
						});

						// Finally, open the modal
						file_frame.open();
					});
				});
			</script>
			<?php
			$this->_style_fix();
		}

		/**
		 * Fixing the style conflict of color picker.
		 *
		 * @return void
		 */
		function _style_fix() {

			global $wp_version;

			if ( version_compare( $wp_version, '3.8', '<=' ) ) :
				?>
				<style type="text/css">
					/** WordPress 3.8 Fix **/
					.form-table th { padding: 20px 10px; }
					#wpbody-content .metabox-holder { padding-top: 5px; }
				</style>
				<?php
			endif;
		}



		/**
		 * Get Section Description
		 *
		 * @param string $desc Description.
		 *
		 * @since 1.1.0
		 */
		function get_description( $desc ) {
			return $desc;
		}

		/**
		 * Prints out all settings sections added to a particular settings page.
		 *
		 * @param string $page The slug name of the page who's settings sections you want to output.
		 * @since 1.1.0
		 */
		function do_settings_sections( $page ) {
			global $wp_settings_sections, $wp_settings_fields;

			if ( ! isset( $wp_settings_sections ) || ! isset( $wp_settings_sections[ $page ] ) ) {
				return;
			}

			foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
				echo "<h3>{$section['title']}</h3>\n";
				echo $section['callback'];
				if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
					continue;
				}
				echo '<table class="form-table">';
				do_settings_fields( $page, $section['id'] );
				echo '</table>';
			}
		}
	}
endif;
