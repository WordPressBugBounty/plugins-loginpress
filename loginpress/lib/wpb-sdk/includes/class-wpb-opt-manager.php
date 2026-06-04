<?php
/**
 * Shared opt-in / opt-out UI and AJAX for WPBrigade SDK products.
 *
 * Driven by each plugin's wpb_sdk_dynamic_init() module config.
 *
 * @package wpbrigade_sdk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dynamic opt-in/opt-out manager (plugin action links, modal, partial opt-out AJAX).
 */
class WPBRIGADE_Opt_Manager {

	/**
	 * Booted slugs (register once per product).
	 *
	 * @var array<string, bool>
	 */
	private static $booted = array();

	/**
	 * Slugs that already registered plugin_action_links_* filters.
	 *
	 * @var array<string, bool>
	 */
	private static $filters_registered = array();

	/**
	 * Whether the shared dismiss-verification-notice AJAX handler is registered.
	 *
	 * @var bool
	 */
	private static $dismiss_ajax_registered = false;

	/**
	 * @var array<string, mixed>
	 */
	private $module;

	/**
	 * @var string
	 */
	private $slug;

	/**
	 * @var string
	 */
	private $views_dir;

	/**
	 * @param array<string, mixed> $module Module definition from wpb_sdk_dynamic_init().
	 */
	public function __construct( array $module ) {
		$this->module    = $module;
		$this->slug      = (string) $module['slug'];
		$this->views_dir = trailingslashit( (string) $module['sdk_views_dir'] );
		$this->hooks();
	}

	/**
	 * @param array<string, mixed> $module Module definition.
	 * @return void
	 */
	public static function register_module( array $module ) {
		if ( empty( $module['slug'] ) || empty( $module['sdk_views_dir'] ) ) {
			return;
		}

		if ( function_exists( 'wpb_sdk_apply_module_defaults' ) ) {
			$module = wpb_sdk_apply_module_defaults( $module );
		}

		// Legacy plugins (wpb_dynamic_init, no provider sdk_version) own their own Opt In/Out links.
		if ( empty( $module['sdk_version'] ) || version_compare( (string) $module['sdk_version'], '3.2.0', '<' ) ) {
			return;
		}

		$slug = (string) $module['slug'];
		if ( ! empty( self::$booted[ $slug ] ) ) {
			return;
		}

		self::$booted[ $slug ] = true;
		new self( $module );
	}

	/**
	 * @return void
	 */
	private function hooks() {
		$slug        = $this->slug;
		$plugin_file = $this->plugin_basename();
		$skip_links  = function_exists( 'wpb_sdk_product_manages_own_opt_action_links' )
			&& wpb_sdk_product_manages_own_opt_action_links( $slug );

		if ( '' !== $plugin_file && ! $skip_links && empty( self::$filters_registered[ $slug ] ) ) {
			self::$filters_registered[ $slug ] = true;
			add_filter( 'plugin_action_links_' . $plugin_file, array( $this, 'sdk_action_links' ), 1 );
			add_filter(
				'network_admin_plugin_action_links_' . $plugin_file,
				array( $this, 'sdk_action_links' ),
				1
			);
		}

		add_action( 'admin_footer', array( $this, 'add_deactive_modal' ) );

		$prefix = $this->ajax_prefix();
		add_action( 'wp_ajax_' . $prefix . '_opt_out_option', array( $this, 'ajax_opt_out_option' ) );
		add_action( 'wp_ajax_' . $prefix . '_optin_yes', array( $this, 'ajax_optin_yes' ) );
		add_action( 'wp_ajax_' . $prefix . '_optout_yes', array( $this, 'ajax_optout_yes' ) );
		add_action( 'wp_ajax_' . $prefix . '_optin_skip', array( $this, 'ajax_optin_skip' ) );

		if ( '' !== $this->verified_meta_key() ) {
			add_action( 'admin_notices', array( $this, 'maybe_show_optin_verification_notice' ) );
			if ( ! self::$dismiss_ajax_registered ) {
				self::$dismiss_ajax_registered = true;
				add_action( 'wp_ajax_wpb_sdk_dismiss_verification_notice', array( __CLASS__, 'ajax_dismiss_verification_notice' ) );
			}
		}
	}

