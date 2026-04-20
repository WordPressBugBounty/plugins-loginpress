<?php
/**
 * LoginPress Settings Trait
 *
 * Registering the Main Plugins Settings
 * Method originally defined in `class-loginpress-setup.php` to keep the main file slim.
 *
 * @package   LoginPress
 * @subpackage Traits
 * @since     6.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'LoginPress_Settings_Trait' ) ) {
	/**
	 * LoginPress Settings Trait
	 *
	 * Handles LoginPress settings functionality.
	 *
	 * @package   LoginPress
	 * @subpackage Traits
	 * @since     6.1.0
	 */
	trait LoginPress_Settings_Trait {

		/**
		 * Initialize the settings
		 *
		 * @since 1.0.9
		 * @return void
		 */
		public function loginpress_setting_init() {

			// set the settings.
			$this->settings_api->set_sections( $this->get_settings_sections() );
			$this->settings_api->set_fields( $this->get_settings_fields() );

			// initialize settings.
			$this->settings_api->admin_init();

			// reset settings.
			$this->load_default_settings();
		}

		/**
		 * Load the default settings
		 *
		 * @since 1.0.9
		 * @return void
		 */
		public function load_default_settings() {

			$loginpress_setting = get_option( 'loginpress_setting' );
			if ( isset( $loginpress_setting['reset_settings'] ) && 'on' === $loginpress_setting['reset_settings'] ) {
				$this->handle_settings_reset( $loginpress_setting );
			}
		}

		/**
		 * Handle settings reset logic
		 *
		 * @since 6.1.0
		 * @param array<string, mixed> $settings The settings array to modify.
		 * @return void
		 */
		private function handle_settings_reset( &$settings ) {
			$loginpress_last_reset = array( 'last_reset_on' => gmdate( 'Y-m-d' ) );
			update_option( 'loginpress_customization', $loginpress_last_reset );
			update_option( 'customize_presets_settings', 'minimalist' );
			$settings['reset_settings'] = 'off';
			update_option( 'loginpress_setting', $settings );
			add_action( 'admin_notices', array( $this, 'settings_reset_message' ) );
		}

		/**
		 * Reset settings message
		 *
		 * @since 1.0.9
		 * @return void
		 */
		public function settings_reset_message() {

			$class   = 'message success';
			$message = __( 'Default Settings Restored.', 'loginpress' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}

		/**
		 * Code for add loginpress icon in admin bar.
		 *
		 * @since 1.0.9
		 * @return void
		 */
		public function loginpress_setting_menu() {

			/**
			 * The White-labeling to hide the sidebar menu for specific user/s.
			 */
			if ( apply_filters( 'loginpress_sidebar_hide_menu_item', false ) ) {
				return;
			}

			add_action( 'admin_head', 'loginpressicon' ); // admin_head is a hook loginpressicon is a function we are adding it to the hook.
			/**
			 * LoginPress Dashicon.
			 */
			function loginpressicon() {
				echo "<style>

				#adminmenu li#toplevel_page_loginpress-settings>a>div.wp-menu-image:before{
					content: '';
					background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTkiIGhlaWdodD0iMTgiIHZpZXdCb3g9IjAgMCAxOSAxOCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTE2LjA5MzkgMTQuMzc2MkwxNy44ODM4IDEyLjYyNDFMMTYuOTY4MyAxMy41MjAzTDE2LjA5MzkgMTQuMzc2MloiIGZpbGw9IiNBQUFBQUEiLz4KPHBhdGggZD0iTTExLjA5MjUgOS4zODc3SDIuODU1NTJWMTMuNTE5OEgxMS4wOTI1TDE0LjIwNTkgMTEuNDQ3NUwxMS4wOTI1IDkuMzg3N1oiIGZpbGw9IndoaXRlIi8+CjxwYXRoIGQ9Ik0wIDguMDUwNzVWMTEuMDQ2NEMwLjAwMDg0NTMyNyAxMS43MDIzIDAuMjY3MzkzIDEyLjMzMTEgMC43NDExODQgMTIuNzk0OEMxLjIxNDk3IDEzLjI1ODYgMS44NTczMyAxMy41MTk1IDIuNTI3MzcgMTMuNTIwM0g0LjIyMTQxVjguMDUwNzVIMFoiIGZpbGw9IndoaXRlIi8+CjxwYXRoIGQ9Ik0xMS40NzU5IDEzLjA1MjJMOS40MTMwNiAxNS4wNzE0TDEyLjQwNSAxOEwxNi4zNTM1IDE0LjEzNUwxNS4yMTk0IDEzLjI1MjNMMTEuNDc1OSAxMy4wNTIyWiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTQuMjIxNDEgMEgwVjkuMzg3NzVINC4yMjE0MVYwWiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTEzLjQ0NiAxMS4xMjMyTDEwLjk5ODEgMTMuNTIwM0wxNS44ODY3IDEzLjU0NzVMMTMuNDQ2IDExLjEyMzJaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTYuMzM5OCA4Ljc3MzA4TDEyLjQwNSA0LjkyMTQ5TDkuNDEzMDYgNy44MzY3N0wxMS4yMDI5IDkuNTg4NzlMMTUuMjE5IDkuNjgyNkwxNi4zMzk4IDguNzczMDhaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTUuMjE5NSA5LjM4NzdIMTAuOTk4MUwxMy4xMDE5IDExLjQ2MDVMMTUuMjE5NSA5LjM4NzdaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTQuOTA1NSAxMy4yMjZMMTYuMDkyIDE0LjM3NjNMMTcuMjI2MSAxMy4yNzk2TDE0LjkwNTUgMTMuMjI2WiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTE3LjIxNDMgOS42Mjg5OEwxNi4wOTQzIDguNTMyMjlMMTQuOTE5MiA5LjY4MjU5TDE3LjIxNDMgOS42Mjg5OFoiIGZpbGw9IndoaXRlIi8+CjxwYXRoIGQ9Ik0xNy44ODM3IDEwLjI4MzhMMTYuOTY4MyA5LjM4NzdIMTUuMjE5NUwxMy4xMDE5IDExLjQ2MDVMMTUuMjE5NSAxMy41MzMySDE2Ljk2ODNMMTcuODgzNyAxMi42MzcxQzE4LjA0MyAxMi40ODM0IDE4LjE2OTQgMTIuMzAwMiAxOC4yNTU3IDEyLjA5ODJDMTguMzQyIDExLjg5NjIgMTguMzg2NCAxMS42Nzk0IDE4LjM4NjQgMTEuNDYwNUMxOC4zODY0IDExLjI0MTUgMTguMzQyIDExLjAyNDggMTguMjU1NyAxMC44MjI4QzE4LjE2OTQgMTAuNjIwOCAxOC4wNDMgMTAuNDM3NiAxNy44ODM3IDEwLjI4MzhaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K');
					width: 18px;
					height: 18px;
					background-size: 18px 18px;
					background-position: center;
					background-repeat: no-repeat;
					display: inline-block;
					vertical-align: middle;
				}
				#adminmenu li#toplevel_page_loginpress-settings>a:not(:hover)>div.wp-menu-image:before{
					opacity: 0.6;
				}
				#adminmenu li#toplevel_page_loginpress-settings:hover>a>div.wp-menu-image:before,
				#adminmenu li#toplevel_page_loginpress-settings>a:is(:hover, .wp-has-current-submenu)>div.wp-menu-image:before{
					opacity: 1;
				}
				#adminmenu li#toplevel_page_loginpress-settings:is(:hover):not(.wp-has-current-submenu)>a>div.wp-menu-image:before{
					opacity: 1;
					background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTkiIGhlaWdodD0iMTgiIHZpZXdCb3g9IjAgMCAxOSAxOCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTE2LjA5MzggMTQuMzc2M0wxNy44ODM2IDEyLjYyNDNMMTYuOTY4MiAxMy41MjA0TDE2LjA5MzggMTQuMzc2M1oiIGZpbGw9IiM3MkFFRTYiLz4KPHBhdGggZD0iTTExLjA5MjUgOS4zODc3SDIuODU1NTJWMTMuNTE5OEgxMS4wOTI1TDE0LjIwNTkgMTEuNDQ3NUwxMS4wOTI1IDkuMzg3N1oiIGZpbGw9IiM3MkFFRTYiLz4KPHBhdGggZD0iTTAgOC4wNTA3NVYxMS4wNDY0QzAuMDAwODQ1MzI3IDExLjcwMjMgMC4yNjczOTMgMTIuMzMxMSAwLjc0MTE4NCAxMi43OTQ4QzEuMjE0OTcgMTMuMjU4NiAxLjg1NzMzIDEzLjUxOTUgMi41MjczNyAxMy41MjAzSDQuMjIxNDFWOC4wNTA3NUgwWiIgZmlsbD0iIzcyQUVFNiIvPgo8cGF0aCBkPSJNMTEuNDc1OSAxMy4wNTIyTDkuNDEzMDYgMTUuMDcxNEwxMi40MDUgMThMMTYuMzUzNSAxNC4xMzVMMTUuMjE5NCAxMy4yNTIzTDExLjQ3NTkgMTMuMDUyMloiIGZpbGw9IiM3MkFFRTYiLz4KPHBhdGggZD0iTTQuMjIxNDEgMEgwVjkuMzg3NzVINC4yMjE0MVYwWiIgZmlsbD0iIzcyQUVFNiIvPgo8cGF0aCBkPSJNMTMuNDQ2IDExLjEyMzJMMTAuOTk4MSAxMy41MjAzTDE1Ljg4NjcgMTMuNTQ3NUwxMy40NDYgMTEuMTIzMloiIGZpbGw9IiM3MkFFRTYiLz4KPHBhdGggZD0iTTE2LjMzOTggOC43NzMwOEwxMi40MDUgNC45MjE0OUw5LjQxMzA2IDcuODM2NzdMMTEuMjAyOSA5LjU4ODc5TDE1LjIxOSA5LjY4MjZMMTYuMzM5OCA4Ljc3MzA4WiIgZmlsbD0iIzcyQUVFNiIvPgo8cGF0aCBkPSJNMTUuMjE5NSA5LjM4NzdIMTAuOTk4MUwxMy4xMDE5IDExLjQ2MDVMMTUuMjE5NSA5LjM4NzdaIiBmaWxsPSIjNzJBRUU2Ii8+CjxwYXRoIGQ9Ik0xNC45MDU1IDEzLjIyNkwxNi4wOTIgMTQuMzc2M0wxNy4yMjYxIDEzLjI3OTZMMTQuOTA1NSAxMy4yMjZaIiBmaWxsPSIjNzJBRUU2Ii8+CjxwYXRoIGQ9Ik0xNy4yMTQzIDkuNjI4OThMMTYuMDk0MyA4LjUzMjI5TDE0LjkxOTIgOS42ODI1OUwxNy4yMTQzIDkuNjI4OThaIiBmaWxsPSIjNzJBRUU2Ii8+CjxwYXRoIGQ9Ik0xNy44ODM3IDEwLjI4MzhMMTYuOTY4MyA5LjM4NzdIMTUuMjE5NUwxMy4xMDE5IDExLjQ2MDVMMTUuMjE5NSAxMy41MzMySDE2Ljk2ODNMMTcuODgzNyAxMi42MzcxQzE4LjA0MyAxMi40ODM0IDE4LjE2OTQgMTIuMzAwMiAxOC4yNTU3IDEyLjA5ODJDMTguMzQyIDExLjg5NjIgMTguMzg2NCAxMS42Nzk0IDE4LjM4NjQgMTEuNDYwNUMxOC4zODY0IDExLjI0MTUgMTguMzQyIDExLjAyNDggMTguMjU1NyAxMC44MjI4QzE4LjE2OTQgMTAuNjIwOCAxOC4wNDMgMTAuNDM3NiAxNy44ODM3IDEwLjI4MzhaIiBmaWxsPSIjNzJBRUU2Ii8+Cjwvc3ZnPgo=')
				}
				</style>";
				$styles = '
					#adminmenu .loginpress-sidebar-upgrade-pro {
						background-color: #00a32a !important;
						color: #fff !important;
						font-weight: 600 !important;
					}
					#adminmenu .loginpress-sidebar-upgrade-pro:hover {
						background-color: #008a20 !important;
						color: #fff !important;
					}
				';

				printf( '<style>%s</style>', $styles ); // phpcs:ignore
				// Adjust the Pro menu item link.
				global $submenu;

				// Bail if a plugin menu is not registered.
				if ( ! isset( $submenu['loginpress-settings'] ) ) {
					return;
				}

				$upgrade_link_position = key(
					array_filter(
						$submenu['loginpress-settings'],
						static function ( $item ) {
							return strpos( urldecode( $item[2] ), 'loginpress.pro' ) !== false;
						}
					)
				);

				// Bail if "Upgrade to Pro" menu item is not registered.
				if ( null === $upgrade_link_position ) {
					return;
				}

				// Add the PRO badge to the menu item.
				if ( isset( $submenu['loginpress-settings'][ $upgrade_link_position ][4] ) ) {
					$submenu['loginpress-settings'][ $upgrade_link_position ][4] .= ' loginpress-sidebar-upgrade-pro'; // phpcs:ignore
				} else {
					$submenu['loginpress-settings'][ $upgrade_link_position ][] = 'loginpress-sidebar-upgrade-pro'; // phpcs:ignore
				}

				$current_screen      = get_current_screen();
				$upgrade_utm_content = null === $current_screen ? 'Upgrade to Pro' : 'Upgrade to Pro - ' . $current_screen->base;
				$upgrade_utm_content = empty( $_GET['view'] ) ? $upgrade_utm_content : $upgrade_utm_content . ': ' . sanitize_key( $_GET['view'] ); // phpcs:ignore
				$upgrade_utm_content = empty( $_GET['tab'] ) ? $upgrade_utm_content : $upgrade_utm_content . ': ' . sanitize_key( $_GET['tab'] ); // phpcs:ignore

				// Parse the existing URL and manually add utm_content to maintain parameter order.
				$existing_url = $submenu['loginpress-settings'][ $upgrade_link_position ][2];

				// Simply append utm_content with & since the URL already has query parameters from loginpress_admin_upgrade_link.
				$submenu['loginpress-settings'][ $upgrade_link_position ][2] = esc_url( // phpcs:ignore
					$existing_url . '&utm_content=' . rawurlencode( $upgrade_utm_content )
				);
			}

			// Create LoginPress Parent Page.
			add_menu_page(
				__( 'LoginPress', 'loginpress' ),
				'LoginPress',
				'manage_options',
				'loginpress-settings',
				array( $this, 'plugin_page' ),
				'',
				50
			);

			// Create Submenu for LoginPress > Settings Page.
			add_submenu_page(
				'loginpress-settings',
				__( 'Settings', 'loginpress' ),
				__( 'Settings', 'loginpress' ),
				'manage_options',
				'loginpress-settings',
				array( $this, 'plugin_page' )
			);

			// Create Submenu for LoginPress > Customizer Page.
			add_submenu_page(
				'loginpress-settings',
				__( 'Customizer', 'loginpress' ),
				__( 'Customizer', 'loginpress' ),
				'manage_options',
				'loginpress',
				'__return_null'
			);

			// Create Submenu for LoginPress > Help Page.
			add_submenu_page(
				'loginpress-settings',
				__( 'Help', 'loginpress' ),
				__( 'Help', 'loginpress' ),
				'manage_options',
				'loginpress-help',
				array( $this, 'loginpress_help_page' )
			);

			// Create Submenu for LoginPress > Import / Export Page.
			add_submenu_page(
				'loginpress-settings',
				__( 'Import/Export LoginPress Settings', 'loginpress' ),
				__( 'Import / Export', 'loginpress' ),
				'manage_options',
				'loginpress-import-export',
				array( $this, 'loginpress_import_export_page' )
			);

			// Create Submenu for LoginPress > Add-Ons Page.
			add_submenu_page(
				'loginpress-settings',
				__( 'Add-Ons', 'loginpress' ),
				__( 'Add-Ons', 'loginpress' ),
				'manage_options',
				'loginpress-addons',
				array( $this, 'loginpress_addons_page' )
			);
			// Add Upgrade to Pro menu item only if not Pro version.
			if ( ! loginpress_is_pro() ) {
				add_submenu_page(
					'loginpress-settings',
					__( 'Upgrade to Pro', 'loginpress' ),
					__( 'Upgrade to Pro', 'loginpress' ),
					'manage_options',
					loginpress_admin_upgrade_link( 'admin-menu' )
				);
			}
		}

		/**
		 * Render the settings section for LoginPress.
		 *
		 * @since 1.0.0
		 * @version 3.0.0
		 * @return array<string, mixed> The settings sections array.
		 */
		public function get_settings_sections() {

			/**
			 * Add a general settings section of LoginPress.
			 * id: unique section id
			 * title: Title of the section
			 * sub-title: Sub title of the section
			 * description: Description of the section
			 * video link: Video link for the section
			 */
			$loginpress_general_tab = array(
				array(
					'id'         => 'loginpress_setting',
					'title'      => __( 'Settings', 'loginpress' ),
					'sub-title'  => __( 'Login Page Settings', 'loginpress' ),
					'desc'       => sprintf(
						// translators: WordPress customizer.
						__( '%3$sEverything else is customizable through %1$sWordPress Customizer%2$s.%4$s', 'loginpress' ),
						'<a href="' . admin_url( 'admin.php?page=loginpress' ) . '">',
						'</a>',
						'<p>',
						'</p>'
					),
					'video_link' => 'GMAwsHomJlE',

				),
			);

			/**
			 * Add Promotion tabs in settings page.
			 *
			 * @since 1.1.22
			 * @version 1.1.24
			 */
			if ( ! has_action( 'loginpress_pro_add_template' ) ) {

				include LOGINPRESS_DIR_PATH . 'classes/class-loginpress-promotion-tabs.php';
			}

			$sections = apply_filters( 'loginpress_settings_tab', $loginpress_general_tab );

			return $sections;
		}

		/**
		 * Returns all the settings fields
		 *
		 * @since 1.0.9
		 * @version 3.0.8
		 * @return array<string, mixed> Settings fields array.
		 */
		public function get_settings_fields() {

			$apply_strength_options = array(
				'register' => __( 'Register Form', 'loginpress' ),
				'reset'    => __( 'Password Reset Form', 'loginpress' ),
			);

			if ( class_exists( 'woocommerce' ) ) {

				$woo_strength_options   = array(
					'wc_forms' => __( 'WooCommerce Reset Form', 'loginpress' ),
				);
				$apply_strength_options = array_merge( $apply_strength_options, $woo_strength_options );
			}
			/**
			 * Array of free fields.
			 *
			 * @var array<array<string, mixed>> $free_fields array of free fields.
			 */
			$free_fields = array(
				array(
					'name'       => 'enable_password_reset',
					'label'      => __( 'Force Password Reset', 'loginpress' ),
					'desc'       => __( 'Enable to enforce password reset after certain duration.', 'loginpress' ),
					'extra_desc' => __( 'Enable to enforce password reset after certain duration.', 'loginpress' ),
					'type'       => 'checkbox',
				),
				array(
					'name'              => 'loginpress_password_reset_time_limit',
					'label'             => __( 'Password Reset Duration', 'loginpress' ),
					'desc'              => __( 'Set the duration in days after which the user will be forced to change password again. e.g 10.', 'loginpress' ),
					'placeholder'       => __( '10', 'loginpress' ),
					'min'               => 0,
					'max'               => $this->change_force_time_limit( 500 ),
					'step'              => '1',
					'type'              => 'number',
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
				array(
					'name'    => 'roles_for_password_reset',
					'label'   => __( 'Password Reset For', 'loginpress' ),
					'desc'    => __( 'Choose the roles for password reset forcefully to secure the site\'s security.', 'loginpress' ),
					'type'    => 'multicheck',
					'options' => $this->get_all_roles(),
				),
				array(
					'name'              => 'session_expiration',
					'label'             => __( 'Session Expire', 'loginpress' ),

					'desc'              => sprintf( __( 'Set the session expiration time in minutes. e.g: 10', 'loginpress' ) ), // <br /> When you set the time, here you need to set the expiration cookies. for this, you just need to logout at least one time. After login again, it should be working fine.<br />For removing the session expiration just pass empty value in “Expiration” field and save it. Now clear the expiration cookies by logout at least one time.
					'placeholder'       => __( '10', 'loginpress' ),
					'min'               => 0,
					'step'              => '1',
					'type'              => 'number',
					'default'           => 'Title',
					'sanitize_callback' => 'absint',
				),
				array(
					'name'  => 'auto_remember_me',
					'label' => __( 'Auto Remember Me', 'loginpress' ),
					'desc'  => sprintf(
						// translators: Auto Remember Me.
						__( 'Enable to keep the %1$sRemember Me%2$s option always checked on the Login Page.', 'loginpress' ),
						'<a href="' . esc_url( 'https://loginpress.pro/doc/enable-the-auto-remember-me-checkbox?utm_source=loginpress-lite&utm_medium=settings&utm_campaign=user-guide&utm_content=Auto+Remember+Me+Documentation' ) . '" target="_blank">',
						'</a>'
					),
					'type'  => 'checkbox',
				),
				array(
					'name'  => 'enable_reg_pass_field',
					'label' => __( 'Custom Password Fields', 'loginpress' ),
					'desc'  => sprintf(
						// translators: Custom Password Fields.
						__( 'Enable to add %1$sCustom Password Fields%2$s to the Registration Form.', 'loginpress' ),
						'<a href="' . esc_url( 'https://loginpress.pro/doc/custom-password-fields-on-the-registration-form/?utm_source=loginpress-lite&utm_medium=settings&utm_campaign=user-guide&utm_content=Custom+Password+Fields+Documentation' ) . '" target="_blank">',
						'</a>'
					),
					'type'  => 'checkbox',
				),
				array(
					'name'  => 'enable_pass_strength',
					'label' => __( 'Enable Password Strength', 'loginpress' ),
					'desc'  => __( 'To Enable password strength setting on password field.', 'loginpress' ),
					'type'  => 'checkbox',
				),
				array(
					'name'              => 'minimum_pass_char',
					'label'             => __( 'Minimum Password Strength', 'loginpress' ),
					'desc'              => __( 'Set the minimum password length. e.g: 8', 'loginpress' ),
					'placeholder'       => __( '8', 'loginpress' ),
					'min'               => 8,
					'default'           => 8,
					'type'              => 'number',
					'sanitize_callback' => 'absint',
				),
				array(
					'name'    => 'pass_strength',
					'label'   => __( 'Password Strength Options', 'loginpress' ),
					'type'    => 'multicheck',
					'options' => array(
						'lower_upper_char_must' => __( '[a-z | A-Z] At least one lower & upper case character.', 'loginpress' ),
						'special_char_must'     => __( '[ @,#,$,% etc ] At least one special character.', 'loginpress' ),
						'integer_no_must'       => __( '[0-9] At least one integer number.', 'loginpress' ),
					),
				),
				array(
					'name'  => 'password_strength_meter',
					'label' => __( 'Password Strength Meter', 'loginpress' ),
					'desc'  => __( 'Enable to show password strength meter.', 'loginpress' ),
					'type'  => 'checkbox',
				),
				array(
					'name'    => 'enable_pass_strength_forms',
					'label'   => __( 'Enable Password Strength on', 'loginpress' ),
					'type'    => 'multicheck',
					'options' => $apply_strength_options,
				),
				array(
					'name'    => 'login_order',
					'label'   => __( 'Login Order', 'loginpress' ),
					'type'    => 'radio',
					'default' => 'default',
					'options' => array(
						'default'  => __( 'Both Username Or Email Address', 'loginpress' ),
						'username' => __( 'Only Username', 'loginpress' ),
						'email'    => __( 'Only Email Address', 'loginpress' ),
					),
				),
				array(
					'name'  => 'enable_pci_compliance',
					'label' => __( 'Enable PCI Compliance', 'loginpress' ),
					'desc'  => sprintf(
						// translators: Enable PCI Compliance.
						__( 'Enable to add %1$sPCI Compliance%2$s to WordPress Login Forms.', 'loginpress' ),
						'<a href="' . esc_url( 'https://loginpress.pro/doc/wordpress-login-page-pci-compliance/?utm_source=loginpress-lite&utm_medium=settings&utm_campaign=user-guide&utm_content=PCI+Compliance+Documentation' ) . '" target="_blank">',
						'</a>'
					),
					'type'  => 'checkbox',
				),
				array(
					'name'  => 'reset_settings',
					'label' => __( 'Reset customizer settings', 'loginpress' ),
					'desc'  => sprintf(
						// translators: Reset customizer settings.
						__( 'Enable to reset customizer settings.%1$sNote: All your customization will be reverted back to the LoginPress default theme.%2$s', 'loginpress' ),
						'<span class="loginpress-settings-span">',
						'</span>'
					),
					'type'  => 'checkbox',
				),
			);

			/**
			 * Add option to remove language switcher option
			 *
			 * @since 1.5.11
			 */
			if ( version_compare( $GLOBALS['wp_version'], '5.9', '>=' ) && ! empty( get_available_languages() ) ) {
				$free_fields = $this->loginpress_language_switcher( $free_fields );
			}

			/**
			 * Add WooCommerce lostpassword_url field.
			 *
			 * @since 1.1.7
			 */
			if ( class_exists( 'WooCommerce' ) ) {
				$free_fields = $this->loginpress_woocommerce_lostpasword_url( $free_fields );
			}

			// Add loginpress_uninstall field in version 1.1.9.
			$free_fields     = $this->loginpress_uninstallation_tool( $free_fields );
			$settings_fields = apply_filters( 'loginpress_pro_settings', $free_fields );
			$settings_fields = array( 'loginpress_setting' => $settings_fields );
			$tab             = apply_filters( 'loginpress_settings_fields', $settings_fields );

			return $tab;
		}


		/**
		 * Get all roles for force reset password after six months in settings section
		 *
		 * @since 3.0.0
		 * @return array<string, string> Array of user roles.
		 */
		public function get_all_roles() {

			global $wp_roles;
			$loginpress_force_reset_roles = array();

			foreach ( $wp_roles->roles as $role => $val ) {

				$loginpress_force_reset_roles[ $val['name'] ] = sanitize_text_field( $val['name'] );
			}
			return $loginpress_force_reset_roles;
		}

		/**
		 * Merge a WooCommerce lost password URL field with the last element of array.
		 *
		 * @param array<array<string, mixed>> $fields_list The fields list array.
		 * @since 1.1.7
		 * @return array<array<string, mixed>> Modified fields list.
		 */
		public function loginpress_woocommerce_lostpasword_url( $fields_list ) {

			$array_elements    = array_slice( $fields_list, 0, -1 ); // slice a last element of array.
			$last_element      = end( $fields_list ); // last element of array.
			$lostpassword_url  = array(
				'name'  => 'lostpassword_url',
				'label' => __( 'Lost Password URL', 'loginpress' ),
				'desc'  => __( 'Use WordPress default lost password URL instead of WooCommerce custom lost password URL.', 'loginpress' ),
				'type'  => 'checkbox',
			);
			$last_two_elements = array_merge( array( $lostpassword_url, $last_element ? $last_element : array() ) ); // merge last 2 elements of array.
			return array_merge( $array_elements, $last_two_elements ); // merge an array and return.
		}

		/**
		 * Merge a language switcher in the settings element of array.
		 *
		 * @param array<array<string, mixed>> $fields_list The free fields of LoginPress.
		 * @since 1.5.11
		 * @return array<array<string, mixed>> The total fields including the added field of language switcher.
		 */
		public function loginpress_language_switcher( $fields_list ) {

			$array_elements      = array_slice( $fields_list, 0, -1 ); // slice a last element of array.
			$last_element        = end( $fields_list ); // last element of array.
			$switcher_option     = array(
				'name'  => 'enable_language_switcher',
				'label' => __( 'Language Switcher', 'loginpress' ),
				'desc'  => sprintf(
					// translators: Custom Password Fields.
					__( 'Enable to remove %1$sLanguage Switcher Dropdown%2$s on Login Forms.', 'loginpress' ),
					'<i>',
					'</i>'
				),
				'type'  => 'checkbox',
			);
			$lang_switch_element = array_merge( array( $switcher_option, $last_element ? $last_element : array() ) ); // merge last 2 elements of array.
			return array_merge( $array_elements, $lang_switch_element ); // merge an array and return.
		}
	}
}
