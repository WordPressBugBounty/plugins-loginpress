<?php
/**
 * LoginPress Addons Meta Class.
 *
 * Meta class about Add-Ons.
 *
 * @package LoginPress
 * @since 3.0.5
 * @version 6.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LoginPress_Addons_Meta' ) ) :

	/**
	 * LoginPress Addons Meta Class.
	 *
	 * Handles metadata for LoginPress addons.
	 *
	 * @package LoginPress
	 * @since 3.0.5
	 */
	class LoginPress_Addons_Meta {

		/**
		 * Class constructor.
		 *
		 * @since 1.0.19
		 * @return void
		 */
		public function __construct() {
			if ( ! get_option( 'loginpress_pro_addons' ) ) {
				$this->addons_options_array();
			} else {
				add_action( 'init', array( $this, 'addons_options_array' ) );
			}
		}

		/**
		 * The addons options array.
		 *
		 * @since 3.0.5
		 * @return void
		 */
		public static function addons_options_array() {

			$addons_array = array(
				'login-logout-menu'    => array(
					'title'      => __( 'Login Logout Menu', 'loginpress' ),
					'short_desc' => __( 'Login Logout Menu Section', 'loginpress' ),
					'slug'       => 'login-logout-menu',
					'is_free'    => true,
					'is_active'  => false,
				),
				'auto-login'           => array(
					'title'      => __( 'Auto Login', 'loginpress' ),
					'short_desc' => __( 'No More Manual Login', 'loginpress' ),
					'slug'       => 'auto-login',
					'is_free'    => false,
					'is_active'  => false,
				),
				'login-redirects'      => array(
					'title'      => __( 'Login Redirects', 'loginpress' ),
					'short_desc' => __( 'Automatically redirects the login', 'loginpress' ),
					'slug'       => 'login-redirects',
					'is_free'    => false,
					'is_active'  => false,
				),
				'limit-login-attempts' => array(
					'title'      => __( 'Limit Login Attempts', 'loginpress' ),
					'short_desc' => __( 'Limits for login attempts', 'loginpress' ),
					'slug'       => 'limit-login-attempts',
					'is_free'    => false,
					'is_active'  => false,
				),
				'hide-login'           => array(
					'title'      => __( 'Hide Login', 'loginpress' ),
					'short_desc' => __( 'Hide your login page', 'loginpress' ),
					'slug'       => 'hide-login',
					'is_free'    => false,
					'is_active'  => false,
				),
				'social-login'         => array(
					'title'      => __( 'Social Login', 'loginpress' ),
					'short_desc' => __( 'Third Party login access', 'loginpress' ),
					'slug'       => 'social-login',
					'is_free'    => false,
					'is_active'  => false,
				),
				'login-widget'         => array(
					'title'      => __( 'Login Widget', 'loginpress' ),
					'short_desc' => __( 'Creates a login widget', 'loginpress' ),
					'slug'       => 'login-widget',
					'is_free'    => false,
					'is_active'  => false,
				),
			);

			if ( ! get_option( 'loginpress_pro_addons' ) ) {
				add_option( 'loginpress_pro_addons', $addons_array );
			}

			if ( count( $addons_array ) !== count( get_option( 'loginpress_pro_addons' ) ) ) {
				update_option( 'loginpress_pro_addons', $addons_array );
			}
		}

		/**
		 * The addon details.
		 *
		 * @since 3.0.5
		 * @return array<string, array<string, string>>
		 */
		public static function addons_details() {

			$addons_details_array = array(
				'login-logout-menu'    => array(
					'title'   => 'Login Logout Menu',
					'excerpt' => __( 'Add login, logout, register, and profile links directly to your selected menus. This add-on makes it easy to manage user navigation and improves accessibility across your site without requiring any custom code or complex configuration.', 'loginpress' ),
				),
				'login-redirects'      => array(
					'title'   => 'Login Redirects',
					'excerpt' => __( 'Redirect users based on their roles after login or logout. This helps guide admins, editors, subscribers, or customers to the most relevant pages. Easily manage rules through a simple interface to create a more personalized user experience.', 'loginpress' ),
				),
				'social-login'         => array(
					'title'   => 'Social Login',
					'excerpt' => __( 'Allow users to log in or register using 15+ popular social platforms like Facebook, Google, and Twitter. This simplifies the registration process, reduces friction, and improves user experience by eliminating the need to create new credentials.', 'loginpress' ),
				),
				'login-widget'         => array(
					'title'   => 'Login Widget',
					'excerpt' => __( 'Add a flexible login widget to your sidebar for quick user access. It supports AJAX login for a seamless experience. You can customize its appearance using HTML/CSS to match your site’s design and branding.', 'loginpress' ),
				),
				'limit-login-attempts' => array(
					'title'   => 'Limit Login Attempts',
					'excerpt' => __( 'Protect your site by limiting the number of login attempts per user. This helps prevent brute-force attacks and unauthorized access. You can configure lockout duration and attempt limits to strengthen your site’s overall security.', 'loginpress' ),
				),
				'auto-login'           => array(
					'title'   => 'Auto Login',
					'excerpt' => __( 'Generate secure, unique login URLs for selected users without requiring passwords. This is ideal for quick access scenarios. You can manage, disable, or revoke access at any time, giving you full control over user authentication and security.', 'loginpress' ),
				),
				'hide-login'           => array(
					'title'   => 'Hide Login',
					'excerpt' => __( 'Customize your login page URL to make it harder for bots and attackers to find. This simple yet effective security feature reduces spam and brute-force attempts. You can also save or receive your custom login URL for easy access.', 'loginpress' ),
				),
			);
			return $addons_details_array;
		}
	}

endif;
new LoginPress_Addons_Meta();
