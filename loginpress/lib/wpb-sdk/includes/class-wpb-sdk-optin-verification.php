<?php
/**
 * Shared email opt-in verification (one implementation for all WPBrigade SDK products).
 *
 * @package wpbrigade_sdk
 * @since   3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dynamic opt-in verification driven by each plugin's wpb_sdk_dynamic_init() module config.
 */
class WPBRIGADE_Optin_Verification {

	/**
	 * Registered modules keyed by slug.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static $modules = array();

	/**
	 * Whether the single init handler is registered.
	 *
	 * @var bool
	 */
	private static $init_hooked = false;

	/**
	 * Register a product module that uses email opt-in verification.
	 *
	 * @param array<string, mixed> $module Module definition from wpb_dynamic_init().
	 * @return void
	 */
	public static function register_module( array $module ) {
		if ( empty( $module['slug'] ) ) {
			return;
		}
		if (
			empty( $module['optin_user_meta']['token'] )
			|| empty( $module['optin_user_meta']['verified'] )
		) {
			return;
		}

		$slug                   = (string) $module['slug'];
		self::$modules[ $slug ] = $module;

		if ( ! self::$init_hooked ) {
			self::$init_hooked = true;
			add_action( 'init', array( __CLASS__, 'handle_request' ), 1 );
		}
	}

	/**
	 * Handle verification link for whichever registered product matches the query string.
	 *
	 * @return void
	 */
	public static function handle_request() {
		foreach ( self::$modules as $slug => $module ) {
			if ( self::request_has_verify_token( $slug, $module ) ) {
				self::verify( $slug, $module );
				return;
			}
		}
	}

