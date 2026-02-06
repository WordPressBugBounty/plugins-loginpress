<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress Settings Class.
 *
 * This class handles all settings-related functionality for the LoginPress plugin.
 * This includes admin pages, settings API integration, and user interface.
 *
 * @package LoginPress
 * @since 1.0.9
 * @version 3.0.0
 */

if ( ! class_exists( 'LoginPress_Settings' ) ) :
	require_once ( defined( 'LOGINPRESS_DIR_PATH' ) ? LOGINPRESS_DIR_PATH : '' ) . 'classes/traits/loginpress-settings-trait.php'; //phpcs:ignore
	/**
	 * LoginPress Settings Class.
	 *
	 * Handles all settings-related functionality for the LoginPress plugin.
	 * This includes admin pages, settings API integration, and user interface.
	 *
	 * @since 1.0.9
	 * @version 3.0.0
	 */
	class LoginPress_Settings {
		use LoginPress_Settings_Trait;

		/**
		 * Settings API instance for handling form fields and validation.
		 *
		 * @var LoginPress_Settings_API Instance of the settings API class.
		 */
		private $settings_api;

		/**
		 * Class constructor.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function __construct() {

			require_once ( defined( 'LOGINPRESS_ROOT_PATH' ) ? LOGINPRESS_ROOT_PATH : '' ) . '/classes/class-loginpress-settings-api.php'; //phpcs:ignore
			$this->settings_api = new LoginPress_Settings_API();

			add_action( 'admin_init', array( $this, 'loginpress_setting_init' ) );
			add_action( 'admin_menu', array( $this, 'loginpress_setting_menu' ) );
			add_action( 'admin_notices', array( $this, 'loginpress_show_custom_dashboard_popup' ) );
			add_action( 'wp_ajax_dismiss_notification', array( $this, 'loginpress_handle_notification_dismiss' ) );
		}

		/**
		 * Main settings page content.
		 *
		 * @since 1.0.19
		 * @version 3.0.0
		 * @return void
		 */
		public function plugin_page() {
			// Output header first.
			$this::loginpress_admin_page_header();

			// Start wrap div.
			echo '<div class="wrap">';

			// For React version (both free and pro >= 6.0).
			if ( version_compare( defined( 'LOGINPRESS_VERSION' ) ? LOGINPRESS_VERSION : '1.0.0', '6.0.0', '>=' ) && ( ! class_exists( 'LoginPress_Pro' ) || version_compare( defined( 'LOGINPRESS_PRO_VERSION' ) ? LOGINPRESS_PRO_VERSION : '1.0.0', '6.0.0', '>=' ) ) ) {
				// Output notices container at the top.
				echo '<div id="loginpress-notices-container"></div>';

				// Output React root.
				echo '<div id="loginpress-settings-root">';
				echo '<div className="loginpress-main-wrapper skeleton-layout">';
				// Your skeleton loader HTML.
				echo '</div>';
				echo '</div>';

				// Add JavaScript to move notices into our container.
				echo '<script>
				document.addEventListener("DOMContentLoaded", function() {
					// Get all notices that WordPress outputs.
					var notices = document.querySelectorAll(".notice:not(.loginpress-notice)");
					var container = document.getElementById("loginpress-notices-container");
					
					// Move each notice to our container.
					notices.forEach(function(notice) {
						container.appendChild(notice);
					});
				});
				</script>';
			} else {
				// For legacy PHP version.
				// Video popup if needed.
				if ( version_compare( defined( 'LOGINPRESS_VERSION' ) ? LOGINPRESS_VERSION : '1.0.0', '6.0.0', '<' ) || ( class_exists( 'LoginPress_Pro' ) && version_compare( defined( 'LOGINPRESS_PRO_VERSION' ) ? LOGINPRESS_PRO_VERSION : '1.0.0', '6.0.0', '<' ) ) ) {
					echo '<div class="loginpress-video-popup">
						<div class="loginpress-cross"></div>
						<div class="loginpress-video-overlay"></div>
						<div class="loginpress-video-frame">
							<iframe id="loginpress-video" allow="autoplay" frameborder="0" title="' . esc_attr__( 'LoginPress Video', 'loginpress' ) . '"></iframe>
						</div>
					</div>';
				}

				echo '<h2 class="loginpress-settings-heading">';
				esc_html_e( 'LoginPress - Rebranding your boring WordPress Login pages', 'loginpress' );
				echo '</h2>';

				// Legacy settings.
				echo '<div class="loginpress-admin-setting">';
				$this->settings_api->show_navigation();
				$this->settings_api->show_forms();
				echo '</div>';
			}

			// Close wrap div.
			echo '</div>';
		}

		/**
		 * Help page content.
		 *
		 * @since 1.0.19
		 * @version 3.0.8
		 * @return void
		 */
		public function loginpress_help_page() {

			self::loginpress_admin_page_header();
			require_once ( defined( 'LOGINPRESS_DIR_PATH' ) ? LOGINPRESS_DIR_PATH : '' ) . 'classes/class-loginpress-log-info.php'; //phpcs:ignore

			$html  = '<div class="loginpress-help-page">';
			$html .= '<h2>' . esc_html__( 'Help & Troubleshooting', 'loginpress' ) . '</h2>';
			$html .= '<p>';
			$html .= sprintf(
				// translators: Plugin support forum.
				esc_html__( 'For assistance with the free plugin, visit the %1$s plugin support forums%2$s.', 'loginpress' ),
				'<a href="' . esc_url( 'https://wordpress.org/support/plugin/loginpress' ) . '" target="_blank" rel="noopener noreferrer">',
				'</a>'
			);
			$html .= '<br />';

			if ( ! class_exists( 'LoginPress_Pro' ) ) {
				$html .= sprintf(
					// translators: Upgrade to Pro.
					esc_html__( 'For premium features, add-ons, or priority email support, %1$s upgrade to pro%2$s.', 'loginpress' ),
					'<a href="' . esc_url( 'https://loginpress.pro/pricing/?utm_source=loginpress-lite&utm_medium=help-page&utm_campaign=pro-upgrade&utm_content=upgrade-text-link' ) . '" target="_blank" rel="noopener noreferrer">',
					'</a>'
				);
			} else {
				$html .= sprintf(
					// translators: Submit query through support page.
					esc_html__( 'For premium features, add-ons, or priority email support, submit your query through %1$sour support page%2$s!', 'loginpress' ),
					'<a href="' . esc_url( 'https://loginpress.pro/contact/' ) . '" target="_blank" rel="noopener noreferrer">',
					'</a>'
				);
			}

			$html .= '<br />';
			$html .= sprintf(
				// translators: Issue submission form.
				esc_html__( 'If you\'ve found a bug or have a feature request, let us know via our %1$sissue submission form%2$s!', 'loginpress' ),
				'<a href="' . esc_url( 'https://loginpress.pro/contact/' ) . '" target="_blank" rel="noopener noreferrer">',
				'</a>'
			);
			$html .= '</p>';
			$html .= '<pre><textarea rows="25" cols="75" readonly="readonly">';
			$html .= esc_html( LoginPress_Log_Info::get_sysinfo() );
			$html .= '</textarea></pre>';
			$html .= '<button type="button" class="button loginpress-log-file"><span class="dashicons dashicons-download"></span> ' . esc_html__( 'Download Log File', 'loginpress' ) . '</button>';
			$html .= '<span class="log-file-sniper"><img src="' . esc_url( admin_url( 'images/wpspin_light.gif' ) ) . '" alt="' . esc_attr__( 'Loading', 'loginpress' ) . '" /></span>';
			$html .= '<span class="log-file-text">' . esc_html__( 'LoginPress Log File Downloaded Successfully!', 'loginpress' ) . '</span>';
			$html .= '</div>';

			// Output the HTML using wp_kses_post for proper escaping.
			echo wp_kses_post( $html );
		}

		/**
		 * Import/Export page content.
		 *
		 * @since 1.0.19
		 * @version 3.0.0
		 * @return void
		 */
		public function loginpress_import_export_page() {

			self::loginpress_admin_page_header();
			require_once ( defined( 'LOGINPRESS_DIR_PATH' ) ? LOGINPRESS_DIR_PATH : '' ) . 'include/loginpress-import-export.php'; //phpcs:ignore
		}

		/**
		 * Addons page content.
		 *
		 * @since 1.0.19
		 * @version 3.0.0
		 * @return void
		 */
		public function loginpress_addons_page() {

			self::loginpress_admin_page_header();
			$active_plugins = get_option( 'active_plugins' );

			if ( in_array( 'loginpress-pro/loginpress-pro.php', $active_plugins, true ) && version_compare( defined( 'LOGINPRESS_PRO_VERSION' ) ? LOGINPRESS_PRO_VERSION : '1.0.0', '3.0.0', '<' ) ) {
				require_once ( defined( 'LOGINPRESS_DIR_PATH' ) ? LOGINPRESS_DIR_PATH : '' ) . 'classes/class-loginpress-deprecated-addons.php'; //phpcs:ignore
			} else {
				require_once ( defined( 'LOGINPRESS_DIR_PATH' ) ? LOGINPRESS_DIR_PATH : '' ) . 'classes/class-loginpress-addons.php'; //phpcs:ignore
			}
			$loginpress_addons = new LoginPress_Addons();
			/**
			 * Ignore next line for PHPStan analysis.
			 *
			 * @phpstan-ignore-next-line
			 */
			$loginpress_addons->addons_array_construct();
			$loginpress_addons->addon_html();
		}

		/**
		 * Get pages list.
		 *
		 * @since 1.0.19
		 * @return array<int, string> Page names with key value pairs.
		 */
		public function get_pages() {
			$pages         = get_pages();
			$pages_options = array();
			if ( $pages ) {
				foreach ( $pages as $page ) {
					$pages_options[ $page->ID ] = sanitize_text_field( $page->post_title );
				}
			}

			return $pages_options;
		}

		/**
		 * Merge uninstall LoginPress field with array of elements.
		 *
		 * @param  array<string, mixed> $fields_list Array of existing fields.
		 * @since 1.1.9
		 * @return array<int|string, mixed> Merged array with uninstall fields.
		 */
		public function loginpress_uninstallation_filed( $fields_list ) {

			$loginpress_page_check = '';
			if ( is_multisite() ) {
				$loginpress_page_check = esc_html__( 'and LoginPress page', 'loginpress' );
			}

			$loginpress_db_check = array(
				array(
					'name'  => 'loginpress_uninstall',
					'label' => __( 'Remove Settings On Uninstall', 'loginpress' ),
					'desc'  => sprintf(
						// translators: Remove Settings on Uninstall.
						esc_html__( 'Enable to remove all custom settings made %1$s by LoginPress upon uninstall.', 'loginpress' ),
						$loginpress_page_check
					),
					'type'  => 'checkbox',
				),
			);

			if ( class_exists( 'LoginPress_Pro' ) ) {
				// Add the Pro uninstall setting.
				$loginpress_db_check[] = array(
					'name'  => 'loginpress_pro_uninstall',
					'label' => __( 'Remove Settings On Uninstall For Pro', 'loginpress' ),
					'desc'  => sprintf(
						// translators: Remove Settings on Uninstall For Pro.
						esc_html__( 'Enable to remove all custom settings made %1$s by LoginPress-Pro upon uninstall.', 'loginpress' ),
						$loginpress_page_check
					),
					'type'  => 'checkbox',
				);
			}

			return array_merge( $fields_list, $loginpress_db_check ); // merge an array and return.
		}

		/**
		 * Uninstallation tool for multisite compatibility.
		 *
		 * Pass return true in loginpress_multisite_uninstallation_tool filter's callback .
		 * for enable uninstallation control on each site.
		 *
		 * @param  array<string, mixed> $free_fields Array of free fields.
		 * @since 1.1.9
		 * @return array<int|string, mixed> Processed fields array.
		 */
		public function loginpress_uninstallation_tool( $free_fields ) {

			if ( is_multisite() && ! apply_filters( 'loginpress_multisite_uninstallation_tool', false ) ) {
				if ( get_current_blog_id() === 1 ) {
					$free_fields = $this->loginpress_uninstallation_filed( $free_fields );
				}
			} else {
				$free_fields = $this->loginpress_uninstallation_filed( $free_fields );
			}

			return $free_fields;
		}

		/**
		 * Filter to increase days for force reset password in settings.
		 *
		 * @param  int $days Number of days for password reset time limit.
		 * @since 3.0.0
		 * @return int Modified days for password reset time limit.
		 */
		public function change_force_time_limit( $days ) {

			$force_reset_duration = absint( apply_filters( 'increase_force_time_limit', $days ) );
			$force_reset_duration = ( 0 === $force_reset_duration ) ? 182 : $force_reset_duration;
			return $force_reset_duration;
		}

		/**
		 * Header HTML.
		 * Call on LoginPress pages at dashboard.
		 *
		 * @since 3.0.0
		 * @version 3.0.8
		 * @return void
		 */
		public static function loginpress_admin_page_header() {

			if ( ! has_action( 'loginpress_pro_add_template' ) ) {
				$button_text = '<a href="' . esc_url( 'https://loginpress.pro/pricing/?utm_source=loginpress-lite&utm_medium=top-banner&utm_campaign=pro-upgrade&utm_content=Upgrade+to+Pro+CTA' ) . '" class="loginpress-pro-cta" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-star-filled"></span>' . sprintf(
				// translators: Upgrade to Pro link.
					esc_html__( 'Upgrade%1$s to Pro%2$s', 'loginpress' ),
					'<span>',
					'</span>'
				) . '</a>';
				$documentation_link = 'https://loginpress.pro/documentation/?utm_source=loginpress-lite&utm_medium=top-banner&utm_campaign=pro-upgrade&utm_content=Documentation+CTA';
			} else {
				$button_text        = '<a href="' . esc_url( 'https://loginpress.pro/contact?utm_source=loginpress-pro&utm_medium=top-banner&utm_campaign=customer-support&utm_content=Support+CTA' ) . '" class="loginpress-pro-cta" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support', 'loginpress' ) . '</a>';
				$documentation_link = 'https://loginpress.pro/documentation?utm_source=loginpress-pro&utm_medium=top-banner&utm_campaign=user-guide&utm_content=Documentation+CTA';
			}

			$html = '<div class="loginpress-header-wrapper">
				<div class="loginpress-header-container">
					<div class="loginpress-header-logo">
						<a href="' . esc_url( 'https://loginpress.pro/pricing/?utm_source=loginpress-lite&utm_medium=top-links&utm_campaign=pro-upgrade' ) . '" target="_blank" rel="noopener noreferrer"><img src="' . esc_url( ( defined( 'LOGINPRESS_DIR_URL' ) ? LOGINPRESS_DIR_URL : '' ) . 'img/loginpress-logo.svg' ) . '" alt="' . esc_attr__( 'LoginPress Logo', 'loginpress' ) . '"></a>
					</div>
					<div class="loginpress-header-cta">
						' . $button_text . '
						<a href="' . esc_url( $documentation_link ) . '" class="loginpress-documentation" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Documentation', 'loginpress' ) . '</a>
					</div>
				</div>
			</div>';

			if ( class_exists( 'LoginPress_Pro' ) ) {
				if ( ! LoginPress_Pro::is_activated() ) {
					$html .= '<div class="wrap">
						<div class="loginpress-license-notice">
							<strong>' . esc_html__( 'Please activate your license key.', 'loginpress' ) . '</strong> 
							' . esc_html__( 'Validating the license key is mandatory to use automatic updates and receive plugin support.', 'loginpress' ) . '
						</div>
					</div>';
				}
			}

			// Output the HTML using wp_kses_post for proper escaping.
			echo wp_kses_post( $html );
		}

		/**
		 * Outputs a dismissible notification for the free version of LoginPress.
		 * that encourages users to upgrade to the Pro version.
		 *
		 * This function is only used in the free version of LoginPress.
		 *
		 * @version 6.0.0
		 * @return void
		 */
		public function loginpress_show_custom_dashboard_popup() {

			$loginpress_page = false;
			if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Page parameter for display purposes.
				$page            = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Page parameter for display purposes.
				$loginpress_page = ( 0 === strpos( $page, 'loginpress' ) );
			}

			if ( class_exists( 'LoginPress_Pro' ) && $loginpress_page && version_compare( defined( 'LOGINPRESS_PRO_VERSION' ) ? LOGINPRESS_PRO_VERSION : '1.0.0', '6.0.0', '<' ) ) {
				$update_url = admin_url( 'plugins.php?s=LoginPress+Pro' );
				?>
				<div class="loginpress-notification-bar" >
					<p><?php echo esc_html__( 'A new version of LoginPress Pro is available.', 'loginpress' ); ?> <a href="<?php echo esc_url( $update_url ); ?>" target="_self">
					<?php echo esc_html__( 'Update now', 'loginpress' ); ?></a><?php echo esc_html__( ' to access the latest features.', 'loginpress' ); ?></p>
				</div>
				<?php
			}

			// Check if the message should be shown.
			$dismissed_until = get_transient( 'loginpress_pro_pop_up' );

			if ( $dismissed_until || class_exists( 'LoginPress_Pro' ) ) {
				return; // Do not show the notification if it's still dismissed.
			}
			if ( $loginpress_page ) {
				// Output the dismissible notification.
				$html = '<div class="loginpress-notification-bar">
					<p>' . esc_html__( 'You\'re using LoginPress Free. To unlock more features consider', 'loginpress' ) . ' <a href="' . esc_url( 'https://loginpress.pro/pricing/?utm_source=loginpress-lite&utm_medium=top-popup&utm_campaign=pro-upgrade&utm_content=upgrading+to+pro' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'upgrading to LoginPress Pro', 'loginpress' ) . '</a></p>
					<span class="loginpress-notification-close"></span>
				</div>';

				// Output the HTML using wp_kses_post for proper escaping.
				echo wp_kses_post( $html );
			}
		}

		/**
		 * Handle notification dismiss AJAX request.
		 *
		 * @since 1.0.19
		 * @return void
		 */
		public function loginpress_handle_notification_dismiss() {
			// Verify nonce for security.
			check_ajax_referer( 'loginpress-log-nonce', 'security' );

			// Check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'No cheating, huh!', 'loginpress' ) );
			}

			// Handle AJAX request for dismissal.
			set_transient( 'loginpress_pro_pop_up', true, 0 );
			wp_send_json_success();
		}
	}
endif;
