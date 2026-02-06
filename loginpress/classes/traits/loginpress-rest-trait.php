<?php
/**
 * LoginPress REST Endpoints Trait
 *
 * Registers REST routes for LoginPress settings.
 * Handles read/update of settings data for the React Settings page.
 * Methods originally defined in `loginpress/loginpress.php` to keep the main file slim.
 *
 * @package   LoginPress
 * @subpackage Traits
 * @since     6.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'LoginPress_Rest_Trait' ) ) {
	/**
	 * LoginPress REST Endpoints Trait
	 *
	 * Handles REST API endpoints for LoginPress settings.
	 *
	 * @package   LoginPress
	 * @subpackage Traits
	 * @since     6.1.0
	 */
	trait LoginPress_Rest_Trait {

		/**
		 * Register the rest routes
		 *
		 * @since  6.0.0
		 * @return void
		 */
		public function loginpress_register_routes() {
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/settings',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'loginpress_get_settings' ),
					'permission_callback' => array( $this, 'loginpress_rest_can_manage_options' ),
				)
			);

			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/settings',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_update_settings' ),
					'permission_callback' => array( $this, 'loginpress_rest_can_manage_options' ),
				)
			);
		}

		/**
		 * Get loginpress settings
		 *
		 * @since  6.0.0
		 * @return array<string, mixed>
		 */
		public function loginpress_get_settings() { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Parameter required by WordPress REST API.
			$settings = get_option( 'loginpress_setting', array() );
			// Convert restrict_domains_textarea from array back to string for frontend.
			if ( isset( $settings['restrict_domains_textarea'] ) ) {
				if ( is_array( $settings['restrict_domains_textarea'] ) ) {
					$settings['restrict_domains_textarea'] = implode( "\n", $settings['restrict_domains_textarea'] );
				}
			}

			return array(
				'settings'    => $settings,
				'userRoles'   => wp_roles()->roles,
				'upgradeLink' => loginpress_admin_upgrade_link( 'settings-tab' ),
			);
		}

		/**
		 * Update loginpress settings
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @since  6.0.0
		 * @return array<string, mixed>
		 */
		public function loginpress_update_settings( WP_REST_Request $request ) {
			$settings = $request->get_json_params();

			// Process restrict_domains_textarea to convert string to array.
			if ( isset( $settings['restrict_domains_textarea'] ) && is_string( $settings['restrict_domains_textarea'] ) ) {
				$domains_string = $settings['restrict_domains_textarea'];
				$domains_array  = array();

				// Split by newlines and process each domain.
				$domains = explode( "\n", $domains_string );
				foreach ( $domains as $domain ) {
					$domain = trim( $domain );
					if ( ! empty( $domain ) ) {
						// Ensure domain starts with @ (compatible with PHP < 8).
						if ( function_exists( 'str_starts_with' ) ) {
							if ( ! str_starts_with( $domain, '@' ) ) {
								$domain = '@' . $domain;
							}
						} elseif ( substr( $domain, 0, 1 ) !== '@' ) {
								$domain = '@' . $domain;
						}
						$domains_array[] = $domain;
					}
				}

				// Save as array instead of string.
				$settings['restrict_domains_textarea'] = $domains_array;
			}

			if ( isset( $settings['reset_settings'] ) && 'on' === $settings['reset_settings'] ) {
				$loginpress_last_reset = array( 'last_reset_on' => gmdate( 'Y-m-d' ) );
				update_option( 'loginpress_customization', $loginpress_last_reset );
				update_option( 'customize_presets_settings', 'minimalist' );
				$settings['reset_settings'] = 'off';
				add_action( 'admin_notices', array( $this, 'settings_reset_message' ) );
			}
			update_option( 'loginpress_setting', $settings );

			return array( 'success' => true );
		}

		/**
		 * Check user permissions
		 *
		 * @since  6.0.0
		 * @return bool
		 */
		public function loginpress_rest_can_manage_options() {
			return current_user_can( 'manage_options' );
		}

		/**
		 * Add a link to the settings page to the plugins list.
		 *
		 * @since  1.0.11
		 * @version 3.0.8
		 * @param array<string> $links Array of existing action links.
		 * @param string        $file  Plugin file path.
		 * @return array<string> Modified action links array.
		 */
		public function loginpress_action_links( $links, $file ) {

			static $this_plugin;

			if ( empty( $this_plugin ) ) {
				$this_plugin = 'loginpress/loginpress.php';
			}

			if ( $file === $this_plugin ) {
				// Build the initial settings and customize links.
				$settings_link = sprintf(
					// translators: Build links.
					esc_html__( '%1$s Settings %2$s | %3$s Customize %4$s', 'loginpress' ),
					'<a href="' . admin_url( 'admin.php?page=loginpress-settings' ) . '">',
					'</a>',
					'<a href="' . admin_url( 'admin.php?page=loginpress' ) . '">',
					'</a>'
				);

				// Retrieve WPB SDK Opt Out options.
				$sdk_data = json_decode( get_option( 'wpb_sdk_loginpress' ), true );

				// Set default values for options.
				$communication   = isset( $sdk_data['communication'] ) ? $sdk_data['communication'] : false;
				$diagnostic_info = isset( $sdk_data['diagnostic_info'] ) ? $sdk_data['diagnostic_info'] : false;
				$extensions      = isset( $sdk_data['extensions'] ) ? $sdk_data['extensions'] : false;

				// Determine the opt-in state and whether all options are false.
				$is_optin          = 'yes' === get_option( '_loginpress_optin' );
				$all_options_false = false === $communication && false === $diagnostic_info && false === $extensions;

				// Build the settings link based on the option states.
				if ( $communication || $diagnostic_info || $extensions ) {
					$settings_link .= sprintf(
						// translators: links based on opt in.
						esc_html__( ' | %1$s Opt Out %2$s ', 'loginpress' ),
						'<a class="opt-out" href="' . admin_url( 'admin.php?page=loginpress-settings' ) . '">',
						'</a>'
					);
				} elseif ( $is_optin ) {
					if ( $all_options_false ) {
						// Case 1: Old users without any settings (meaning all options are false).
						// Ensure old users remain fully opted in by setting all options to true.
						$sdk_data = wp_json_encode(
							array(
								'communication'   => '1',
								'diagnostic_info' => '1',
								'extensions'      => '1',
								'user_skip'       => '0',
							)
						);
						update_option( 'wpb_sdk_loginpress', $sdk_data );
						$settings_link .= sprintf(
							// translators: setting link when opted out.
							esc_html__( ' | %1$s Opt Out %2$s ', 'loginpress' ),
							'<a class="opt-out" href="' . admin_url( 'admin.php?page=loginpress-settings' ) . '">',
							'</a>'
						);
					} else {
						// If opted in and not all options are false, update the opt-in state.
						update_option( '_loginpress_optin', 'no' );
						// Display opt-in link.
						$settings_link .= sprintf(
							// translators: Update opt-in state.
							esc_html__( ' | %1$s Opt In %2$s ', 'loginpress' ),
							'<a href="' . admin_url( 'admin.php?page=loginpress-optin&redirect-page=loginpress-settings' ) . '">',
							'</a>'
						);
					}

						// Display opt-out link.
				} else {
					$settings_link .= sprintf(
						// translators: Opt-out link.
						esc_html__( ' | %1$s Opt In %2$s ', 'loginpress' ),
						'<a href="' . admin_url( 'admin.php?page=loginpress-optin&redirect-page=loginpress-settings' ) . '">',
						'</a>'
					);
				}

				// Add the settings link to the array.
				array_unshift( $links, $settings_link );

				// Add Pro upgrade link if not already present.
				if ( ! has_action( 'loginpress_pro_add_template' ) ) {
					$pro_link = sprintf(
						// translators: Pro upgrade link.
						esc_html__( '%1$s %3$s Upgrade Pro %4$s %2$s', 'loginpress' ),
						'<a href="https://loginpress.pro/lite/?utm_source=loginpress-lite&utm_medium=plugins&utm_campaign=pro-upgrade&utm_content=Upgrade+Pro" target="_blank">',
						'</a>',
						'<span class="loginpress-dashboard-pro-link">',
						'</span>'
					);
					array_push( $links, $pro_link );
				}
			}

			return $links;
		}

		/**
		 * Session Expiration.
		 *
		 * @since  1.0.18
		 * @version 1.3.2
		 * @param int  $expiration Default expiration time in seconds.
		 * @param int  $user_id    The user ID.
		 * @param bool $remember   Whether to remember the user.
		 * @return int Modified expiration time in seconds.
		 */
		public function change_auth_cookie_expiration( $expiration, $user_id, $remember ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Parameters required by WordPress filter.
			$loginpress_setting = get_option( 'loginpress_setting' );
			$expiration_time    = isset( $loginpress_setting['session_expiration'] ) ? absint( $loginpress_setting['session_expiration'] ) : 0;

			/**
			 * Return the WordPress default $expiration time if LoginPress Session Expiration time set 0 or empty.
			 *
			 * @since 1.0.18
			 */
			// @phpstan-ignore-next-line
			if ( empty( $expiration_time ) || '0' === $expiration_time ) {
				return $expiration;
			}

			/**
			 * $filter_role Use filter `loginpress_exclude_role_session` for return the role.
			 * By default it's false and $expiration time will apply on all user.
			 *
			 * @return string|array|false role name.
			 * @since 1.3.2
			 */
			$filter_role = apply_filters( 'loginpress_exclude_role_session', false );

			if ( $filter_role ) {
				$user_data = get_userdata( $user_id );
				if ( ! $user_data ) {
					return $expiration;
				}
				$user_roles = $user_data->roles;

				// if $filter_role is array, return the default $expiration for each defined role.
				if ( is_array( $filter_role ) ) {
					foreach ( $filter_role as $role ) {
						if ( in_array( $role, $user_roles, true ) ) {
							return $expiration;
						}
					}
				} elseif ( in_array( $filter_role, $user_roles, true ) ) {
					return $expiration;
				}
			}

			// Convert Duration (minutes) of the expiration period in seconds.
			$expiration = $expiration_time * 60;

			return $expiration;
		}

		/**
		 * Redirect to Optin page.
		 *
		 * @since 1.0.15
		 * @return void
		 */
		public function redirect_optin() {
			/**
			 * Fix the Broken Access Control (BAC) security fix.
			 *
			 * @since 1.6.3
			 */
			if ( current_user_can( 'manage_options' ) ) {
				if ( isset( $_POST['loginpress-submit-optout'] ) ) {
					if ( ! isset( $_POST['loginpress_submit_optin_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['loginpress_submit_optin_nonce'] ) ), 'loginpress_submit_optin_nonce' ) ) {
						return;
					}
					update_option( '_loginpress_optin', 'no' );
					// Retrieve WPB SDK existing option and set user_skip.
					$sdk_data              = json_decode( get_option( 'wpb_sdk_loginpress' ), true );
					$sdk_data['user_skip'] = '1';
					$sdk_data_json         = wp_json_encode( $sdk_data );
					update_option( 'wpb_sdk_loginpress', $sdk_data_json );
				} elseif ( isset( $_POST['loginpress-submit-optin'] ) ) {
					if ( ! isset( $_POST['loginpress_submit_optin_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['loginpress_submit_optin_nonce'] ) ), 'loginpress_submit_optin_nonce' ) ) {
						return;
					}
					update_option( '_loginpress_optin', 'yes' );
					// WPB SDK OPT IN OPTIONS.
					$sdk_data      = array(
						'communication'   => '1',
						'diagnostic_info' => '1',
						'extensions'      => '1',
						'user_skip'       => '0',
					);
					$sdk_data_json = wp_json_encode( $sdk_data );
					update_option( 'wpb_sdk_loginpress', $sdk_data_json );
				} elseif ( ! get_option( '_loginpress_optin' ) && isset( $_GET['page'] ) && ( 'loginpress-settings' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) || 'loginpress' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) || 'abw' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) ) {
					/**
					 * XSS Attack vector found and fixed.
					 *
					 * @since 1.5.11
					 */
					$page_redirect = 'loginpress' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ? 'loginpress' : 'loginpress-settings';
					wp_safe_redirect( admin_url( 'admin.php?page=loginpress-optin&redirect-page=' . $page_redirect ) );
					exit;
				} elseif ( get_option( '_loginpress_optin' ) && ( 'yes' === get_option( '_loginpress_optin' ) ) && isset( $_GET['page'] ) && 'loginpress-optin' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
					wp_safe_redirect( admin_url( 'admin.php?page=loginpress-settings' ) );
					exit;
				}
			}
		}
	}
}