	/**
	 * @return string
	 */
	private function ajax_prefix() {
		$optin = isset( $this->module['optin'] ) && is_array( $this->module['optin'] ) ? $this->module['optin'] : array();
		if ( ! empty( $optin['ajax_prefix'] ) ) {
			return sanitize_key( (string) $optin['ajax_prefix'] );
		}

		return sanitize_key( str_replace( '-', '_', $this->slug ) );
	}

	/**
	 * @return string
	 */
	private function product_name() {
		$optin = isset( $this->module['optin'] ) && is_array( $this->module['optin'] ) ? $this->module['optin'] : array();
		if ( ! empty( $optin['product_name'] ) ) {
			return (string) $optin['product_name'];
		}

		$details = function_exists( 'wpb_sdk_get_plugin_details' ) ? wpb_sdk_get_plugin_details( $this->slug ) : null;
		if ( ! empty( $details['Name'] ) ) {
			return (string) $details['Name'];
		}

		return $this->slug;
	}

	/**
	 * @return string
	 */
	private function sdk_option_name() {
		return 'wpb_sdk_' . $this->slug;
	}

	/**
	 * @return string
	 */
	private function optin_option_name() {
		$optin = isset( $this->module['optin'] ) && is_array( $this->module['optin'] ) ? $this->module['optin'] : array();

		return ! empty( $optin['option_name'] ) ? (string) $optin['option_name'] : '';
	}

	/**
	 * @return bool
	 */
	private function uses_site_option() {
		$optin = isset( $this->module['optin'] ) && is_array( $this->module['optin'] ) ? $this->module['optin'] : array();

		return ! empty( $optin['use_site_option'] );
	}

	/**
	 * @param string $value Option value.
	 * @return void
	 */
	private function update_optin_flag( $value ) {
		$name = $this->optin_option_name();
		if ( '' === $name ) {
			return;
		}

		if ( $this->uses_site_option() ) {
			update_site_option( $name, $value );
			return;
		}

		update_option( $name, $value );
	}

	/**
	 * @return string
	 */
	/**
	 * @param mixed $value SDK flag value.
	 * @return bool
	 */
	private function sdk_option_is_enabled( $value ) {
		if ( function_exists( 'wpb_sdk_sdk_option_is_enabled' ) ) {
			return wpb_sdk_sdk_option_is_enabled( $value );
		}

		return in_array( strtolower( (string) $value ), array( '1', 'true', 'yes', 'on' ), true );
	}

	/**
	 * @param array<string, mixed> $sdk_data Decoded wpb_sdk_* option.
	 * @return bool
	 */
	private function all_sdk_sharing_disabled( array $sdk_data ) {
		$communication   = isset( $sdk_data['communication'] ) ? $sdk_data['communication'] : '0';
		$diagnostic_info = isset( $sdk_data['diagnostic_info'] ) ? $sdk_data['diagnostic_info'] : '0';
		$extensions      = isset( $sdk_data['extensions'] ) ? $sdk_data['extensions'] : '0';

		return ! $this->sdk_option_is_enabled( $communication )
			&& ! $this->sdk_option_is_enabled( $diagnostic_info )
			&& ! $this->sdk_option_is_enabled( $extensions );
	}

	/**
	 * @param array<string, mixed> $sdk_data Decoded wpb_sdk_* option.
	 * @return void
	 */
	private function maybe_clear_optin_after_full_optout( array $sdk_data ) {
		if ( 'yes' !== $this->get_optin_flag() || ! $this->all_sdk_sharing_disabled( $sdk_data ) ) {
			return;
		}

		$this->update_optin_flag( 'no' );
	}

	private function get_optin_flag() {
		$name = $this->optin_option_name();
		if ( '' === $name ) {
			return '';
		}

		if ( $this->uses_site_option() ) {
			return (string) get_site_option( $name, '' );
		}

		return (string) get_option( $name, '' );
	}

	/**
	 * @return string
	 */
	private function verified_meta_key() {
		if ( ! empty( $this->module['optin_user_meta']['verified'] ) ) {
			return (string) $this->module['optin_user_meta']['verified'];
		}

		return '';
	}

	/**
	 * @return string
	 */
	private function token_meta_key() {
		if ( ! empty( $this->module['optin_user_meta']['token'] ) ) {
			return (string) $this->module['optin_user_meta']['token'];
		}

		return '';
	}

