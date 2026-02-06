<?php
/**
 * LoginPress Customizer Layout Trait.
 *
 * Defines functions related to customizer.
 * Contains logic moved from `class-loginpress-customizer.php`
 *
 * @package   LoginPress
 * @subpackage Traits\Customizer
 * @since     6.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'LoginPress_Customizer_Layout' ) ) {
	/**
	 * LoginPress Customizer Layout Trait.
	 *
	 * Handles customizer layout functionality for LoginPress.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\Customizer
	 * @since     6.1.0
	 */
	trait LoginPress_Customizer_Layout {
		/**
		 * Set Redirect Path of Logo.
		 *
		 * @since   1.0.0
		 * @version 1.5.3
		 * @return string The logo URL.
		 */
		public function login_page_logo_url() {
			if ( $this->loginpress_key && is_array( $this->loginpress_key ) && array_key_exists( 'customize_logo_hover', $this->loginpress_key ) && ! empty( $this->loginpress_key['customize_logo_hover'] ) ) {
				return esc_url( $this->loginpress_key['customize_logo_hover'] );
			} else {
				return home_url();
			}
		}

		/**
		 * Remove the filter login_errors from WooCommerce login form.
		 *
		 * @since   1.0.16
		 * @param mixed $validation_error The validation error.
		 * @param mixed $arg1 First argument.
		 * @param mixed $arg2 Second argument.
		 * @return mixed The validation error.
		 */
		public function loginpress_woo_login_errors( $validation_error, $arg1, $arg2 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Parameters required by WordPress filter.

			remove_filter( 'login_errors', array( $this, 'login_error_messages' ) );
			return $validation_error;
		}


		/**
		 * Set hover Title of Logo.
		 *
		 * @since   1.0.0
		 * @version 1.5.3
		 *
		 * @return mixed
		 */
		public function login_page_logo_title() {

			if ( $this->loginpress_key && is_array( $this->loginpress_key ) && array_key_exists( 'customize_logo_hover_title', $this->loginpress_key ) && ! empty( $this->loginpress_key['customize_logo_hover_title'] ) ) {
                return __( $this->loginpress_key['customize_logo_hover_title'], 'loginpress' ); // @codingStandardsIgnoreLine.
			} else {
				return home_url();
			}
		}

		/**
		 * Set Errors Messages to Show off.
		 *
		 * @param string $error The error message.
		 * @since   1.0.0
		 * @version 1.2.5
		 *
		 * @return string
		 */
		public function login_error_messages( $error ) {

			global $errors;

			if ( isset( $errors ) && apply_filters( 'loginpress_display_custom_errors', true ) ) {

				$error_codes = $errors->get_error_codes();

				if ( $this->loginpress_key && is_array( $this->loginpress_key ) ) {

					$invalid_username = array_key_exists( 'incorrect_username', $this->loginpress_key ) && ! empty( $this->loginpress_key['incorrect_username'] ) ? $this->loginpress_key['incorrect_username'] : sprintf(
						// translators: Username not valid.
						__( '%1$sError:%2$s Invalid Username.', 'loginpress' ),
						'<strong>',
						'</strong>'
					);

					$invalid_password = array_key_exists( 'incorrect_password', $this->loginpress_key ) && ! empty( $this->loginpress_key['incorrect_password'] ) ? $this->loginpress_key['incorrect_password'] : sprintf(
						// translators: Password not valid.
						__( '%1$sError:%2$s Invalid Password.', 'loginpress' ),
						'<strong>',
						'</strong>'
					);

					$empty_username = array_key_exists( 'empty_username', $this->loginpress_key ) && ! empty( $this->loginpress_key['empty_username'] ) ? $this->loginpress_key['empty_username'] : sprintf(
						// translators: Username field empty.
						__( '%1$sError:%2$s The username field is empty.', 'loginpress' ),
						'<strong>',
						'</strong>'
					);

					$empty_password = array_key_exists( 'empty_password', $this->loginpress_key ) && ! empty( $this->loginpress_key['empty_password'] ) ? $this->loginpress_key['empty_password'] : sprintf(
						// translators: Password field empty.
						__( '%1$sError:%2$s The password field is empty.', 'loginpress' ),
						'<strong>',
						'</strong>'
					);

					$invalid_email = array_key_exists( 'invalid_email', $this->loginpress_key ) && ! empty( $this->loginpress_key['invalid_email'] ) ? $this->loginpress_key['invalid_email'] : sprintf(
						// translators: Incorrect email.
						__( '%1$sError:%2$s The email address isn\'t correct..', 'loginpress' ),
						'<strong>',
						'</strong>'
					);

					$empty_email = array_key_exists( 'empty_email', $this->loginpress_key ) && ! empty( $this->loginpress_key['empty_email'] ) ? $this->loginpress_key['empty_email'] : sprintf(
						// translators: Enter email.
						__( '%1$sError:%2$s Please type your email address.', 'loginpress' ),
						'<strong>',
						'</strong>'
					);

					$username_exists = array_key_exists( 'username_exists', $this->loginpress_key ) && ! empty( $this->loginpress_key['username_exists'] ) ? $this->loginpress_key['username_exists'] : sprintf(
						// translators: Username already taken.
						__( '%1$sError:%2$s This username is already registered. Please choose another one.', 'loginpress' ),
						'<strong>',
						'</strong>'
					);

					$email_exists = array_key_exists( 'email_exists', $this->loginpress_key ) && ! empty( $this->loginpress_key['email_exists'] ) ? $this->loginpress_key['email_exists'] : sprintf(
						// translators: Email already taken.
						__( '%1$sError:%2$s This email is already registered, please choose another one.', 'loginpress' ),
						'<strong>',
						'</strong>'
					);

					$password_mismatch = array_key_exists( 'password_mismatch', $this->loginpress_key ) && ! empty( $this->loginpress_key['password_mismatch'] ) ? $this->loginpress_key['password_mismatch'] : sprintf(
						// translators: Password mismatch.
						__( '%1$sError:%2$s Passwords Don\'t match.', 'loginpress' ),
						'<strong>',
						'</strong>'
					);

					$invalid_combo = array_key_exists( 'invalidcombo_message', $this->loginpress_key ) && ! empty( $this->loginpress_key['invalidcombo_message'] ) ? $this->loginpress_key['invalidcombo_message'] : sprintf(
						// translators: Username or Password not vaild.
						__( '%1$sError:%2$s Invalid username or email.', 'loginpress' ),
						'<strong>',
						'</strong>'
					);

					if ( in_array( 'invalid_username', $error_codes, true ) ) {
						return $invalid_username;
					}

					if ( in_array( 'incorrect_password', $error_codes, true ) ) {
						return $invalid_password;
					}

					if ( in_array( 'empty_username', $error_codes, true ) ) {
						return $empty_username;
					}

					if ( in_array( 'empty_password', $error_codes, true ) ) {
						return $empty_password;
					}

					// registration Form entries.
					if ( in_array( 'invalid_email', $error_codes, true ) ) {
						return $invalid_email;
					}

					if ( in_array( 'empty_email', $error_codes, true ) ) {
						return '</br>' . $empty_email;
					}

					if ( in_array( 'username_exists', $error_codes, true ) ) {
						return $username_exists;
					}

					if ( in_array( 'email_exists', $error_codes, true ) ) {
						return $email_exists;
					}

					// forget password entry.
					if ( in_array( 'invalidcombo', $error_codes, true ) ) {
						return $invalid_combo;
					}

					// Password mismatch custom error message.
					if ( in_array( 'password_mismatch', $error_codes, true ) ) {
						return $password_mismatch;
					}
				}
			}

			return $error;
		}

		/**
		 * Redirecting the Lost Password url to default lost post password page when Woocommerce is active.
		 *
		 * @since 3.1.1
		 * @return string
		 */
		public function loginpress_reset_pass_url_in_notify() {
			$site_url  = get_option( 'siteurl' );
			$login_url = wp_login_url();
			$login_url = explode( '/', $login_url );
			$path      = $login_url[3];
			return "{$site_url}/{$path}?action=lostpassword";
		}

		/**
		 * Checks if the Lost password URL is enabled.
		 *
		 * @since 3.1.1
		 * @version 6.0.0
		 * @return void
		 */
		public function loginpress_lostpassword_url_changed() {

			$lostpassword_url = isset( $this->loginpress_settings['lostpassword_url'] ) ? $this->loginpress_settings['lostpassword_url'] : 'off';

			if ( 'on' === $lostpassword_url ) {
				add_filter( 'lostpassword_url', array( $this, 'loginpress_reset_pass_url_in_notify' ), 11, 0 );
			}
		}

		/**
		 * Change Lost Password Text from Form.
		 *
		 * @param string $translated_text The translated text.
		 * @param string $text The original text.
		 * @param string $domain The text domain.
		 * @since   1.0.0
		 * @version 3.0.0
		 *
		 * @return string
		 */
		public function change_lostpassword_message( $translated_text, $text, $domain ) {

			if ( is_array( $this->loginpress_key ) && array_key_exists( 'login_footer_text', $this->loginpress_key ) && 'Lost your password?' === $text && 'default' === $domain && trim( $this->loginpress_key['login_footer_text'] ) ) {

                return trim( __( $this->loginpress_key['login_footer_text'], 'loginpress' ) ); // @codingStandardsIgnoreLine.
			}

			return $translated_text;
		}

		/**
		 * Change Label of the Username from login Form.
		 *
		 * @param string $translated_text Translated text.
		 * @param string $text The text to translate.
		 * @param string $domain The text domain.
		 *
		 * @since 1.1.3
		 * @version 6.0.0
		 * @return string
		 */
		public function change_username_label( $translated_text, $text, $domain ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Parameters required by WordPress filter.

			if ( $this->loginpress_settings ) {

				$default     = 'Username or Email Address';
				$login_order = isset( $this->loginpress_settings['login_order'] ) ? $this->loginpress_settings['login_order'] : '';

				// If the option does not exist, return the text unchanged.
				if ( empty( $this->loginpress_settings ) && $default === $text ) {
					return $translated_text;
				}

				// If options exist, then translate away.
				if ( ! empty( $this->loginpress_settings ) && $default === $text ) {
					// Check if the option exists.
					if ( '' !== $login_order && 'default' !== $login_order ) {
						if ( 'username' === $login_order ) {
							$label = __( 'Username', 'loginpress' );
						} elseif ( 'email' === $login_order ) {
							$label = __( 'Email Address', 'loginpress' );
						} else {
							$label = __( 'Username or Email Address', 'loginpress' );
						}
						$translated_text = esc_html( $label );
					}
					$translated_text = esc_html( apply_filters( 'loginpress_username_label', $translated_text ) );
				}
			}
			return $translated_text;
		}
		/**
		 * Change Password Label from Form.
		 *
		 * @param string $translated_text The translated text.
		 * @param string $text The original text.
		 * @param string $domain The text domain.
		 * @since 1.1.3
		 *
		 * @return string
		 */
		public function change_password_label( $translated_text, $text, $domain ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Parameters required by WordPress filter.

			if ( is_array( $this->loginpress_key ) ) {
				$default = 'Password';
				$options = $this->loginpress_key;
				$label   = array_key_exists( 'form_password_label', $options ) ? $options['form_password_label'] : '';

				// If the option does not exist, return the text unchanged.
				if ( empty( $options ) && $default === $text ) {
					return $translated_text;
				}

				// If options exist, then translate away.
				if ( ! empty( $options ) && $default === $text ) {

					// Check if the option exists.
					if ( array_key_exists( 'form_password_label', $options ) ) {
						$translated_text = esc_html( $label );
					} else {
						return $translated_text;
					}
				}
			}
			return $translated_text;
		}

		/**
		 * Manage Welcome Messages.
		 *
		 * @param string $message The welcome message.
		 * @since   1.0.0
		 * @version 1.5.3
		 *
		 * @return string
		 */
		public function change_welcome_message( $message ) {
			if ( strpos( $message, __( 'Please enter your username or email address. You will receive an email message with instructions on how to reset your password.', 'loginpress' ) ) !== false ) {
				return $message;
			}
			if ( is_array( $this->loginpress_key ) ) {

				// Check, User Logged out.
				if ( isset( $_GET['loggedout'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['loggedout'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for display purposes.

					if ( array_key_exists( 'logout_message', $this->loginpress_key ) && ! empty( $this->loginpress_key['logout_message'] ) ) {

                        $loginpress_message = __( $this->loginpress_key['logout_message'], 'loginpress' ); // @codingStandardsIgnoreLine.
					}
				} else { // phpcs:ignore
					// Logged In messages.
					if ( isset( $_GET['action'] ) && 'lostpassword' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for display purposes.

						if ( array_key_exists( 'lostpwd_welcome_message', $this->loginpress_key ) && ! empty( $this->loginpress_key['lostpwd_welcome_message'] ) ) {

	                        $loginpress_message = __( $this->loginpress_key['lostpwd_welcome_message'], 'loginpress' ); // @codingStandardsIgnoreLine.
						}
					} elseif ( isset( $_GET['action'] ) && 'register' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for display purposes.

						if ( array_key_exists( 'register_welcome_message', $this->loginpress_key ) && ! empty( $this->loginpress_key['register_welcome_message'] ) ) {

	                        $loginpress_message = __( $this->loginpress_key['register_welcome_message'], 'loginpress' ); // @codingStandardsIgnoreLine.
						}
					} elseif ( strpos( $message, __( 'Your password has been reset.', 'loginpress' ) ) !== false ) {

						$loginpress_message = __( 'Your password has been reset.', 'loginpress' ) . ' <a href="' . esc_url( wp_login_url() ) . '">' . __( 'Log in', 'loginpress' ) . '</a></p>';
					} elseif ( array_key_exists( 'welcome_message', $this->loginpress_key ) && ! empty( $this->loginpress_key['welcome_message'] ) ) {

	                        $loginpress_message = __( $this->loginpress_key['welcome_message'], 'loginpress' ); // @codingStandardsIgnoreLine.
					}
				}

				return ! empty( $loginpress_message ) ? "<p class='custom-message'>" . $loginpress_message . '</p>' : $message;
			}

			return $message;
		}

		/**
		 * Set WordPress login page title.
		 *
		 * @param string $title The page title.
		 * @since   1.4.6
		 * @version 1.5.3
		 * @return  string
		 */
		public function login_page_title( $title ) {

			if ( $this->loginpress_key && is_array( $this->loginpress_key ) && array_key_exists( 'customize_login_page_title', $this->loginpress_key ) && ! empty( $this->loginpress_key['customize_login_page_title'] ) ) {
                return __( $this->loginpress_key['customize_login_page_title'], 'loginpress' ); // @codingStandardsIgnoreLine.
			} else {
				return $title;
			}
		}

		/**
		 * Hook to Redirect Page for Customize.
		 *
		 * @since   1.1.3
		 * @version 3.2.1
		 * @return void
		 */
		public function redirect_to_custom_page() {
			if ( ! empty( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Page parameter for display purposes.
				$page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Page parameter for display purposes.
				if ( ( 'abw' === $page ) || ( 'loginpress' === $page ) ) {

					if ( is_multisite() ) { // if subdirectories are used in multisite.

						$loginpress_obj  = new LoginPress();
						$loginpress_page = $loginpress_obj->get_loginpress_page();

						$page = get_permalink( $loginpress_page );

						// Generate the redirect url.
							$url = add_query_arg(
								array(
									'autofocus[panel]' => 'loginpress_panel',
									'url'              => rawurlencode( $page ? $page : '' ),
								),
								admin_url( 'customize.php' )
							);

						wp_safe_redirect( $url );

					} else {
						$login_url = wp_login_url();
						$site_url  = site_url();

						// Parse the URLs only once to avoid redundancy.
						$parsed_login_url = wp_parse_url( $login_url );
						$parsed_site_url  = wp_parse_url( $site_url );

						// Determine login path.
						$login_path   = isset( $parsed_login_url['path'] ) ? $parsed_login_url['path'] : '/wp-login.php';
						$subdirectory = isset( $parsed_site_url['path'] ) ? $parsed_site_url['path'] : '';

						// If the login path starts with the subdirectory, remove the subdirectory from it.
						if ( ! empty( $subdirectory ) && strpos( $login_path, $subdirectory ) === 0 ) {
							$login_path = substr( $login_path, strlen( $subdirectory ) );
						}

						$login_path = sanitize_text_field( rtrim( $login_path, '/' ) );

						// Redirect to the login page URL.
						wp_safe_redirect( get_admin_url() . 'customize.php?url=' . esc_url( site_url( $login_path, 'login_post' ) ) . '&autofocus=loginpress_panel' );
						exit;
					}
				}
			}
		}

		/**
		 * Sanitize username with allowed characters.
		 *
		 * @since 6.0.0
		 *
		 * @param string $username The username to sanitize.
		 * @param string $raw_username The raw username input.
		 * @param bool   $strict Whether to apply strict sanitization.
		 *
		 * @return string Sanitized username.
		 */
		public function loginpress_sanitize_username( $username, $raw_username, $strict ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Parameters required by WordPress filter.

			$allowed_chars = isset( $this->loginpress_settings['allowed_username_characters'] ) && is_array( $this->loginpress_settings['allowed_username_characters'] ) ? array_keys( $this->loginpress_settings['allowed_username_characters'] ) : array();

			$unicode_map = array(
				'arabic'   => '\\p{Arabic}',
				'latin'    => '\\p{Latin}',
				'armenian' => '\\p{Armenian}',
				'bengali'  => '\\p{Bengali}',
				'bopomofo' => '\\p{Bopomofo}',
				'cyrillic' => '\\p{Cyrillic}',
				'georgian' => '\\p{Georgian}',
				'greek'    => '\\p{Greek}',
			);
			// Validate unicode_map keys to prevent injection.
			$allowed_chars = array_intersect( (array) $allowed_chars, array_keys( $unicode_map ) );
			$pattern       = '|[^a-z0-9 _.\-@' . implode( '', array_intersect_key( $unicode_map, array_flip( $allowed_chars ) ) ) . ']|iu';

			$username = wp_strip_all_tags( $raw_username );
			$username = remove_accents( $username );
			$username = preg_replace(
				array( '|%([a-fA-F0-9][a-fA-F0-9])|', '/&.+?;/', $pattern, '|\s+|' ),
				array( '', '', '', ' ' ),
				trim( $username )
			);
			return $username ? $username : '';
		}

		/**
		 * Redirect to the Admin Panel After Closing LoginPress Customizer.
		 *
		 * @since   1.0.0
		 * @version 3.0.6
		 * @return  void
		 */
		public function menu_url() {

			global $submenu; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required for menu modification.

			$parent = 'index.php';
			$page   = 'abw';

			// Create specific url for login view.
			$login_url  = wp_login_url();
			$parsed_url = wp_parse_url( $login_url );
			$login_url  = isset( $parsed_url['path'] ) ? sanitize_text_field( $parsed_url['path'] ) : 'wp-login.php';
			$url        = add_query_arg(
				array(
					'url'    => esc_url( site_url( $login_url, 'login_post' ) ),
					'return' => admin_url( 'themes.php' ),
				),
				admin_url( 'customize.php' )
			);

			// If is Not Design Menu, return.
			if ( ! isset( $submenu[ $parent ] ) ) {
				exit;
			}

			foreach ( $submenu[ $parent ] as $key => $value ) {
				// Set new URL for menu item.
				if ( $page === $value[2] ) {
					$submenu[ $parent ][ $key ][2] = $url;	// phpcs:ignore
					break;
				}
			}
		}

		/**
		 * This function is removed the error messages in the customizer.
		 *
		 * @param WP_Error $errors The error object.
		 * @param string   $redirect_to The redirect URL.
		 * @since  1.2.0
		 * @version 3.0.6
		 * @return WP_Error
		 */
		public function remove_error_messages_in_wp_customizer( $errors, $redirect_to ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Parameters required by WordPress filter.

			if ( is_customize_preview() && version_compare( $GLOBALS['wp_version'], '5.2', '>=' ) ) {
				return new WP_Error( '', '' );
			}
			// If Logout message is set and not empty then remove the default logout message from WordPress.
			if ( is_array( $this->loginpress_key ) && array_key_exists( 'logout_message', $this->loginpress_key ) && ! empty( $this->loginpress_key['logout_message'] ) ) {
				if ( isset( $_GET['loggedout'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['loggedout'] ) ) && isset( $errors->errors['loggedout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for display purposes.
					unset( $errors->errors['loggedout'] );
				}
			}
			return $errors;
		}
	}
}
