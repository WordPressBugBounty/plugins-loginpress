<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: LoginPress
 * Plugin URI: https://loginpress.pro?utm_source=loginpress-lite&utm_medium=plugin-header&utm_campaign=pro-upgrade&utm_content=plugin-uri
 * Description: LoginPress is the best <code>wp-login</code> Login Page Customizer plugin by <a href="https://wpbrigade.com/?utm_source=loginpress-lite&utm_medium=plugins&utm_campaign=wpbrigade-home&utm_content=WPBrigade-text-link">WPBrigade</a> which allows you to completely change the layout of login, register and forgot password forms.
 * Version: 6.1.2
 * Author: LoginPress
 * Author URI: https://loginpress.pro?utm_source=loginpress-lite&utm_medium=plugin-header&utm_campaign=pro-upgrade&utm_content=author-uri
 * Text Domain: loginpress
 * Domain Path: /languages
 * Requires at least: 4.0
 * Tested up to: 6.9
 * License: GPLv2 or later
 * GitHub Plugin URI: https://github.com/WPBrigade/loginpress
 *
 * @package loginpress
 * @category Core
 * @author WPBrigade
 *
 * phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed
 */

// Define constants for PHPStan.
if ( ! defined( 'LOGINPRESS_PLUGIN_BASENAME' ) ) {
	define( 'LOGINPRESS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'LOGINPRESS_DIR_PATH' ) ) {
	define( 'LOGINPRESS_DIR_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'LOGINPRESS_DIR_URL' ) ) {
	define( 'LOGINPRESS_DIR_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'LOGINPRESS_ROOT_PATH' ) ) {
	define( 'LOGINPRESS_ROOT_PATH', __DIR__ . '/' );
}
if ( ! defined( 'LOGINPRESS_ROOT_FILE' ) ) {
	define( 'LOGINPRESS_ROOT_FILE', __FILE__ );
}
if ( ! defined( 'LOGINPRESS_VERSION' ) ) {
	define( 'LOGINPRESS_VERSION', '6.1.2' );
}
if ( ! defined( 'LOGINPRESS_FEEDBACK_SERVER' ) ) {
	define( 'LOGINPRESS_FEEDBACK_SERVER', 'https://wpbrigade.com/' );
}
if ( ! defined( 'LOGINPRESS_REST_NAMESPACE' ) ) {
	define( 'LOGINPRESS_REST_NAMESPACE', 'loginpress/v1' );
}


if ( ! function_exists( 'loginpress_wpb53407382' ) ) {
	/**
	 * Create a helper function for easy SDK access.
	 *
	 * @return mixed
	 */
	function loginpress_wpb53407382() {
		global $loginpress_wpb53407382;

		if ( ! isset( $loginpress_wpb53407382 ) ) {
			/**
			 * Include Telemetry SDK.
			 *
			 * @var mixed $loginpress_wpb53407382
			 * @phpstan-ignore-next-line
		 */
			require_once __DIR__ . '/lib/wpb-sdk/start.php';

			/**
			 * Initialize WPB SDK.
			 *
			 * @phpstan-ignore-next-line
			 */
			$loginpress_wpb53407382 = wpb_dynamic_init(
				array(
					'id'             => '6',
					'slug'           => 'loginpress',
					'type'           => 'plugin',
					'public_key'     => '1|4aOA8EuyIN4pi2miMvC23LLpnHbBZFNki9R9pVmwd673d3c8',
					'secret_key'     => 'sk_b36c525848fee035',
					'is_premium'     => false,
					'has_addons'     => false,
					'has_paid_plans' => false,
					'menu'           => array(
						'slug'    => 'loginpress',
						'account' => false,
						'support' => false,
					),
					'settings'       => array(
						'loginpress_customization'       => '',
						'loginpress_setting'             => '',
						'loginpress_addon_active_time'   => '',
						'loginpress_addon_dismiss_1'     => '',
						'loginpress_review_dismiss'      => '',
						'loginpress_active_time'         => '',
						'_loginpress_optin'              => '',
						'loginpress_friday_sale_active_time' => '',
						'loginpress_friday_sale_dismiss' => '',
						'loginpress_friday_21_sale_dismiss' => '',
					),
				)
			);
		}

		return $loginpress_wpb53407382;
	}

	// Init Telemetry.
	loginpress_wpb53407382();
	// Signal that SDK was initiated.
	do_action( 'loginpress_wpb53407382_loaded' );
}

if ( ! class_exists( 'LoginPress' ) ) :
	// REST endpoints moved into a trait to keep main file slimmer.
	require_once plugin_dir_path( __FILE__ ) . 'classes/traits/loginpress-rest-trait.php';

	/**
	 * LoginPress Main Class
	 *
	 * Main LoginPress plugin class.
	 *
	 * @package   LoginPress
	 * @since 1.0.0
	 * @version 6.1.1
	 */
	final class LoginPress {
		use LoginPress_Rest_Trait;

		/**
		 * Plugin version number.
		 *
		 * @var string Current version of the LoginPress plugin.
		 */
		public $version = '6.1.1';

		/**
		 * The single instance of the LoginPress class.
		 *
		 * @var LoginPress|null Singleton instance of the LoginPress class.
		 * @since 1.0.0
		 *
		 * phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
		 */
		protected static ?LoginPress $_instance = null;

		/**
		 * Session data storage.
		 *
		 * @var mixed WordPress session data or null if not set.
		 */
		public $session = null;

		/**
		 * Database query object.
		 *
		 * @var mixed WordPress database query object or null if not set.
		 */
		public $query = null;

		/**
		 * Countries data for forms.
		 *
		 * @var mixed Array of countries data or null if not loaded.
		 */
		public $countries = null;

		/**
		 * Class Constructor.
		 *
		 * @since 1.0.0
		 * @version 6.0.0
		 * @return void
		 */
		public function __construct() {
			$this->define_constants();
			$this->includes();
			$this->_hooks();
		}

		/**
		 * Define LoginPress Constants.
		 *
		 * @return void
		 */
		private function define_constants() {

			$this->define( 'LOGINPRESS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			$this->define( 'LOGINPRESS_DIR_PATH', plugin_dir_path( __FILE__ ) );
			$this->define( 'LOGINPRESS_DIR_URL', plugin_dir_url( __FILE__ ) );
			$this->define( 'LOGINPRESS_ROOT_PATH', __DIR__ . '/' );
			$this->define( 'LOGINPRESS_ROOT_FILE', __FILE__ );
			$this->define( 'LOGINPRESS_VERSION', $this->version );
			$this->define( 'LOGINPRESS_FEEDBACK_SERVER', 'https://wpbrigade.com/' );
			$this->define( 'LOGINPRESS_REST_NAMESPACE', 'loginpress/v1' );
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @since 1.0.0
		 * @version 3.0.0
		 * @return void
		 */
		public function includes() {

			include_once LOGINPRESS_DIR_PATH . 'include/class-loginpress-compatibility.php';
			include_once LOGINPRESS_DIR_PATH . 'include/class-loginpress-domains.php';
			include_once LOGINPRESS_DIR_PATH . 'classes/customizer/class-loginpress-customizer.php';
			include_once LOGINPRESS_DIR_PATH . 'classes/class-loginpress-setup.php';
			include_once LOGINPRESS_DIR_PATH . 'classes/class-loginpress-ajax.php';
			include_once LOGINPRESS_DIR_PATH . 'classes/class-loginpress-developer-hooks.php';
			include_once LOGINPRESS_DIR_PATH . 'classes/class-loginpress-notifications.php';
			include_once LOGINPRESS_DIR_PATH . 'include/loginpress-utilities.php';
			if ( is_multisite() ) {
				require_once LOGINPRESS_DIR_PATH . 'include/class-loginpress-theme-template.php';
			}

			$loginpress_setting = get_option( 'loginpress_setting' );

			$loginpress_privacy_policy = isset( $loginpress_setting['enable_privacy_policy'] ) ? $loginpress_setting['enable_privacy_policy'] : 'off';
			if ( 'off' !== $loginpress_privacy_policy ) {
				include_once LOGINPRESS_DIR_PATH . 'include/privacy-policy.php';
			}

			$login_with_email = isset( $loginpress_setting['login_order'] ) ? $loginpress_setting['login_order'] : 'default';
			if ( 'default' !== $login_with_email ) {
				include_once LOGINPRESS_DIR_PATH . 'classes/class-loginpress-login-order.php';
				new LoginPress_Login_Order();
			}

			$enable_reg_pass_field = isset( $loginpress_setting['enable_reg_pass_field'] ) ? $loginpress_setting['enable_reg_pass_field'] : 'off';
			if ( 'off' !== $enable_reg_pass_field ) {
				include_once LOGINPRESS_DIR_PATH . 'classes/class-loginpress-custom-password.php';
				new LoginPress_Custom_Password();
				include_once LOGINPRESS_DIR_PATH . 'classes/class-loginpress-password-strength.php';
				new LoginPress_Password_Strength();
			}
			$loginpress_password_reset_time_limit = isset( $loginpress_setting['loginpress_password_reset_time_limit'] ) ? $loginpress_setting['loginpress_password_reset_time_limit'] : 'off';
			if ( 'off' !== $loginpress_password_reset_time_limit ) {
				include_once LOGINPRESS_DIR_PATH . 'classes/class-loginpress-force-password-reset.php';
				new LoginPress_Force_Password_Reset();
			}
		}

		/**
		 * Hook into actions and filters
		 *
		 * @since  1.0.0
		 * @version 3.0.0
		 * @return void
		 *
		 * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
		 */
		private function _hooks() {
			add_action( 'rest_api_init', array( $this, 'loginpress_register_routes' ) );
			add_action( 'admin_menu', array( $this, 'register_options_page' ) );
			add_action( 'init', array( $this, 'textdomain' ) );
			add_filter( 'plugin_row_meta', array( $this, '_row_meta' ), 10, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, '_admin_scripts' ) );
			add_action( 'admin_footer', array( $this, 'add_deactivate_modal' ) );
			add_filter( 'plugin_action_links', array( $this, 'loginpress_action_links' ), 10, 2 );
			add_action( 'admin_init', array( $this, 'redirect_optin' ) );
			add_filter( 'auth_cookie_expiration', array( $this, '_change_auth_cookie_expiration' ), 10, 3 );
			add_action( 'wp_wpb_sdk_after_uninstall', array( $this, 'plugin_uninstallation' ) );
			if ( is_multisite() ) {
				add_action( 'admin_init', array( $this, 'redirect_loginpress_edit_page' ) );
				add_action( 'admin_init', array( $this, 'check_loginpress_page' ) );
				// Makes sure the plugin is defined before trying to use it.
				if ( ! function_exists( 'is_plugin_active_for_network' ) || ! function_exists( 'is_plugin_active' ) ) {
					/**
					 * Include plugin functions.
					 *
					 * @phpstan-ignore-next-line
					 */
					require_once ABSPATH . '/wp-admin/includes/plugin.php';
					if ( is_plugin_active_for_network( 'wordpress-seo/wp-seo.php' ) ) {
						/**
						 * This filters the ID of the page/post which you want to remove from the sitemap XML.
						 *
						 * @since 1.5.14
						 *
						 * @documentation https://developer.yoast.com/features/xml-sitemaps/api/
						 */
						add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', array( $this, 'loginpress_exclude_from_sitemap' ) );
					}
				}
			}
		}

		/**
		 * Callback function to exclude LoginPress page from sitemap.
		 *
		 * @return array<int>|false Exclude page/s or post/s.
		 * @since 1.5.14
		 * @version 1.6.3
		 */
		public function loginpress_exclude_from_sitemap() {

			$page = get_page_by_path( 'loginpress' );
			if ( is_object( $page ) ) {
				// This is used as a filter callback, so we need to return the array
				// even though the action expects void return.
				return array( $page->ID );
			}
			return false;
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
				} elseif ( ! get_option( '_loginpress_optin' ) && isset( $_GET['page'] ) && ( 'loginpress-settings' === $_GET['page'] || 'loginpress' === $_GET['page'] || 'abw' === $_GET['page'] ) ) {
					/**
					 * XSS Attack vector found and fixed.
					 *
					 * @since 1.5.11
					 */
					$page_redirect = 'loginpress' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ? 'loginpress' : 'loginpress-settings';
					wp_safe_redirect( admin_url( 'admin.php?page=loginpress-optin&redirect-page=' . $page_redirect ) );
					exit;
				} elseif ( get_option( '_loginpress_optin' ) && ( get_option( '_loginpress_optin' ) === 'yes' ) && isset( $_GET['page'] ) && 'loginpress-optin' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
					wp_safe_redirect( admin_url( 'admin.php?page=loginpress-settings' ) );
					exit;
				}
			}
		}

		/**
		 * Main Instance.
		 *
		 * @since 1.0.0
		 * @static
		 * @see loginpress_loader()
		 * @return LoginPress instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Load Languages.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function textdomain() {

			$plugin_dir = dirname( plugin_basename( __FILE__ ) );
			load_plugin_textdomain( 'loginpress', false, $plugin_dir . '/languages/' );
		}

		/**
		 * Define constant if not already set.
		 *
		 * @param string      $name  The constant name to define.
		 * @param string|bool $value The constant value to set.
		 * @return void
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Init WPBrigade when WordPress Initializes.
		 *
		 * @return void
		 */
		public function init() {
			// Before init action.
		}

		/**
		 * Create LoginPress Page Template.
		 *
		 * @since 1.1.3
		 * @return void
		 */
		public function check_loginpress_page() {

			// Retrieve the LoginPress admin page option, that was created during the activation process.
			$option = $this->get_loginpress_page();

			include LOGINPRESS_DIR_PATH . 'include/class-loginpress-page-create.php';
			// Retrieve the status of the page, if the option is available.
			if ( $option ) {
				$page   = get_post( $option );
				$status = $page ? $page->post_status : null;
			} else {
				$status = null;
			}

			// Check the status of the page. Let's fix it, if the page is missing or in the trash.
			if ( empty( $status ) || 'trash' === $status ) {
				new LoginPress_Page_Create();
			}
		}

		/**
		 * Function for redirect the LoginPress page on editing.
		 *
		 * @since 1.1.3
		 * @return void
		 */
		public function redirect_loginpress_edit_page() {
			global $pagenow;

			$page = $this->get_loginpress_page();

			if ( ! $page ) {
				return;
			}

			$page_url = get_permalink( $page );
			$page_id  = $page->ID;

			// Generate the redirect url.
			$url = add_query_arg(
				array(
					'autofocus[section]' => 'loginpress_panel',
					'url'                => rawurlencode( $page_url ? $page_url : '' ),
				),
				admin_url( 'customize.php' )
			);

			// Check current admin page.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for admin page check.
			if ( 'post.php' === $pagenow && isset( $_GET['post'] ) && absint( $_GET['post'] ) === $page_id ) {
				wp_safe_redirect( $url );
			}
		}

		/**
		 * Add new page in Appearance to customize Login Page.
		 *
		 * @version 3.0.0
		 * @return void
		 */
		public function register_options_page() {

			add_submenu_page( 'LoginPress', __( 'Activate', 'loginpress' ), __( 'Activate', 'loginpress' ), 'manage_options', 'loginpress-optin', array( $this, 'render_optin' ) );

			add_theme_page( __( 'LoginPress', 'loginpress' ), __( 'LoginPress', 'loginpress' ), 'manage_options', 'abw', '__return_null' );
		}


		/**
		 * Show Opt-in Page.
		 *
		 * @since 1.0.15
		 * @return void
		 */
		public function render_optin() {
			include LOGINPRESS_DIR_PATH . 'include/loginpress-optin-form.php';
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
		 *
		 * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
		 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		 */
		public function _change_auth_cookie_expiration( $expiration, $user_id, $remember ) {
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
		 * Load JS or CSS files at admin side and enqueue them.
		 *
		 * @param string $hook The current admin page hook identifier.
		 * @return void
		 * @version 3.0.0
		 *
		 * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
		 */
		public function _admin_scripts( $hook ) {
			if ( 'toplevel_page_loginpress-settings' === $hook ) {
				wp_enqueue_script( 'youtube-api', 'https://www.youtube.com/iframe_api', array(), LOGINPRESS_VERSION, true );

				$js_code = '
			var ytPlayers = {};

			function onYouTubeIframeAPIReady() {
				var iframes = document.querySelectorAll("iframe.loginPress-feature-video");

				Array.prototype.forEach.call(iframes, function(iframe) {
					// Assign a unique ID if not present
					if (!iframe.id) {
						iframe.id = "yt-player-" + Math.random().toString(36).substring(2, 15);
					}

					var id = iframe.id;

					ytPlayers[id] = new YT.Player(id, {
						events: {
						"onStateChange": function(event) {
							handleStateChange(event, id);
						}
					}
				});
			});
		}

		function handleStateChange(event, currentId) {
			if (event.data === YT.PlayerState.PLAYING) {
				for (var id in ytPlayers) {
					if (ytPlayers.hasOwnProperty(id) && id !== currentId) {
						var player = ytPlayers[id];
						if (typeof player.pauseVideo === "function") {
							player.pauseVideo();
						}
					}
				}
			}
		}
		';

				wp_add_inline_script( 'youtube-api', $js_code );
			}

			$should_load = ( 'toplevel_page_loginpress-settings' === $hook || 'loginpress_page_loginpress-addons' === $hook || 'loginpress_page_loginpress-help' === $hook || 'loginpress_page_loginpress-import-export' === $hook || 'loginpress_page_loginpress-license' === $hook || 'admin_page_loginpress-optin' === $hook );

			if ( version_compare( LOGINPRESS_VERSION, '6.0.0', '>=' ) ) {
				if ( $should_load || 'users.php' == $hook ) { // phpcs:ignore
					// Register script first with all parameters.
					wp_register_script( 'loginpress-react-settings', LOGINPRESS_DIR_URL . 'build/index.js', array( 'wp-element', 'wp-i18n', 'wp-api-fetch' ), LOGINPRESS_VERSION, true );

					// Enqueue the registered script.
					wp_enqueue_script( 'loginpress-react-settings' );
					wp_enqueue_style( 'loginpress_style_react', plugins_url( 'build/index.css', __FILE__ ), array(), LOGINPRESS_VERSION );

					// Set translations.
					wp_set_script_translations( 'loginpress-react-settings', 'loginpress', LOGINPRESS_DIR_PATH . 'languages' );

					// Localize script data.
					$is_pro_active = false;
					if ( is_plugin_active( 'loginpress-pro/loginpress-pro.php' ) ) {
						$is_pro_active = true;
					}
					$languages = ! empty( get_available_languages() );

					wp_localize_script(
						'loginpress-react-settings',
						'loginpressReactData',
						array(
							'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
							'isProActive'       => $is_pro_active,
							'wp_login_url'      => wp_login_url(),
							'language_switcher' => $languages,
						)
					);
				}
			}

			if ( $should_load ) {
				wp_enqueue_style( 'loginpress_style', plugins_url( 'css/style.css', __FILE__ ), array(), LOGINPRESS_VERSION );
				wp_enqueue_script( 'loginpress_js', plugins_url( 'js/admin-custom.js', __FILE__ ), array(), LOGINPRESS_VERSION, false );

				// Array for localize.
				$loginpress_localize = array(
					'plugin_url'            => plugins_url(),
					'localize_translations' => array(
						_x( 'Name', 'Login Redirect Roles', 'loginpress' ),
					),
					'help_nonce'            => wp_create_nonce( 'loginpress-log-nonce' ),

				);

				wp_localize_script( 'loginpress_js', 'loginpress_script', $loginpress_localize );
			}
			wp_enqueue_script( 'lp_outside_plugin_admin', plugins_url( 'js/outside-plugin-admin.js', __FILE__ ), array(), LOGINPRESS_VERSION, false );
		}

		/**
		 * Add rating icon on plugins page.
		 *
		 * @since 1.0.9
		 * @version 1.1.22
		 * @param array<string> $meta_fields Array of existing meta fields.
		 * @param string        $file        Plugin file path.
		 * @return array<string> Modified meta fields array.
		 *
		 * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
		 */
		public function _row_meta( $meta_fields, $file ) {

			if ( 'loginpress/loginpress.php' !== $file ) {
				return $meta_fields;
			}

			echo '<style>.loginpress-rate-stars { display: inline-block; color: #ffb900; position: relative; top: 3px; }.loginpress-rate-stars svg{ fill:#ffb900; } .loginpress-rate-stars svg:hover{ fill:#ffb900 } .loginpress-rate-stars svg:hover ~ svg{ fill:none; } </style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			$plugin_rate   = 'https://wordpress.org/support/plugin/loginpress/reviews/?rate=5#rate-response';
			$plugin_filter = 'https://wordpress.org/support/plugin/loginpress/reviews/?filter=5';
			$svg_xmlns     = 'https://www.w3.org/2000/svg';
			$svg_icon      = '';

			for ( $i = 0; $i < 5; $i++ ) {
				$svg_icon .= "<svg xmlns='" . esc_url( $svg_xmlns ) . "' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>";
			}

			// Set icon for thumbs up.
			$meta_fields[] = '<a href="' . esc_url( $plugin_filter ) . '" target="_blank"><span class="dashicons dashicons-thumbs-up"></span>' . esc_html__( 'Vote!', 'loginpress' ) . '</a>';

			// Set icon for 5-star reviews, v1.1.22.
			$meta_fields[] = "<a href='" . esc_url( $plugin_rate ) . "' target='_blank' title='" . esc_html__( 'Rate', 'loginpress' ) . "'><i class='loginpress-rate-stars'>" . $svg_icon . '</i></a>';

			return $meta_fields;
		}

		/**
		 * Add deactivate modal layout.
		 *
		 * @return void
		 */
		public function add_deactivate_modal() {
			global $pagenow;

			if ( 'plugins.php' !== $pagenow ) {
				return;
			}

			include LOGINPRESS_DIR_PATH . 'include/loginpress-optout-form.php';
		}

		/**
		 * Plugin activation.
		 *
		 * @since  1.0.15
		 * @version 1.0.22
		 * @return void
		 */
		public static function plugin_activation() {

			$loginpress_key     = get_option( 'loginpress_customization' );
			$loginpress_setting = get_option( 'loginpress_setting' );

			// Create a key 'loginpress_customization' with empty array.
			if ( ! $loginpress_key ) {
				update_option( 'loginpress_customization', array() );
			}

			// Create a key 'loginpress_setting' with empty array.
			if ( ! $loginpress_setting ) {
				update_option( 'loginpress_setting', array() );
			}
		}

		/**
		 * Plugin uninstallation.
		 *
		 * @return void
		 */
		public function plugin_uninstallation() {
			include_once LOGINPRESS_DIR_PATH . 'include/uninstall.php';
		}


		/**
		 * Pull the LoginPress page from options.
		 *
		 * @access public
		 * @since 1.1.3
		 * @version 1.1.7
		 * @return mixed
		 */
		public function get_loginpress_page() {

			$loginpress_setting = get_option( 'loginpress_setting', array() );
			if ( ! is_array( $loginpress_setting ) && empty( $loginpress_setting ) ) {
				$loginpress_setting = array();
			}
			$page = array_key_exists( 'loginpress_page', $loginpress_setting ) ? get_post( $loginpress_setting['loginpress_page'] ) : false;

			return $page;
		}
	} // End Of Class.
endif;


/**
 * Returns the main instance of WP to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return LoginPress
 */
function loginpress_loader() {
	return LoginPress::instance();
}

// Call the function.
loginpress_loader();

/**
* Create the Object of Custom Login Entities and Settings.
*
* @since  1.0.0
*/
new LoginPress_Customizer();
new LoginPress_Settings();

/**
* Create the Object of Remote Notification.
*
* @since  1.0.9
*/
if ( ! class_exists( 'TAV_Remote_Notification_Client' ) ) {
	require LOGINPRESS_ROOT_PATH . 'include/class-remote-notification-client.php';
}
$notification = new TAV_Remote_Notification_Client( 125, '16765c0902705d62', 'https://wpbrigade.com?post_type=notification' );

register_activation_hook( __FILE__, array( 'LoginPress', 'plugin_activation' ) );