	/**
	 * Reset verification state so Allow sends one fresh verification email.
	 *
	 * @return void
	 */
	private function reset_verification_state_for_optin() {
		$user_id = (int) get_current_user_id();
		if ( $user_id < 1 ) {
			return;
		}

		$verified_key = $this->verified_meta_key();
		if ( '' !== $verified_key ) {
			delete_user_meta( $user_id, $verified_key );
		}

		$token_key = $this->token_meta_key();
		if ( '' !== $token_key ) {
			delete_user_meta( $user_id, $token_key );
			delete_user_meta( $user_id, $token_key . '_expires' );
		}

		delete_option( 'wpb_sdk_' . $this->slug . '_initial_log_sent' );
		delete_option( 'wpb_sdk_' . $this->slug . '_fallback_verify_token' );

		$dismiss_key = $this->verification_notice_dismissed_meta_key();
		if ( '' !== $dismiss_key ) {
			delete_user_meta( $user_id, $dismiss_key );
		}
	}

	/**
	 * User meta key: verification email notice dismissed for this product.
	 *
	 * @return string
	 */
	private function verification_notice_dismissed_meta_key() {
		if ( function_exists( 'wpb_sdk_verification_notice_dismissed_meta_key' ) ) {
			return wpb_sdk_verification_notice_dismissed_meta_key( $this->slug );
		}

		return 'wpb_sdk_' . $this->slug . '_verify_notice_dismissed';
	}

	/**
	 * @return bool
	 */
	private function is_verification_notice_dismissed() {
		$user_id = (int) get_current_user_id();
		if ( $user_id < 1 ) {
			return false;
		}

		return '1' === (string) get_user_meta( $user_id, $this->verification_notice_dismissed_meta_key(), true );
	}

