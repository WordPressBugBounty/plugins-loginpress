<?php
/**
 * LoginPress Developer Hooks Class.
 *
 * LoginPress has some hooks for developers.
 *
 * @package LoginPress
 * @since 1.1.7
 * @version 3.0.0
 */

if ( ! class_exists( 'LoginPress_Developer_Hooks' ) ) :

	/**
	 * LoginPress Developer Hooks Class.
	 *
	 * Developer friendly hooks.
	 *
	 * @since 1.1.7
	 * @version 3.0.0
	 */
	class LoginPress_Developer_Hooks {

		/**
		 * Class Constructor.
		 *
		 * @since 1.1.7
		 * @return void
		 */
		public function __construct() {
			$this->hooks();
		}

		/**
		 * Hooks function for Remember me.
		 *
		 * @since 1.1.7
		 * @return void
		 */
		public function hooks() {
			add_filter( 'loginpress_remember_me', array( $this, 'loginpress_remember_me_callback' ), 10, 1 );
		}

		/**
		 * Turn off the remember me option from WordPress login form.
		 *
		 * @param bool $activate Is activated ot not.
		 * @since 1.1.7
		 * @return bool
		 */
		public function loginpress_remember_me_callback( $activate ) {
			if ( ! $activate ) {
				return;	// phpcs:ignore
			}

			// Add the hook into the login_form.
			add_action( 'login_form', array( $this, 'loginpress_login_form' ), 99 );

			// Reset any attempt to set the remember option.
			add_action( 'login_head', array( $this, 'unset_remember_me_option' ), 99 );

			return $activate;
		}

		/**
		 * Unset remember me option.
		 *
		 * @since 1.1.7
		 * @return void
		 */
		public function unset_remember_me_option() {
			// Remove the remember me post value.
			if ( isset( $_POST['rememberme'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is a developer hook for custom functionality.
				unset( $_POST['rememberme'] );
			}
		}

		/**
		 * Login Form customization for remember me.
		 *
		 * @since 1.1.7
		 * @return void
		 */
		public function loginpress_login_form() {
			ob_start( array( $this, 'remove_forgetmenot_class' ) );
		}

		/**
		 * Forget me not class removal.
		 *
		 * @param string $content The content being removed.
		 * @since 1.1.7
		 * @return string Update content.
		 */
		public function remove_forgetmenot_class( $content ) {
			$content = preg_replace( '/<p class="forgetmenot">(.*)<\/p>/', '', $content );
			return $content ? $content : '';
		}
	}

endif;
$loginpress_developer_hooks = new LoginPress_Developer_Hooks();