	/**
	 * @param string               $slug   Product slug.
	 * @param array<string, mixed> $module Module definition.
	 * @return bool
	 */
	private static function request_has_verify_token( $slug, array $module ) {
		$optin      = isset( $module['optin'] ) && is_array( $module['optin'] ) ? $module['optin'] : array();
		$query_args = self::verify_query_args( $slug, $optin );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Secret token in email verification link.
		foreach ( $query_args as $query_arg ) {
			if ( ! isset( $_GET[ $query_arg ] ) ) {
				continue;
			}
			$token = sanitize_text_field( wp_unslash( (string) $_GET[ $query_arg ] ) );
			if ( '' !== $token ) {
				return true;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		return false;
	}

	/**
	 * @param string               $slug   Product slug.
	 * @param array<string, mixed> $module Module definition.
	 * @return void
	 */
	private static function verify( $slug, array $module ) {
		$optin         = isset( $module['optin'] ) && is_array( $module['optin'] ) ? $module['optin'] : array();
		$token_meta    = (string) $module['optin_user_meta']['token'];
		$verified_meta = (string) $module['optin_user_meta']['verified'];
		$query_args    = self::verify_query_args( $slug, $optin );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Email link carries a secret verification token.
		$verification_token = '';
		foreach ( $query_args as $query_arg ) {
			if ( isset( $_GET[ $query_arg ] ) ) {
				$verification_token = sanitize_text_field( wp_unslash( (string) $_GET[ $query_arg ] ) );
				break;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( '' === $verification_token ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			auth_redirect();
			return;
		}

		$current_user = wp_get_current_user();
		$settings_url = function_exists( 'wpb_sdk_resolve_optin_redirect_url' )
			? wpb_sdk_resolve_optin_redirect_url( $slug )
			: self::settings_redirect_url( $optin );

		if ( ! empty( $optin['require_manage_options'] ) && ! current_user_can( 'manage_options' ) ) {
			wp_safe_redirect( add_query_arg( 'verified', 'unauthorized', $settings_url ) );
			exit;
		}

		if ( ! empty( $optin['verified_by_option'] ) ) {
			$verified_by = get_option( (string) $optin['verified_by_option'] );
			if ( ! empty( $verified_by ) ) {
				$already = ! empty( $optin['already_verified_query'] )
					? (string) $optin['already_verified_query']
					: 'already_verified';
				wp_safe_redirect( add_query_arg( 'verified', $already, $settings_url ) );
				exit;
			}
		}

		$saved_token = '';
		if ( $current_user->ID ) {
			$saved_token = get_user_meta( $current_user->ID, $token_meta, true );
		}
		if ( ( '' === $saved_token || false === $saved_token ) && ! empty( $optin['legacy_token_option'] ) ) {
			$legacy = get_option( (string) $optin['legacy_token_option'], '' );
			if ( is_string( $legacy ) && '' !== $legacy ) {
				$saved_token = $legacy;
			}
		}
		if ( '' === $saved_token || false === $saved_token ) {
			$fallback = get_option( 'wpb_sdk_' . $slug . '_fallback_verify_token', '' );
			if ( is_string( $fallback ) && '' !== $fallback ) {
				$saved_token = $fallback;
			}
		}

		if (
			function_exists( 'wpb_sdk_is_verification_token_expired' )
			&& wpb_sdk_is_verification_token_expired( $slug, (int) $current_user->ID )
		) {
			wp_safe_redirect( add_query_arg( 'verified', 'expired', $settings_url ) );
			exit;
		}

		if ( $saved_token && hash_equals( (string) $saved_token, $verification_token ) ) {
			update_user_meta( $current_user->ID, $verified_meta, 'yes' );
			delete_user_meta( $current_user->ID, $token_meta );
			$expires_meta = function_exists( 'wpb_sdk_verification_token_expires_meta_key' )
				? wpb_sdk_verification_token_expires_meta_key( $module )
				: $token_meta . '_expires';
			delete_user_meta( $current_user->ID, $expires_meta );
			delete_option( 'wpb_sdk_' . $slug . '_fallback_verify_token' );
			if ( ! empty( $optin['legacy_token_option'] ) ) {
				delete_option( (string) $optin['legacy_token_option'] );
			}

			$optin_option = ! empty( $optin['option_name'] )
				? (string) $optin['option_name']
				: self::guess_option_name( $module );
			if ( '' !== $optin_option ) {
				update_option( $optin_option, 'yes' );
			}

			if ( ! empty( $optin['verified_by_option'] ) && ! empty( $optin['store_verified_by_user'] ) ) {
				update_option(
					(string) $optin['verified_by_option'],
					array(
						'user_id'     => $current_user->ID,
						'email'       => sanitize_email( $current_user->user_email ),
						'verified_at' => current_time( 'mysql' ),
					)
				);
			}

			do_action( 'wpb_sdk_optin_verified', $current_user, $module );

			wp_safe_redirect( add_query_arg( 'verified', 'success', $settings_url ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'verified', 'failed', $settings_url ) );
		exit;
	}

	/**
	 * @param string               $slug  Product slug.
	 * @param array<string, mixed> $optin Module optin config.
	 * @return string[]
	 */
	private static function verify_query_args( $slug, array $optin ) {
		$args = array();
		if ( ! empty( $optin['verify_query_args'] ) && is_array( $optin['verify_query_args'] ) ) {
			$args = array_map( 'strval', $optin['verify_query_args'] );
		} elseif ( ! empty( $optin['verify_query_arg'] ) ) {
			$args[] = (string) $optin['verify_query_arg'];
		}

		// Laravel / default email links use {slug}_optin_verify (e.g. simple-social-buttons_optin_verify).
		$default = $slug . '_optin_verify';
		if ( ! in_array( $default, $args, true ) ) {
			$args[] = $default;
		}

		return $args;
	}

	/**
	 * @param array<string, mixed> $optin Module optin config.
	 * @return string
	 */
	private static function settings_redirect_url( array $optin ) {
		if ( ! empty( $optin['settings_admin_path'] ) ) {
			return admin_url( (string) $optin['settings_admin_path'] );
		}
		if ( ! empty( $optin['settings_page'] ) ) {
			return admin_url( 'admin.php?page=' . (string) $optin['settings_page'] );
		}
		return admin_url();
	}

	/**
	 * @param array<string, mixed> $module Module definition.
	 * @return string
	 */
	private static function guess_option_name( array $module ) {
		if ( empty( $module['settings'] ) || ! is_array( $module['settings'] ) ) {
			return '';
		}
		foreach ( array_keys( $module['settings'] ) as $key ) {
			if ( is_string( $key ) && preg_match( '/^_.*_optin$/', $key ) ) {
				return $key;
			}
		}
		return '';
	}
}
