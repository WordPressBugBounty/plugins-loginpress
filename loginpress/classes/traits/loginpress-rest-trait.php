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
		 * @version 6.2.3
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

			// Process exclude_force_login_ids - ensure it's saved as array of IDs (dense array for JSON/React).
			if ( isset( $settings['exclude_force_login_ids'] ) ) {
				$raw = $settings['exclude_force_login_ids'];
				if ( is_array( $raw ) ) {
					$settings['exclude_force_login_ids'] = array_values( array_filter( array_map( 'absint', $raw ) ) );
				} else {
					// Best-effort migration for legacy or malformed data (e.g. string or comma-separated IDs).
					$ids = array();
					if ( is_string( $raw ) ) {
						$parts = preg_split( '/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
						foreach ( $parts as $part ) {
							$id = absint( trim( $part ) );
							if ( $id > 0 ) {
								$ids[] = $id;
							}
						}
					} elseif ( is_numeric( $raw ) ) {
						$id = absint( $raw );
						if ( $id > 0 ) {
							$ids[] = $id;
						}
					}
					$settings['exclude_force_login_ids'] = array_values( array_unique( $ids ) );
				}
			}

			if ( ! isset( $settings['exclude_force_login_ids'] ) ) {
				$settings['exclude_force_login_ids'] = array();
			}

			// Compute and cache exclude_force_login_urls from IDs (used on front end to avoid get_posts per request).
			$ids = $settings['exclude_force_login_ids'];
			if ( ! empty( $ids ) ) {
				$posts = get_posts(
					array(
						'post__in'       => $ids,
						'posts_per_page' => -1,
						'post_type'      => 'any',
						'post_status'    => 'publish',
						'fields'         => 'ids',
						'no_found_rows'  => true,
					)
				);
				$urls  = array();
				foreach ( $posts as $post_id ) {
					$urls[] = rtrim( get_permalink( $post_id ), '/' );
				}
				$settings['exclude_force_login_urls'] = array_values( $urls );
			} else {
				$settings['exclude_force_login_urls'] = array();
			}

			if ( isset( $settings['reset_settings'] ) && 'on' === $settings['reset_settings'] ) {
				$loginpress_last_reset = array( 'last_reset_on' => gmdate( 'Y-m-d' ) );
				update_option( 'loginpress_customization', $loginpress_last_reset );
				update_option( 'customize_presets_settings', 'minimalist' );
				$settings['reset_settings'] = 'off';
				add_action( 'admin_notices', array( $this, 'settings_reset_message' ) );
			}

			if ( isset( $settings['session_expiration'] ) ) {
				$session_expiration = absint( $settings['session_expiration'] );
				$session_max        = loginpress_session_expiration_max();

				if ( $session_expiration > $session_max ) {
					$session_expiration = $session_max;
				}

				$settings['session_expiration'] = $session_expiration;
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

				// Add the settings link to the array.
				array_unshift( $links, $settings_link );

				// Add Pro upgrade link if not already present.
				if ( ! has_action( 'loginpress_pro_add_template' ) ) {
					$pro_link = sprintf(
						// translators: Pro upgrade link.
						esc_html__( '%1$s %3$s Upgrade Pro %4$s %2$s', 'loginpress' ),
						'<a href="https://loginpress.pro/lite/?utm_source=loginpress-lite&utm_medium=plugins&utm_campaign=pro-upgrade&utm_content=Upgrade+Pro" target="_blank" rel="noopener noreferrer" style="color:#3db634;font-weight:600;">',
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
			$expiration_max     = loginpress_session_expiration_max();

			if ( $expiration_time > $expiration_max ) {
				$expiration_time = $expiration_max;
			}

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
	}
}
