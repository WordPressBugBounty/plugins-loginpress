<?php
/**
 * LoginPress Custom Password Class.
 *
 * Enable Custom Password for Register User.
 *
 * @package LoginPress
 * @since 1.0.22
 * @version 3.2.1
 */

if ( ! class_exists( 'LoginPress_Custom_Password' ) ) :

	/**
	 * LoginPress Custom Passwords class.
	 *
	 * @since 1.0.22
	 * @version 3.2.1
	 */
	class LoginPress_Custom_Password {
		/**
		 * LoginPress customization options.
		 *
		 * @var array<string, mixed>|false The single instance of the class.
		 * @since 1.0.0
		 */
		public $loginpress_key;

		/**
		 * Class Constructor.
		 *
		 * @since 1.0.22
		 * @return void
		 */
		public function __construct() {
			$this->loginpress_key = get_option( 'loginpress_customization' );
			$this->hooks();
			$this->includes();
		}

		/**
		 * Include required files used in admin or on the frontend.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public function includes() {
			if ( defined( 'LOGINPRESS_DIR_PATH' ) ) {
				require_once LOGINPRESS_DIR_PATH . 'classes/class-loginpress-password-strength.php';
			}
		}

		/**
		 * Hook into actions and filters.
		 *
		 * @since 1.0.22
		 * @return void
		 */
		public function hooks() {

			add_action( 'register_form', array( $this, 'loginpress_reg_password_fields' ) );
			add_action( 'register_new_user', array( $this, 'loginpress_default_password_nag' ) );
			add_filter( 'registration_errors', array( $this, 'loginpress_reg_pass_errors' ), 10, 3 );
			add_filter( 'wp_new_user_notification_email', array( $this, 'loginpress_new_user_email_notification' ), 11 );
		}

		/**
		 * Custom Password Fields on Registration Form.
		 *
		 * @since   1.0.22
		 * @access  public
		 * @return  void
		 */
		public function loginpress_reg_password_fields() {

			$loginpress_setting       = get_option( 'loginpress_setting' );
			$enable_password_strength = isset( $loginpress_setting['enable_pass_strength'] ) ? $loginpress_setting['enable_pass_strength'] : 'off';
			$enable_pass_strength     = isset( $loginpress_setting['enable_pass_strength_forms'] ) ? $loginpress_setting['enable_pass_strength_forms'] : 'off';
			$register                 = isset( $enable_pass_strength['register'] ) ? $enable_pass_strength['register'] : false;
			?>
			<p class="loginpress-reg-pass-wrap">
				<label for="loginpress-reg-pass"><?php esc_html_e( 'Password', 'loginpress' ); ?></label>
			</p>

			<div class="loginpress-reg-pass-wrap-1 password-field">
				<input autocomplete="off" name="loginpress-reg-pass" id="loginpress-reg-pass" class="input custom-password-input" size="20" value="" type="password" />
				<span class="show-password-toggle dashicons dashicons-visibility"></span>
			</div>

			<p class="loginpress-reg-pass-2-wrap">
				<label for="loginpress-reg-pass-2"><?php esc_html_e( 'Confirm Password', 'loginpress' ); ?></label>
			</p>

			<div class="loginpress-reg-pass-wrap-2 password-field">
				<input autocomplete="off" name="loginpress-reg-pass-2" id="loginpress-reg-pass-2" class="input custom-password-input" size="20" value="" type="password" />
				<span class="show-password-toggle dashicons dashicons-visibility"></span>
			</div>
			<span id="pass-strength-result" style=" padding: 3px 15px; width:100%; display:block;"></span>
			<style>
				#pass-strength-result:empty{
					display: none !important;
				}
			</style>
			
			<?php if ( 'on' === $enable_password_strength && $register ) { ?>
			<p class="hint-custom-reg" style="padding: 5px;">
				<?php echo wp_kses_post( LoginPress_Password_Strength::loginpress_hint_creator() ); ?>
			</p>
			<?php } ?>
			<?php
		}

		/**
		 * Handles password field errors for registration form.
		 *
		 * @param WP_Error $errors WP_Error object.
		 * @param string   $sanitized_user_login User login.
		 * @param string   $user_email User email.
		 * @since 1.0.22
		 * @version 3.0.0
		 * @return WP_Error object.
		 */
		public function loginpress_reg_pass_errors( $errors, $sanitized_user_login, $user_email ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Parameters required by WordPress filter.

			// Ensure passwords aren't empty.
			if ( ( empty( $_POST['loginpress-reg-pass'] ) || empty( $_POST['loginpress-reg-pass-2'] ) ) && ( empty( $_POST['user_pass'] ) || empty( $_POST['user_confirm_pass'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
				$errors->add(
					'empty_password',
					// translators: Empty Password.
					sprintf( __( '%1$sError:%2$s Please enter your password twice.', 'loginpress' ), '<strong>', '</strong>' ) // phpcs:ignore
				);

				// Ensure passwords are matched.
			} elseif ( sanitize_text_field( wp_unslash( $_POST['loginpress-reg-pass'] ) ) !== sanitize_text_field( wp_unslash( $_POST['loginpress-reg-pass-2'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.

				// if passwords are not matched, then set default passwords doesn't match message or show customized message.
				$password_mismatch = is_array( $this->loginpress_key ) && array_key_exists( 'password_mismatch', $this->loginpress_key ) && ! empty( $this->loginpress_key['password_mismatch'] ) ? $this->loginpress_key['password_mismatch'] :
				// translators: Passwords Unmatched.
				sprintf( __( '%1$sError:%2$s Passwords doesn\'t match.', 'loginpress' ), '<strong>', '</strong>' ); // phpcs:ignore

				// Show error message of passwords don't match message.
				$errors->add(
					'password_mismatch',
					// translators: Error Message.
					sprintf( __( 'Error: %s', 'loginpress' ), $password_mismatch ) // phpcs:ignore
				);

				// Password Set? assign password to a user_pass.
			} else {
				$_POST['user_pass'] = sanitize_text_field( wp_unslash( $_POST['loginpress-reg-pass'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
			}

			return $errors;
		}

		/**
		 * Sets the value of default password nag.
		 *
		 * @param int $user_id User ID.
		 * @since 1.0.22
		 * @version 3.0.0
		 * @return void
		 */
		public function loginpress_default_password_nag( $user_id ) {

			// False => User not using WordPress default password.
			update_user_meta( $user_id, 'default_password_nag', false );
			if ( isset( $_POST['user_pass'] ) && ! empty( $_POST['user_pass'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is called during user registration process where nonce is already verified.
				$password = sanitize_text_field( wp_unslash( $_POST['user_pass'] ) ); // phpcs:ignore
				wp_set_password( $password, $user_id );
			}
		}

		/**
		 * Filter the new user email notification.
		 *
		 * @param array<string, mixed> $email The new user email notification parameters.
		 * @since 1.4.0
		 * @version 3.0.0
		 * @return array<string, mixed> The new user email notification parameters.
		 */
		public function loginpress_new_user_email_notification( $email ) {

			$email['message']  = "\r\n" . esc_html__( 'You have already set your own password, use the password you have already set to login.', 'loginpress' );
			$email['message'] .= "\r\n\r\n" . wp_login_url() . "\r\n";

			return $email;
		}
	}

endif;
