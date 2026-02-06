<?php
/**
 * LoginPress Login Order Class.
 *
 * Enable user to login using their username and/or email address.
 *
 * @package LoginPress
 * @since 1.0.18
 * @version 3.0.0
 */

if ( ! class_exists( 'LoginPress_Login_Order' ) ) :

	/**
	 * LoginPress Login Order Class.
	 *
	 * Handles login order functionality allowing users to login with username or email.
	 *
	 * @since 1.0.18
	 * @version 3.0.0
	 */
	class LoginPress_Login_Order {

		/**
		 * Variable that Check for LoginPress Key.
		 *
		 * @access public
		 * @var array<string, mixed>|false
		 */
		public $loginpress_key;

		/**
		 * Class constructor
		 *
		 * @since 1.0.18
		 * @version 3.0.0
		 */
		public function __construct() {

			$this->loginpress_key = get_option( 'loginpress_customization' );
			$this->hooks();
		}

		/**
		 * Hooks for the Login Order Class.
		 *
		 * @since 1.0.18
		 * @version 3.0.0
		 * @return void
		 */
		public function hooks() {
			$wp_version         = get_bloginfo( 'version' );
			$loginpress_setting = get_option( 'loginpress_setting' );
			$login_order        = isset( $loginpress_setting['login_order'] ) ? $loginpress_setting['login_order'] : '';

			if ( ! empty( $login_order ) ) {
				remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
			}

			add_filter( 'authenticate', array( $this, 'loginpress_login_order' ), 20, 3 );

			if ( 'username' === $login_order && '4.5.0' < $wp_version ) {
				// For WP 4.5.0 remove email authentication.
				remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );
			}
		}

		/**
		 * If an email address is entered in the username field, then look up the matching username and authenticate as per normal, using that.
		 *
		 * @param mixed       $user     WP_User object if authentication was successful, WP_Error or null otherwise.
		 * @param string|null $username Username or email address entered by user.
		 * @param string|null $password Password entered by user.
		 * @since 1.0.18
		 * @version 1.0.22
		 *
		 * @return mixed Results of authenticating via wp_authenticate_username_password(), using the username found when looking up via email.
		 */
		public function loginpress_login_order( $user, $username, $password ) {

			if ( $user instanceof WP_User ) {
				return $user; // phpcs:ignore
			}

			// Is username or password field empty?
			if ( empty( $username ) || empty( $password ) ) {

				if ( is_wp_error( $user ) ) {
					return $user; // phpcs:ignore
				}

				$error = new WP_Error();

				$empty_username = ( is_array( $this->loginpress_key ) && isset( $this->loginpress_key['empty_username'] ) && ! empty( $this->loginpress_key['empty_username'] ) ) ? $this->loginpress_key['empty_username'] : sprintf(
					// translators: Username field empty.
					__( '%1$sError:%2$s The username field is empty.', 'loginpress' ), // phpcs:ignore
					'<strong>',
					'</strong>'
				);

				$empty_password = ( is_array( $this->loginpress_key ) && isset( $this->loginpress_key['empty_password'] ) && ! empty( $this->loginpress_key['empty_password'] ) ) ? $this->loginpress_key['empty_password'] : sprintf(
					// translators: Password field empty.
					__( '%1$sError:%2$s The password field is empty.', 'loginpress' ), // phpcs:ignore
					'<strong>',
					'</strong>'
				);

				if ( empty( $username ) ) {
					$error->add( 'empty_username', $empty_username );
				}

				if ( empty( $password ) ) {
					$error->add( 'empty_password', $empty_password );
				}

				return $error; // phpcs:ignore
			}

			$loginpress_setting = get_option( 'loginpress_setting' );
			$login_order        = isset( $loginpress_setting['login_order'] ) ? $loginpress_setting['login_order'] : '';

			// Is login order is set to be 'email'.
			if ( 'email' === $login_order ) {

				if ( ! empty( $username ) && ! is_email( $username ) ) {

					$error = new WP_Error();

					$force_email_login = ( is_array( $this->loginpress_key ) && isset( $this->loginpress_key['force_email_login'] ) && ! empty( $this->loginpress_key['force_email_login'] ) ) ? $this->loginpress_key['force_email_login'] : sprintf(
						// translators: Email address not valid.
						__( '%1$sError:%2$s Invalid Email Address', 'loginpress' ), // phpcs:ignore
						'<strong>',
						'</strong>'
					);

					$error->add( 'loginpress_use_email', $force_email_login );

					return $error;  // phpcs:ignore
				}

				// Check if username is a valid email address.
				if ( ! empty( $username ) && is_email( $username ) ) {

					$username = str_replace( '&', '&amp;', stripslashes( $username ) );
					$user     = get_user_by( 'email', $username );

					// Check if user exists and is active.
					if ( isset( $user, $user->user_login, $user->user_status ) && 0 === intval( $user->user_status ) ) {
						$username = $user->user_login;
					}
					return wp_authenticate_username_password( null, $username, $password ); // phpcs:ignore
				}
			} // login order 'email'.

			// Is login order is set to be 'username'.
			if ( 'username' === $login_order ) {
				$user = get_user_by( 'login', $username );

				$invalid_username = ( is_array( $this->loginpress_key ) && array_key_exists( 'incorrect_username', $this->loginpress_key ) && ! empty( $this->loginpress_key['incorrect_username'] ) ) ? $this->loginpress_key['incorrect_username'] : sprintf(
					// translators: Username field invalid.
					__( '%1$sError:%2$s Invalid Username.', 'loginpress' ), // phpcs:ignore
					'<strong>',
					'</strong>'
				);

				if ( ! $user ) {
					return new WP_Error( 'invalid_username', $invalid_username ); // phpcs:ignore
				}

				// Check if username or password is provided.
				if ( ! empty( $username ) || ! empty( $password ) ) {

					$username = str_replace( '&', '&amp;', stripslashes( $username ) );
					$user     = get_user_by( 'login', $username );

					// Check if user exists and is active.
					if ( isset( $user, $user->user_login, $user->user_status ) && 0 === intval( $user->user_status ) ) {
						$username = $user->user_login;
					}
					if ( ! empty( $username ) && is_email( $username ) ) {
						return wp_authenticate_username_password( null, '', '' ); // phpcs:ignore
					} else {
						return wp_authenticate_username_password( null, $username, $password ); // phpcs:ignore
					}
				}
			} // login order 'username'.

			return $user; // phpcs:ignore
		}
	} // End Of Class.
endif;
