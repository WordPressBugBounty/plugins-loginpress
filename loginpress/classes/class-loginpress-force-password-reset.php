<?php
/**
 * LoginPress Force Password Reset Class.
 *
 * Force users to change the password every 6 months by default.
 * Newly created users are affected by default.
 * Developer's hooks are provided to change the effect on existing users.
 *
 * @package LoginPress
 * @since 3.0.0
 * @version 4.0.0
 */

/**
 * LoginPress Force Password Reset Class.
 *
 * Forces users to change their password every 6 months by default.
 * Newly created users are affected by default.
 *
 * @package LoginPress
 * @since 3.0.0
 * @version 4.0.0
 */
class LoginPress_Force_Password_Reset {

	/**
	 * Password reset time limit array.
	 *
	 * @access private
	 * @var array<string, mixed>
	 */
	private $loginpress_password_reset_time_limit;

	/**
	 * Force Password Reset constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		$time_limit = $this->loginpress_get_limit();

		if ( false === $time_limit ) {
			return;
		}

		$this->loginpress_password_reset_time_limit = array(
			'loginpress_password_reset_time_limit' => $time_limit,
		);

		$this->hooks();
	}

	/**
	 * Action Hooks.
	 *
	 * @since 3.0.0
	 * @version 4.0.0
	 * @return void
	 */
	public function hooks() {

		add_action( 'user_register', array( $this, 'loginpress_update_expire_duration' ) );
		add_action( 'after_password_reset', array( $this, 'loginpress_update_expire_duration' ), 10 );
		add_action( 'profile_update', array( $this, 'loginpress_user_profile_update' ), 10 );
		add_action( 'wp_login', array( $this, 'loginpress_user_login_check' ), 10, 2 );
		add_filter( 'login_message', array( $this, 'loginpress_reset_pass_message_text' ), 10, 1 );
	}

	/**
	 * Return the password age limit setting.
	 *
	 * @since 3.0.0
	 * @return mixed $limit The password age limit.
	 */
	public function loginpress_get_limit() {
		$loginpress_setting = get_option( 'loginpress_setting' );

		if ( empty( $loginpress_setting['loginpress_password_reset_time_limit'] ) || ! isset( $loginpress_setting['loginpress_password_reset_time_limit'] ) ) {
			return false;
		}

		// By default if value is empty or equal to 0 add 182 Days by default.
		$limit = ( absint( $loginpress_setting['loginpress_password_reset_time_limit'] ) === 0 || empty( $loginpress_setting['loginpress_password_reset_time_limit'] ) ) ? 182 : absint( $loginpress_setting['loginpress_password_reset_time_limit'] );

		$timestamp = strtotime( "$limit days" );
		return $timestamp ? esc_html( (string) $timestamp ) : false;
	}

	/**
	 * Updates User meta for force change password.
	 *
	 * @since 3.0.0
	 * @param mixed $user User ID or User Object.
	 * @return void
	 */
	public function loginpress_update_expire_duration( $user ) {

		if ( is_object( $user ) ) {
			$user_id = isset( $user->ID ) ? $user->ID : 0;
		} else {
			$user_id = (int) $user;
		}

		// Update User meta with 6 months time-frame.
		update_user_meta( $user_id, 'loginpress_password_reset_limit', $this->loginpress_password_reset_time_limit );
	}

