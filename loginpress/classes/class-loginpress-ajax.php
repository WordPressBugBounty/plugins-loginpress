<?php
/**
 * LoginPress AJAX Handler
 *
 * This file handles all AJAX requests for the LoginPress plugin.
 * It manages addon activation, deactivation, import/export, and other plugin operations.
 *
 * @package LoginPress
 * @since 1.0.19
 * @version 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! class_exists( 'LoginPress_AJAX' ) ) :

	/**
	 * LoginPress AJAX Class
	 *
	 * Handles all AJAX requests for the LoginPress plugin.
	 * This includes import/export, addon activation, and various admin operations.
	 *
	 * @since 1.0.19
	 * @version 4.0.0
	 */
	class LoginPress_AJAX {

		/**
		 * Database table name for login limit details.
		 *
		 * @var string
		 */
		private $table_name;

		/**
		 * Class constructor.
		 *
		 * @since 1.0.19
		 * @return void
		 */
		public function __construct() {
			global $wpdb;
			$this->table_name = $wpdb->prefix . 'loginpress_limit_login_details';
			$this->init();
		}

		/**
		 * Initialize AJAX calls.
		 *
		 * Sets up all AJAX action hooks for the plugin.
		 *
		 * @since 1.0.19
		 * @return void
		 */
		public function init() {
			$ajax_calls = array(
				'export'            => false,
				'import'            => false,
				'help'              => false,
				'deactivate'        => false,
				'optout_yes'        => false,
				'presets'           => false,
				'video_url'         => false,
				'youtube_video_url' => false,
				'activate_addon'    => false,
				'deactivate_addon'  => false,
			);

			foreach ( $ajax_calls as $ajax_call => $no_priv ) {
				if ( $no_priv ) {
					add_action( 'wp_ajax_nopriv_loginpress_' . $ajax_call, array( $this, $ajax_call ) );
				} else {
					add_action( 'wp_ajax_loginpress_' . $ajax_call, array( $this, $ajax_call ) );
				}
			}
		}

		/**
		 * Activate Plugins.
		 *
		 * @since 1.2.2
		 * @version 3.0.6
		 * @return void
		 */
		public function activate_addon() {
			if ( ! isset( $_POST['slug'] ) ) {
				wp_die( esc_html__( 'Invalid request.', 'loginpress' ) );
			}
			$plugin_slug = sanitize_text_field( wp_unslash( $_POST['slug'] ) );

			check_ajax_referer( 'install-plugin_' . $plugin_slug, '_wpnonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'No cheating, huh!', 'loginpress' ) );
			}
			if ( defined( 'LOGINPRESS_PRO_VERSION' ) && version_compare( LOGINPRESS_PRO_VERSION, '3.0.0', '>=' ) ) {
				$addons = get_option( 'loginpress_pro_addons' );

				if ( $addons && is_array( $addons ) ) {
					foreach ( $addons as $addon ) {
						if ( $plugin_slug === $addon['slug'] ) {
							if ( true === $addon['is_free'] ) {
								activate_plugins( $addon['slug'] . '/' . $addon['slug'] . '.php' );
								echo esc_attr( wp_create_nonce( 'uninstall_' . $plugin_slug ) );
							}

							$addons[ $plugin_slug ]['is_active'] = true;
							break;
						}
					}
					if ( class_exists( 'LoginPress_Pro' ) && 'login-logout-menu' !== $plugin_slug ) {
						if ( LoginPress_Pro::addon_wrapper( $plugin_slug ) ) {
							update_option( 'loginpress_pro_addons', $addons );
							do_action( 'loginpress_pro_addon_activation', $plugin_slug );
							echo esc_attr( wp_create_nonce( 'uninstall_' . $plugin_slug ) );
						} else {
							echo esc_html( 'erroneous' );
						}
					}
				} else {
					echo 'erroneous';
				}
			} else {
				$free_slug = ( 'login-logout-menu' === $plugin_slug ) ? $plugin_slug . '/' . $plugin_slug . '.php' : $plugin_slug;
				if ( ! is_plugin_active( $free_slug ) ) {
					activate_plugins( $free_slug );
				}

				echo esc_attr( wp_create_nonce( 'uninstall_' . $plugin_slug ) );
			}
			wp_die();
		}

		/**
		 * Deactivate Plugins.
		 *
		 * @since 1.2.2
		 * @version 3.0.0
		 * @return void
		 */
		public function deactivate_addon() {
			if ( ! isset( $_POST['slug'] ) ) {
				wp_die( esc_html__( 'Invalid request.', 'loginpress' ) );
			}
			$plugin_slug = sanitize_text_field( wp_unslash( $_POST['slug'] ) );

			check_ajax_referer( 'uninstall_' . $plugin_slug, '_wpnonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'No cheating, huh!', 'loginpress' ) );
			}
			if ( defined( 'LOGINPRESS_PRO_VERSION' ) && version_compare( LOGINPRESS_PRO_VERSION, '3.0.0', '>=' ) ) {
				$addons = get_option( 'loginpress_pro_addons' );

				if ( $addons && is_array( $addons ) ) {
					foreach ( $addons as $addon ) {
						if ( $plugin_slug === $addon['slug'] ) {
							if ( true === $addon['is_free'] ) {
								deactivate_plugins( $addon['slug'] . '/' . $addon['slug'] . '.php' );
							}

							$addons[ $plugin_slug ]['is_active'] = false;
							break;
						}
					}

					update_option( 'loginpress_pro_addons', $addons );
				}

				echo esc_attr( wp_create_nonce( 'install-plugin_' . $plugin_slug ) );
			} else {
				$free_slug = ( 'login-logout-menu' === $plugin_slug ) ? $plugin_slug . '/' . $plugin_slug . '.php' : $plugin_slug;

				deactivate_plugins( $free_slug );

				echo esc_attr( wp_create_nonce( 'install-plugin_' . $free_slug ) );
			}
			wp_die();
		}

		/**
		 * Import LoginPress Settings, update loginPress settings meta.
		 *
		 * @since 1.0.19
		 * @version 3.0.0
		 * @return void
		 */
		public function import() {
			$image_error = false;
			check_ajax_referer( 'loginpress-import-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'No cheating, huh!', 'loginpress' ) );
			}

			// Validate file upload.
			if ( ! isset( $_FILES['file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['file']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File validation is done by is_uploaded_file().
				wp_die( esc_html__( 'Invalid file upload.', 'loginpress' ) );
			}
			global $wpdb;
			$import_tmp_name = $_FILES['file']['tmp_name']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File path is validated by is_uploaded_file().
			$file_content    = file_get_contents( $import_tmp_name ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file reading is safe here.
			$loginpress_json = false;
			if ( false !== $file_content ) {
				$loginpress_json = json_decode( $file_content, true );
			}

			if ( JSON_ERROR_NONE === json_last_error() ) {

				foreach ( $loginpress_json as $object => $array ) {
					// Check for LoginPress customizer images.
					if ( 'loginpress_customization' === $object ) {
						update_option( $object, $array );

						foreach ( $array as $key => $value ) {
							// Array of loginpress customizer images.
							$images_check = array( 'setting_logo', 'setting_background', 'setting_form_background', 'forget_form_background', 'gallery_background' );

							/**
							 * If json fetched data has array of $images_check.
							 *
							 * @var array{'setting_logo', 'setting_background', 'setting_form_background', 'forget_form_background', 'gallery_background'} $images_check
							 */
							if ( in_array( $key, $images_check, true ) ) {

								// Count the $value of that $key from {$wpdb->posts}.
								$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(guid) FROM {$wpdb->posts} WHERE guid=%s", $value ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query needed for image validation.

								if ( $count < 1 && ! empty( $value ) ) {
									$file             = array();
									$file['name']     = basename( $value );
									$file['tmp_name'] = download_url( $value ); // Downloads a url to a local temporary file.
									if ( is_wp_error( $file['tmp_name'] ) ) {
										$error_message = $file['tmp_name']->get_error_message(); // Get the error message.
										$image_error   = true;
									} else {
										$id  = media_handle_sideload( $file, 0 ); // Handles a sideloaded file.
										$src = '';
										if ( ! is_wp_error( $id ) ) {
											$src = wp_get_attachment_url( $id ); // Returns a full URI for an attachment file.
										}
										$loginpress_options = get_option( 'loginpress_customization' ); // Get option that was updated previously.

										// Change the options array properly.
										$loginpress_options[ $key ] = esc_url_raw( $src );

										// Update entire array again for save the attachment w.r.t $key.
										update_option( $object, $loginpress_options );
									}
								} // media_upload.
							} // images check.
						} // inner foreach.
					} // loginpress_customization check.

					if ( 'loginpress_setting' === $object ) {
						$loginpress_options = get_option( 'loginpress_setting' );
						// Check $loginpress_options is exists.
						if ( isset( $loginpress_options ) && ! empty( $loginpress_options ) ) {
							foreach ( $array as $key => $value ) {
								// Array of loginpress Settings that doesn't import.
								$setting_array = array( 'captcha_enable', 'captcha_language', 'captcha_theme', 'recaptcha_type', 'secret_key', 'secret_key_v2_invisible', 'secret_key_v3', 'site_key', 'site_key_v2_invisible', 'site_key_v3', 'good_score', 'enable_repatcha' );

								if ( ! in_array( $key, $setting_array, true ) ) {
									// Change the options array properly.
									$loginpress_options[ $key ] = sanitize_text_field( $value );
									// Update array w.r.t $key exists.
									update_option( $object, $loginpress_options );
								}
							} // inner foreach.
						} else {
							update_option( $object, $array );
						}
					} // loginpress_setting check.

					if ( 'customize_presets_settings' === $object ) {
						update_option( 'customize_presets_settings', $array );
					}
					// loginpress_limit_login_attempts.
					if ( 'loginpress_limit_login_attempts' === $object ) {
						update_option( 'loginpress_limit_login_attempts', $array );
					}
					if ( 'loginpress_limit_login_details' === $object ) {

						// Validate data structure before proceeding.
						if ( isset( $array ) && is_array( $array ) ) {
							foreach ( $array as $record ) {
								// Validate record structure.
								if ( ! isset( $record['ip'] ) || ! isset( $record['username'] ) || ! isset( $record['datentime'] ) || ! isset( $record['gateway'] ) || ! isset( $record['whitelist'] ) || ! isset( $record['blacklist'] ) ) {
									continue; // Skip invalid records.
								}

								// Sanitize data before insertion.
								$sanitized_record = array(
									'ip'        => sanitize_text_field( $record['ip'] ),
									'username'  => sanitize_text_field( $record['username'] ),
									'datentime' => absint( $record['datentime'] ),
									'gateway'   => sanitize_text_field( $record['gateway'] ),
									'whitelist' => absint( $record['whitelist'] ),
									'blacklist' => absint( $record['blacklist'] ),
								);

								// Insert each record into the database.
								$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct insert needed for import functionality.
									$this->table_name,
									$sanitized_record,
									array(
										'%s', // ip (string).
										'%s', // username (string).
										'%d', // datentime (integer).
										'%s', // gateway (string).
										'%d', // whitelist (integer).
										'%d',  // blacklist (integer).
									)
								);

								// Log any errors that occur during insertion.
								if ( false === $result ) { //phpcs:ignore
									// Error logging can be added here if needed.
								}
							}
						}
					}
				} // endforeach.
			} else {
				echo esc_html( 'error' );
			}
			if ( true === $image_error ) {
				wp_send_json_success(
					array(
						'status'  => 'error',
						'message' => esc_html__( 'Could not download image from remote source.', 'loginpress' ),
					)
				);
			}
			wp_die();
		}

		/**
		 * Export LoginPress Settings.
		 *
		 * @since 1.0.19
		 * @version 3.0.0
		 * @return void
		 */
		public function export() {
			check_ajax_referer( 'loginpress-export-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'No cheating, huh!', 'loginpress' ) );
			}

			$loginpress_db                   = array();
			$loginpress_setting_options      = array();
			$loginpress_customization        = get_option( 'loginpress_customization' );
			$loginpress_setting              = get_option( 'loginpress_setting' );
			$loginpress_preset               = get_option( 'customize_presets_settings' );
			$loginpress_setting_fetch        = array( 'captcha_enable', 'captcha_language', 'captcha_theme', 'recaptcha_type', 'secret_key', 'secret_key_v2_invisible', 'secret_key_v3', 'site_key', 'site_key_v2_invisible', 'site_key_v3', 'good_score', 'enable_repatcha' );
			$loginpress_limit_login_attempts = false;
			$loginpress_limit_login_details  = false;
			if ( class_exists( 'LoginPress_Pro' ) ) {
				if ( LoginPress_Pro::is_activated() ) {
					$loginpress_limit_login_attempts = get_option( 'loginpress_limit_login_attempts' );
					global $wpdb;
					// Check if the table exists.
					$table_exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table existence check needed.
						$wpdb->prepare(
							'SHOW TABLES LIKE %s',
							$this->table_name
						)
					);

					if ( $table_exists ) {
						// Get result from the table where IPs are blacklisted or whitelisted.
						$loginpress_limit_login_details = $wpdb->get_results( "SELECT * FROM `{$this->table_name}` WHERE `whitelist` = 1 OR `blacklist` = 1" ); // phpcs:ignore
					}
					// Log or handle the case where the table doesn't exist.
					// Table doesn't exist, continue without data.
				}
			}
			if ( $loginpress_customization ) {
				$loginpress_db['loginpress_customization'] = $loginpress_customization;
			}
			if ( $loginpress_setting ) {
				foreach ( $loginpress_setting as $key => $value ) {
					if ( ! in_array( $key, $loginpress_setting_fetch, true ) ) {
						$loginpress_setting_options[ $key ] = $value;
					}
				}

				$loginpress_db['loginpress_setting'] = $loginpress_setting_options;
			}

			if ( $loginpress_preset ) {
				$loginpress_db['customize_presets_settings'] = $loginpress_preset;
			}

			if ( $loginpress_limit_login_attempts ) {
				$loginpress_db['loginpress_limit_login_attempts'] = $loginpress_limit_login_attempts;
			}
			if ( $loginpress_limit_login_details ) {
				$loginpress_db['loginpress_limit_login_details'] = $loginpress_limit_login_details;
			}

			$loginpress_db = wp_json_encode( $loginpress_db );

			echo wp_kses_post( $loginpress_db );

			wp_die();
		}

		/**
		 * Download the log file from Help page.
		 *
		 * @since 1.0.19
		 * @version 3.0.0
		 * @return void
		 */
		public function help() {
			check_ajax_referer( 'loginpress-log-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'No cheating, huh!', 'loginpress' ) );
			}

			require_once (defined( 'LOGINPRESS_DIR_PATH' ) ? LOGINPRESS_DIR_PATH : '') . 'classes/class-loginpress-log-info.php'; //phpcs:ignore

			echo LoginPress_Log_Info::get_sysinfo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is pre-formatted system info.

			wp_die();
		}

		/**
		 * Get response from user on plugin deactivation.
		 *
		 * @since 1.0.15
		 * @version 3.0.0
		 * @return void
		 */
		public function deactivate() {
			check_ajax_referer( 'loginpress-deactivate-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'No cheating, huh!', 'loginpress' ) );
			}

			$email         = get_option( 'admin_email' );
			$reason_code   = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';
			$reason_detail = isset( $_POST['reason_detail'] ) ? sanitize_text_field( wp_unslash( $_POST['reason_detail'] ) ) : '';
			$reason        = '';

			if ( '1' == $reason_code ) { //phpcs:ignore
				$reason = 'I only needed the plugin for a short period';
			} elseif ( '2' == $reason_code ) { //phpcs:ignore
				$reason = 'I found a better plugin';
			} elseif ( '3' == $reason_code ) { //phpcs:ignore
				$reason = 'The plugin broke my site';
			} elseif ( '4' == $reason_code ) { //phpcs:ignore
				$reason = 'The plugin suddenly stopped working';
			} elseif ( '5' == $reason_code ) { //phpcs:ignore
				$reason = 'I no longer need the plugin';
			} elseif ( '6' == $reason_code ) { //phpcs:ignore
				$reason = 'It\'s a temporary deactivation. I\'m just debugging an issue.';
			} elseif ( '7' == $reason_code ) { //phpcs:ignore
				$reason = 'Other';
			}
			$fields = array(
				'email'             => sanitize_email( $email ),
				'website'           => esc_url( get_site_url() ),
				'action'            => 'Deactivate',
				'reason'            => $reason,
				'reason_detail'     => $reason_detail,
				'blog_language'     => sanitize_text_field( get_bloginfo( 'language' ) ),
				'wordpress_version' => sanitize_text_field( get_bloginfo( 'version' ) ),
				'php_version'       => sanitize_text_field( PHP_VERSION ),
				'plugin_version'    => sanitize_text_field( defined( 'LOGINPRESS_VERSION' ) ? LOGINPRESS_VERSION : '' ),
				'plugin_name'       => 'LoginPress Free',
			);

			$response = wp_remote_post(
				( defined( 'LOGINPRESS_FEEDBACK_SERVER' ) ? LOGINPRESS_FEEDBACK_SERVER : '' ),
				array(
					'method'      => 'POST',
					'httpversion' => '1.0',
					'blocking'    => false,
					'headers'     => array(),
					'body'        => $fields,
				)
			);

			wp_die();
		}

		/**
		 * Handle opt-out.
		 *
		 * @since 1.0.15
		 * @version 3.0.0
		 * @return void
		 */
		public function optout_yes() {
			if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'loginpress-optout-nonce', 'optout_nonce' ) ) {
				wp_die( '<p>' . esc_html__( 'Sorry, you are not allowed to edit this item.', 'loginpress' ) . '</p>', 403 );
			}

			// Get the current option and decode it as an associative array.
			$sdk_data = json_decode( get_option( 'wpb_sdk_loginpress' ), true );

			// If there is no current option, initialize an empty array.
			if ( ! $sdk_data ) {
				$sdk_data = array();
			}

			$setting_name  = isset( $_POST['setting_name'] ) ? sanitize_key( wp_unslash( $_POST['setting_name'] ) ) : '';  // e.g., communication, diagnostic_info, extensions.
			$setting_value = isset( $_POST['setting_value'] ) ? sanitize_text_field( wp_unslash( $_POST['setting_value'] ) ) : '';  // The new value to be updated.

			// Update the specific setting in the array.
			$sdk_data[ $setting_name ] = $setting_value;

			// Encode the array back into a JSON string and update the option.
			update_option( 'wpb_sdk_loginpress', wp_json_encode( $sdk_data ) );

			wp_die();
		}

		/**
		 * Handle presets.
		 *
		 * @since 1.1.22
		 * @return void
		 */
		public static function presets() {
			check_ajax_referer( 'loginpress-preset-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'No cheating, huh!', 'loginpress' ) );
			}

			$selected_preset = get_option( 'customize_presets_settings', true );

			if ( 'default1' === $selected_preset ) {
				require_once (defined( 'LOGINPRESS_ROOT_PATH' ) ? LOGINPRESS_ROOT_PATH : '') . 'classes/customizer/css/themes/default-1.php'; //phpcs:ignore
				if ( function_exists( 'first_presets' ) ) {
					echo wp_kses_post( first_presets() );
				}
			} elseif ( 'minimalist' === $selected_preset ) {
				require_once (defined( 'LOGINPRESS_ROOT_PATH' ) ? LOGINPRESS_ROOT_PATH : '') . 'classes/customizer/css/themes/free-minimalist.php'; //phpcs:ignore
				if ( function_exists( 'free_minimalist_presets' ) ) {
					echo wp_kses_post( free_minimalist_presets() );
				}
			} else {
				do_action( 'loginpress_add_pro_theme', $selected_preset );
			}
			wp_die();
		}

		/**
		 * Get video attachment URL.
		 *
		 * @since 1.1.22
		 * @version 3.0.0
		 * @return void
		 */
		public static function video_url() {
			check_ajax_referer( 'loginpress-attachment-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'No cheating, huh!', 'loginpress' ) );
			}

			$attachment_id  = isset( $_POST['src'] ) ? absint( $_POST['src'] ) : 0;
			$attachment_url = wp_get_attachment_url( $attachment_id );
			if ( false !== $attachment_url ) {
				echo esc_url( $attachment_url );
			}

			wp_die();
		}

		/**
		 * YouTube Video URL.
		 *
		 * @since 1.1.22
		 * @return void
		 */
		public static function youtube_video_url() {
			check_ajax_referer( 'loginpress-attachment-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'No cheating, huh!', 'loginpress' ) );
			}
			$video_id = isset( $_POST['src'] ) ? sanitize_text_field( wp_unslash( $_POST['src'] ) ) : '';
			$url      = 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';
			$response = wp_remote_get( $url );
			if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {
				echo esc_html( $video_id );
			} else {
				echo esc_html( '0' );
			}
			wp_die();
		}
	}

endif;
new LoginPress_AJAX();