	/**
	 * Persist dismissal of the verification email admin notice (per user, per product).
	 *
	 * @return void
	 */
	public static function ajax_dismiss_verification_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1, '', array( 'response' => 403 ) );
		}

		check_ajax_referer( 'wpb_sdk_dismiss_verification_notice', 'nonce' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( (string) $_POST['slug'] ) ) : '';
		if ( '' === $slug ) {
			wp_die( -1 );
		}

		$user_id = (int) get_current_user_id();
		if ( $user_id < 1 ) {
			wp_die( -1 );
		}

		$dismiss_key = function_exists( 'wpb_sdk_verification_notice_dismissed_meta_key' )
			? wpb_sdk_verification_notice_dismissed_meta_key( $slug )
			: 'wpb_sdk_' . $slug . '_verify_notice_dismissed';
		if ( '' !== $dismiss_key ) {
			update_user_meta( $user_id, $dismiss_key, '1' );
		}
		wp_die();
	}

	/**
	 * Output dismiss handler script once per admin request.
	 *
	 * @return void
	 */
	private static function print_verification_notice_dismiss_script() {
		static $printed = false;
		if ( $printed ) {
			return;
		}
		$printed = true;

		$nonce = wp_create_nonce( 'wpb_sdk_dismiss_verification_notice' );
		?>
		<script>
		jQuery(function($) {
			$(document).on('click', '.wpb-sdk-verify-notice .notice-dismiss', function() {
				var $notice = $(this).closest('.wpb-sdk-verify-notice');
				var slug = $notice.data('wpb-sdk-slug');
				if (!slug) {
					return;
				}
				$.post(ajaxurl, {
					action: 'wpb_sdk_dismiss_verification_notice',
					slug: slug,
					nonce: <?php echo wp_json_encode( $nonce ); ?>
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Send activation telemetry after the AJAX response (avoids blocking on HTTP).
	 *
	 * @return void
	 */
	private function defer_activation_log() {
		$slug      = $this->slug;
		$module_id = isset( $this->module['id'] ) ? (string) $this->module['id'] : '';

		add_action(
			'shutdown',
			static function () use ( $slug, $module_id ) {
				if ( ! class_exists( 'WPBRIGADE_Logger', false ) ) {
					return;
				}

				$logger = WPBRIGADE_Logger::instance( $module_id, $slug, true );
				if ( $logger ) {
					$logger->log_activation( $slug );
				}
			},
			0
		);
	}

	/**
	 * @return string
	 */
	private function settings_admin_url() {
		if ( function_exists( 'wpb_sdk_resolve_optin_redirect_url' ) ) {
			return wpb_sdk_resolve_optin_redirect_url( $this->slug );
		}

		$optin = isset( $this->module['optin'] ) && is_array( $this->module['optin'] ) ? $this->module['optin'] : array();
		$page  = ! empty( $optin['settings_page'] ) ? (string) $optin['settings_page'] : '';

		return '' !== $page ? admin_url( 'admin.php?page=' . $page ) : admin_url( 'plugins.php' );
	}

	/**
	 * @return string
	 */
	private function optin_admin_url() {
		$optin = isset( $this->module['optin'] ) && is_array( $this->module['optin'] ) ? $this->module['optin'] : array();
		$page  = ! empty( $optin['optin_page'] ) ? (string) $optin['optin_page'] : '';

		if ( '' === $page && ! empty( $optin['settings_page'] ) ) {
			$page = (string) $optin['settings_page'];
		}

		return '' !== $page ? admin_url( 'admin.php?page=' . $page ) : $this->settings_admin_url();
	}

	/**
	 * @return string
	 */
	private function plugin_basename() {
		if ( function_exists( 'wpb_sdk_resolve_plugin_basename' ) ) {
			return wpb_sdk_resolve_plugin_basename( $this->slug );
		}

		$path = function_exists( 'wpb_get_plugin_path' ) ? wpb_get_plugin_path( $this->slug ) : '';
		if ( is_string( $path ) && '' !== $path && file_exists( $path ) ) {
			return plugin_basename( $path );
		}

		return '';
	}

	/**
	 * @return string Absolute path to opt-out view, or empty.
	 */
	private function optout_view_path() {
		$optin = isset( $this->module['optin'] ) && is_array( $this->module['optin'] ) ? $this->module['optin'] : array();
		$file  = ! empty( $optin['optout_view'] ) ? (string) $optin['optout_view'] : '';

		if ( '' === $file ) {
			$candidates = array(
				'wpb-sdk-optout-form.php',
				$this->slug . '-optout-form.php',
				str_replace( 'wp-', '', $this->slug ) . '-optout-form.php',
			);
			foreach ( $candidates as $candidate ) {
				$path = $this->views_dir . $candidate;
				if ( is_readable( $path ) ) {
					return $path;
				}
			}
			return '';
		}

		$path = $this->views_dir . ltrim( $file, '/\\' );

		return is_readable( $path ) ? $path : '';
	}

	/**
	 * @param array<int, string> $links Plugin row links.
	 * @return array<int, string>
	 */
	public function sdk_action_links( $links ) {
		$settings_link = $this->get_settings_link();
		if ( is_string( $settings_link ) && '' !== $settings_link ) {
			$links[] = $settings_link;
		}

		/**
		 * Append product-specific plugin row links (Settings, etc.) after SDK opt-in/out.
		 *
		 * @param array<int, string>   $links  Plugin row action links.
		 * @param string               $slug   Product slug.
		 * @param array<string, mixed> $module Module definition.
		 */
		$links = apply_filters( 'wpb_sdk_plugin_action_links', $links, $this->slug, $this->module );

		return $links;
	}

	/**
	 * Opted in via Allow but email verification not completed yet.
	 *
	 * @return bool
	 */
	private function is_pending_email_verification() {
		if ( '' === $this->verified_meta_key() ) {
			return false;
		}

		if (
			function_exists( 'wpb_sdk_is_optin_email_verified' )
			&& wpb_sdk_is_optin_email_verified( $this->slug )
		) {
			return false;
		}

		if ( 'yes' === $this->get_optin_flag() ) {
			return true;
		}

		$sdk_data = json_decode( (string) get_option( $this->sdk_option_name(), '' ), true );
		if ( ! is_array( $sdk_data ) ) {
			return false;
		}

		foreach ( array( 'communication', 'diagnostic_info', 'extensions' ) as $flag ) {
			$value = isset( $sdk_data[ $flag ] ) ? $sdk_data[ $flag ] : '0';
			if ( $this->sdk_option_is_enabled( $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return string
	 */
	private function get_settings_link() {
		$sdk_data = json_decode( (string) get_option( $this->sdk_option_name(), '' ), true );
		if ( ! is_array( $sdk_data ) ) {
			$sdk_data = array();
		}

		$communication   = isset( $sdk_data['communication'] ) ? $sdk_data['communication'] : '0';
		$diagnostic_info = isset( $sdk_data['diagnostic_info'] ) ? $sdk_data['diagnostic_info'] : '0';
		$extensions      = isset( $sdk_data['extensions'] ) ? $sdk_data['extensions'] : '0';

		$this->maybe_clear_optin_after_full_optout( $sdk_data );

		$has_sharing = $this->sdk_option_is_enabled( $communication )
			|| $this->sdk_option_is_enabled( $diagnostic_info )
			|| $this->sdk_option_is_enabled( $extensions );

		if ( $this->is_pending_email_verification() ) {
			return '';
		}

		$requires_verification = '' !== $this->verified_meta_key();
		$is_verified           = ! $requires_verification
			|| (
				function_exists( 'wpb_sdk_is_optin_email_verified' )
				&& wpb_sdk_is_optin_email_verified( $this->slug )
			);

		$settings_link = '';
		$settings_url  = $this->settings_admin_url();
		$optin_url     = $this->optin_admin_url();
		if ( $has_sharing && $is_verified ) {
			$settings_link .= sprintf(
				/* translators: 1: opening anchor, 2: closing anchor */
				esc_html__( '%1$s Opt Out %2$s  ', 'wpbrigade-sdk' ),
				'<a class="opt-out" href="' . esc_url( $settings_url ) . '">',
				'</a>'
			);
		} else {
			$settings_link .= sprintf(
				/* translators: 1: opening anchor, 2: closing anchor */
				esc_html__( '%1$s Opt In %2$s  ', 'wpbrigade-sdk' ),
				'<a href="' . esc_url( $optin_url ) . '">',
				'</a>'
			);
		}

		return $settings_link;
	}

	/**
	 * Admin notice while opted in but email verification is still pending.
	 *
	 * @return void
	 */
	public function maybe_show_optin_verification_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! $this->is_pending_email_verification() ) {
			return;
		}

		if ( $this->is_verification_notice_dismissed() ) {
			return;
		}

		$user = wp_get_current_user();
		if ( ! $user instanceof WP_User || '' === $user->user_email ) {
			return;
		}

		$this->optin_email_notice( $user->user_email );
	}

	/**
	 * @param string $email Admin email.
	 * @return void
	 */
	public function optin_email_notice( $email ) {
		if ( empty( $email ) ) {
			return;
		}

		$name = $this->product_name();

		printf(
			'<div class="notice notice-success is-dismissible wpb-sdk-verify-notice" data-wpb-sdk-slug="%1$s"><p style="color: green;">'
			. '<strong>%2$s</strong> '
			. '%3$s '
			. '<strong>%4$s</strong> '
			. '%5$s '
			. '<strong>%6$s</strong> '
			. '%7$s'
			. '</p></div>',
			esc_attr( $this->slug ),
			esc_html__( 'Thanks!', 'wpbrigade-sdk' ),
			esc_html__( 'You should receive a confirmation email ', 'wpbrigade-sdk' ),
			esc_html( $name ),
			esc_html__( 'to your mailbox ', 'wpbrigade-sdk' ),
			esc_html( $email ),
			esc_html__( 'Please make sure you click the link in that email to complete the opt-in.', 'wpbrigade-sdk' )
		);

		self::print_verification_notice_dismiss_script();
	}

	/**
	 * @return void
	 */
	public function add_deactive_modal() {
		global $pagenow;

		if ( 'plugins.php' !== $pagenow ) {
			return;
		}

		$optout = $this->optout_view_path();
		if ( is_readable( $optout ) ) {
			$wpb_sdk_plugin_slug     = $this->slug;
			$wpb_sdk_product_name    = $this->product_name();
			$wpb_sdk_ajax_prefix     = $this->ajax_prefix();
			$wpb_sdk_plugin_basename = $this->plugin_basename();
			$wpb_sdk_optout_nonce    = wp_create_nonce( $this->ajax_prefix() . '_optout_page_nonce' );
			include $optout;
		}
	}

	/**
	 * Sanitize and validate opt-out toggle POST fields.
	 *
	 * @return array{0: string, 1: string}|null Setting name and value, or null when invalid.
	 */
	private function parse_opt_out_ajax_settings() {
		$allowed = array( 'communication', 'diagnostic_info', 'extensions' );

		/**
		 * Filter allowed SDK opt-out setting keys for AJAX toggles.
		 *
		 * @param string[] $allowed Allowed setting names.
		 * @param string   $slug    Product slug.
		 */
		$allowed = apply_filters( 'wpb_sdk_allowed_opt_out_settings', $allowed, $this->slug );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in ajax_opt_out_option().
		$setting_name = isset( $_POST['setting_name'] )
			? sanitize_key( wp_unslash( (string) $_POST['setting_name'] ) )
			: '';

		if ( '' === $setting_name || ! in_array( $setting_name, $allowed, true ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in ajax_opt_out_option().
		$setting_value = isset( $_POST['setting_value'] )
			? sanitize_text_field( wp_unslash( (string) $_POST['setting_value'] ) )
			: '0';

		if ( ! in_array( $setting_value, array( '0', '1' ), true ) ) {
			$setting_value = '0';
		}

		return array( $setting_name, $setting_value );
	}

	/**
	 * @return void
	 */
	public function ajax_opt_out_option() {
		$prefix = $this->ajax_prefix();
		if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( $prefix . '_optout_page_nonce', 'optout_nonce' ) ) {
			wp_die(
				'<p>' . esc_html__( 'Sorry, you are not allowed to edit this item.', 'wpbrigade-sdk' ) . '</p>',
				'',
				array( 'response' => 403 )
			);
		}

		$parsed = $this->parse_opt_out_ajax_settings();
		if ( null === $parsed ) {
			wp_die( '0', '', array( 'response' => 400 ) );
		}

		list( $setting_name, $setting_value ) = $parsed;

		$sdk_data = json_decode( (string) get_option( $this->sdk_option_name(), '' ), true );
		if ( ! is_array( $sdk_data ) ) {
			$sdk_data = array();
		}

		$sdk_data[ $setting_name ] = $setting_value;
		update_option( $this->sdk_option_name(), wp_json_encode( $sdk_data ) );
		$this->maybe_clear_optin_after_full_optout( $sdk_data );
		wp_die( esc_html( $prefix . '_opt_out_option' ) );
	}

	/**
	 * @return void
	 */
	public function ajax_optin_yes() {
		$prefix = $this->ajax_prefix();
		if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( $prefix . '_optin_page_nonce', 'optin_yes_nonce' ) ) {
			wp_die(
				'<p>' . esc_html__( 'Sorry, you are not allowed to edit this item.', 'wpbrigade-sdk' ) . '</p>',
				'',
				array( 'response' => 403 )
			);
		}

		$this->reset_verification_state_for_optin();

		$sdk_data = array(
			'communication'   => '1',
			'diagnostic_info' => '1',
			'extensions'      => '1',
			'user_skip'       => '0',
		);
		update_option( $this->sdk_option_name(), wp_json_encode( $sdk_data ) );
		$this->update_optin_flag( 'yes' );

		if ( '' !== $this->verified_meta_key() ) {
			set_transient( 'wpb_sdk_' . $this->slug . '_pending_verify_notice', '1', DAY_IN_SECONDS );
		}

		$this->defer_activation_log();

		wp_die( '' );
	}

	/**
	 * @return void
	 */
	public function ajax_optout_yes() {
		$prefix = $this->ajax_prefix();
		if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( $prefix . '_optin_page_nonce', 'optout_yes_nonce' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'wpbrigade-sdk' ), 403 );
		}

		$sdk_data = get_option( $this->sdk_option_name(), array() );
		if ( ! is_array( $sdk_data ) ) {
			$sdk_data = json_decode( (string) $sdk_data, true );
		}
		if ( ! is_array( $sdk_data ) ) {
			$sdk_data = array();
		}

		$sdk_data['communication']   = '0';
		$sdk_data['diagnostic_info'] = '0';
		$sdk_data['extensions']      = '0';

		update_option( $this->sdk_option_name(), wp_json_encode( $sdk_data ) );
		$this->update_optin_flag( 'no' );

		wp_send_json_success();
	}

	/**
	 * @return void
	 */
	public function ajax_optin_skip() {
		$prefix = $this->ajax_prefix();
		if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( $prefix . '_optin_page_nonce', 'optin_skip_nonce' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'wpbrigade-sdk' ), 403 );
		}

		$sdk_data = array(
			'communication'   => '0',
			'diagnostic_info' => '0',
			'extensions'      => '0',
			'user_skip'       => '1',
		);
		update_option( $this->sdk_option_name(), wp_json_encode( $sdk_data ) );
		$this->update_optin_flag( 'skip' );

		$this->defer_activation_log();

		wp_send_json_success();
	}
}