	/**
	 * Callback function which Fires Fires after the user's password is reset.
	 *
	 * @param int $user_id The user ID whose password was reset.
	 *
	 * @return void
	 */
	public function loginpress_user_profile_update( $user_id ) {

		// Returns if Password is unchanged during Profile Update.
		if ( ! isset( $_POST['pass1'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is called during profile update process.
			return;
		}

		// Update User meta with 6 months time-frame.
		update_user_meta( $user_id, 'loginpress_password_reset_limit', $this->loginpress_password_reset_time_limit );
	}

	/**
	 * Fires on login submit to check if the user has reset the password less than 6 months ago.
	 *
	 * @since 3.0.0
	 * @version 4.0.0
	 * @param string  $user_login The user login name.
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return void
	 */
	public static function loginpress_user_login_check( $user_login, $user ) {

		$loginpress_settings   = get_option( 'loginpress_setting' );
		$enable_password_reset = isset( $loginpress_settings['enable_password_reset'] ) && ! empty( $loginpress_settings['enable_password_reset'] ) ? $loginpress_settings['enable_password_reset'] : 'off';

		if ( 'off' !== $enable_password_reset ) {

			$desired_role_names = isset( $loginpress_settings['roles_for_password_reset'] ) && ! empty( $loginpress_settings['roles_for_password_reset'] ) ? $loginpress_settings['roles_for_password_reset'] : array( false );

			global $wp_roles;
			$restricted_roles = array();
			foreach ( $wp_roles->roles as $role => $val ) {
				if ( in_array( $val['name'], $desired_role_names, true ) ) {
					$restricted_roles[ $role ] = $role;
				}
			}
			// Get the meta of the user since when the user last reset the password.
			$user_meta                = get_user_meta( $user->ID, 'loginpress_password_reset_limit', true );
			$reset_time_left          = ! empty( $user_meta ) && isset( $user_meta['loginpress_password_reset_time_limit'] ) ? $user_meta['loginpress_password_reset_time_limit'] : '';
			$loginpress_reset_for_all = (bool) apply_filters( 'loginpress_password_reset_for_all', false );

			// Return from widget.
			if ( ( isset( $_POST['action'] ) && sanitize_text_field( wp_unslash( $_POST['action'] ) ) === 'loginpress_widget_login_process' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Widget login process.
				return;
			}
			// If the current date is less than 6 Months than stored before, take the user to the lost password page.
			if ( ( strtotime( 'now' ) > $reset_time_left ) ) {
				if ( $loginpress_reset_for_all || in_array( $user->roles[0], $restricted_roles, true ) ) {

					// Log out the user.
					wp_logout();

					// redirect the user to the lost password page.
					wp_safe_redirect(
						add_query_arg(
							array(
								'action'  => 'lostpassword',
								'expired' => 'expired',
							),
							wp_login_url()
						),
						302
					);

					exit;
				}
			}
		}
	}

	/**
	 * Function to convert simple days to years, months, and days depending on input.
	 *
	 * @version 3.0.0
	 * @return string $time_frame time converted to days, months, and years.
	 */
	public function loginpress_convert_days() {

		$loginpress_setting = get_option( 'loginpress_setting' );
		$limit              = isset( $loginpress_setting['loginpress_password_reset_time_limit'] ) && ! empty( $loginpress_setting['loginpress_password_reset_time_limit'] ) ? $loginpress_setting['loginpress_password_reset_time_limit'] : '';
		$years              = ( $limit / 365 ); // days / 365 days.
		$years              = floor( $years ); // Remove all decimals.
		$month              = ( $limit % 365 ) / 30;
		$month              = floor( $month ); // Remove all decimals.
		$days               = fmod( fmod( $limit, 365 ), 30 ); // the rest of days.
		if ( $years !== 0 ) { // phpcs:ignore
			$year_string  = 1 === absint( $years ) ? __( 'Year', 'loginpress' ) : __( 'Years', 'loginpress' );
			$month_string = 1 === absint( $month ) ? __( 'Month', 'loginpress' ) : __( 'Months', 'loginpress' );
			$day_string   = 1 === absint( $days ) ? __( 'Day', 'loginpress' ) : __( 'Days', 'loginpress' );

			return sprintf(
				// translators: Date year.
				__( '%1$s %2$s, %3$s %4$s and %5$s %6$s', 'loginpress' ),
				$years,
				$year_string,
				$month,
				$month_string,
				$days,
				$day_string
			);

		} elseif ( $month !== 0 ) { // phpcs:ignore
			$month_string = 1 === absint( $month ) ? __( 'Month', 'loginpress' ) : __( 'Months', 'loginpress' );

			// If there are no days, only show months.
			if ( $days === 0 ) { // phpcs:ignore
				return sprintf( '%1$s %2$s', $month, $month_string );
			}

			// If there are days, show both months and days.
			$day_string = 1 === absint( $days ) ? __( 'Day', 'loginpress' ) : __( 'Days', 'loginpress' );

			return sprintf(
				// translators: Date month.
				__( '%1$s %2$s and %3$s %4$s', 'loginpress' ),
				$month,
				$month_string,
				$days,
				$day_string
			);

		} else {
			$remain_string = 1 === absint( $days ) ? __( 'Day', 'loginpress' ) : __( 'Days', 'loginpress' );
			return sprintf( '%1$s %2$s', $days, $remain_string );
		}
	}

	/**
	 * Function callback to change the Message upon Lost password.
	 *
	 * @version 3.0.0
	 * @param string $message The message.
	 * @return string
	 */
	public function loginpress_reset_pass_message_text( $message ) {

		$status = isset( $_GET['expired'] ) && ! empty( $_GET['expired'] ) ? sanitize_text_field( wp_unslash( $_GET['expired'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for display purposes.

		if ( 'expired' === $status ) {
			$limit           = $this->loginpress_convert_days();
			$default_message = sprintf(
				// translators: Update Password.
				__( 'It\'s been %1$s%2$s%3$s since you last updated your password. Kindly update your password.', 'loginpress' ), // phpcs:ignore
				'<b>',
				$limit,
				'</b>',
				'</br>'
			);
			$message = apply_filters( 'loginpress_change_reset_message', $default_message, $limit );
			return '<p id="login_error">' . wp_kses_post( $message ) . '</p>';
		}

		return $message;
	}
}
