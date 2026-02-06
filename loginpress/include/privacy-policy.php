<?php
/**
 * LoginPress Privacy Policy Functions.
 *
 * This file contains functions for privacy policy settings.
 * Purpose of this file is to add privacy policy field to registration form and validate user acceptance.
 *
 * @package LoginPress
 * @since 1.0.19
 */

// Add privacy policy field.
add_action( 'register_form', 'loginpress_add_privacy_policy_field' );

// Add validation. In this case, we make sure lp_privacy_policy is required.
add_filter( 'registration_errors', 'loginpress_privacy_policy_auth', 10, 3 );

/**
 * Add privacy policy field.
 *
 * @since 1.0.19
 * @return void
 */
function loginpress_add_privacy_policy_field() {

	$loginpress_setting = get_option( 'loginpress_setting' );
	$privacy_policy     = isset( $loginpress_setting['privacy_policy'] ) ? $loginpress_setting['privacy_policy'] : __( sprintf( __( '%1$sPrivacy Policy%2$s.', 'loginpress' ), '<a href="' . admin_url( 'admin.php?page=loginpress-settings' ) . '">', '</a>' ), 'loginpress' ); // @codingStandardsIgnoreLine.?> 
	<p>
		<label for="lp_privacy_policy"><br />
		<input type="checkbox" name="lp_privacy_policy" id="lp_privacy_policy" class="checkbox" />
		<?php echo wp_kses_post( $privacy_policy ); ?>
		</label>
	</p>
	<?php
}


/**
 * Add validation. In this case, we make sure lp_privacy_policy is required.
 *
 * @since 1.0.19
 * @param WP_Error $errors The privacy auth error.
 * @return WP_Error
 */
function loginpress_privacy_policy_auth( $errors ) {
	if ( ! isset( $_POST['lp_privacy_policy'] ) ) : // phpcs:ignore
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is called during registration process where nonce is already verified.
		$errors->add( 'policy_error', esc_html__( 'ERROR: Please accept the privacy policy.', 'loginpress' ) );
		return $errors;
	endif;
	return $errors;
}


/**
 * Save privacy policy acceptance.
 *
 * @since 1.0.19
 * @param int $user_id The user ID.
 * @return void
 */
function loginpress_privacy_policy_save( $user_id ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is called during user registration process where nonce is already verified.
	if ( isset( $_POST['lp_privacy_policy'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is called during user registration process where nonce is already verified.
		update_user_meta( $user_id, 'lp_privacy_policy', sanitize_text_field( wp_unslash( $_POST['lp_privacy_policy'] ) ) );
	}
}
