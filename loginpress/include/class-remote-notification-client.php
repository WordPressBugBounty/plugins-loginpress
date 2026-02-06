<?php
/**
 * LoginPress Remote Dashboard Notifications Client Class.
 *
 * Remote Dashboard Notifications.
 *
 * This class is part of the Remote Dashboard Notifications plugin.
 * This plugin allows you to send notifications to your client's
 * WordPress dashboard easily.
 *
 * Notification you send will be displayed as admin notifications
 * using the standard WordPress hooks. A "dismiss" option is added
 * in order to let the user hide the notification.
 *
 * @package   LoginPress
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @link      http://wordpress.org/plugins/remote-dashboard-notifications/
 * @link      https://github.com/ThemeAvenue/Remote-Dashboard-Notifications
 * @copyright 2016 ThemeAvenue
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Remote_Dashboard_Notifications_Client' ) ) {
	include LOGINPRESS_DIR_PATH . 'include/loginpress-remote-notification-trait.php';
	/**
	 * LoginPress Remote Dashboard Notifications Client Class.
	 *
	 * Handles remote dashboard notifications for LoginPress.
	 *
	 * @package   LoginPress
	 * @since 1.3.0
	 */
	final class Remote_Dashboard_Notifications_Client {
		use LoginPress_Remote_Notification_Trait;

		/**
		 * Holds the unique instance of the class.
		 *
		 * @var Remote_Dashboard_Notifications_Client
		 * @since 1.3.0
		 */
		private static $instance;

		/**
		 * Minimum version of WordPress required to run the plugin.
		 *
		 * @since 1.3.0
		 * @var string
		 */
		public $wordpress_version_required = '3.8';

		/**
		 * Required version of PHP.
		 *
		 * Follow WordPress latest requirements and require
		 * PHP version 5.2 at least.
		 *
		 * @since 1.3.0
		 * @var string
		 */
		public $php_version_required = '5.2';

		/**
		 * Holds all the registered notifications.
		 *
		 * @since 1.3.0
		 * @var array<string, array<string, mixed>>
		 */
		public $notifications = array();

		/**
		 * Instantiate and return the unique object.
		 *
		 * @since     1.2.0
		 * @return object Remote_Dashboard_Notifications_Client Unique instance.
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				self::$instance = new Remote_Dashboard_Notifications_Client();
				self::$instance->init();
			}

			return self::$instance;
		}

		/**
		 * Instantiate the plugin.
		 *
		 * @since 1.3.0
		 * @return void
		 */
		private function init() {

			// Make sure the WordPress version is recent enough.
			if ( ! self::$instance->is_version_compatible() ) {
				return;
			}

			// Make sure we have a version of PHP that's not too old.
			if ( ! self::$instance->is_php_version_enough() ) {
				return;
			}

			// Call the dismiss method before testing for Ajax.
			if ( isset( $_GET['rn'] ) && isset( $_GET['notification'] ) ) {
				add_action( 'plugins_loaded', array( self::$instance, 'dismiss' ) );
			}

			if ( ! wp_doing_ajax() ) {
				add_action( 'admin_print_styles', array( self::$instance, 'style' ), 100 );
				add_action( 'admin_notices', array( self::$instance, 'show_notices' ) );
				add_action( 'admin_footer', array( self::$instance, 'script' ) );
			}

			add_action( 'wp_ajax_rdn_fetch_notifications', array( $this, 'remote_get_notice_ajax' ) );
			add_filter( 'heartbeat_received', array( self::$instance, 'heartbeat' ), 10, 2 );
		}

		/**
		 * Throw error on object clone.
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since 3.2.5
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'loginpress' ), '3.2.5' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @since 3.2.5
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'loginpress' ), '3.2.5' );
		}

		/**
		 * Check if the core version is compatible with this addon.
		 *
		 * @since  1.3.0
		 * @return boolean
		 */
		private function is_version_compatible() {

			if ( empty( self::$instance->wordpress_version_required ) ) {
				return true;
			}

			if ( version_compare( get_bloginfo( 'version' ), self::$instance->wordpress_version_required, '<' ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Check if the version of PHP is compatible with this addon.
		 *
		 * @since  1.3.0
		 * @return boolean
		 */
		private function is_php_version_enough() {

			/**
			 * No version set, we assume everything is fine.
			 */
			if ( empty( self::$instance->php_version_required ) ) {
				return true;
			}

			if ( version_compare( phpversion(), self::$instance->php_version_required, '<' ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Adds inline style for non standard notices.
		 *
		 * This function will only be called if the notice style is not standard.
		 *
		 * @since 0.1.0
		 * @return void
		 */
		public function style() {
			?>
			<style type="text/css">div.rn-alert{padding:15px 35px 15px 15px;margin-bottom:20px;border:1px solid transparent;-webkit-box-shadow:none;box-shadow:none}div.rn-alert p:empty{display:none}div.rn-alert ol,div.rn-alert ol li,div.rn-alert ul,div.rn-alert ul li{list-style:inherit!important}div.rn-alert ol,div.rn-alert ul{padding-left:30px}div.rn-alert hr{-moz-box-sizing:content-box;box-sizing:content-box;height:0;margin-top:20px;margin-bottom:20px;border:0;border-top:1px solid #eee}div.rn-alert h1,div.rn-alert h2,div.rn-alert h3,div.rn-alert h4,div.rn-alert h5,div.rn-alert h6{margin-top:0;color:inherit}div.rn-alert a{font-weight:700}div.rn-alert a:hover{text-decoration:underline}div.rn-alert>p{margin:0;padding:0;line-height:1}div.rn-alert>p,div.rn-alert>ul{margin-bottom:0}div.rn-alert>p+p{margin-top:5px}div.rn-alert .rn-dismiss-btn{font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;position:relative;top:-2px;right:-21px;padding:0;cursor:pointer;background:0;border:0;-webkit-appearance:none;float:right;font-size:21px;font-weight:700;line-height:1;color:#000;text-shadow:0 1px 0 #fff;opacity:.2;filter:alpha(opacity=20);text-decoration:none}div.rn-alert-success{background-color:#dff0d8;border-color:#d6e9c6;color:#3c763d}div.rn-alert-success hr{border-top-color:#c9e2b3}div.rn-alert-success a{color:#2b542c}div.rn-alert-info{background-color:#d9edf7;border-color:#bce8f1;color:#31708f}div.rn-alert-info hr{border-top-color:#a6e1ec}div.rn-alert-info a{color:#245269}div.rn-alert-warning{background-color:#fcf8e3;border-color:#faebcc;color:#8a6d3b}div.rn-alert-warning hr{border-top-color:#f7e1b5}div.rn-alert-warning a{color:#66512c}div.rn-alert-danger{background-color:#f2dede;border-color:#ebccd1;color:#a94442}div.rn-alert-danger hr{border-top-color:#e4b9c0}div.rn-alert-danger a{color:#843534}</style>
			<?php
		}

		/**
		 * Display all the registered and available notifications.
		 *
		 * @since 1.3.0
		 * @return void
		 */
		public function show_notices() {

			foreach ( $this->notifications as $id => $notification ) {

				$rn = $this->get_remote_notification( $notification );

				if ( empty( $rn ) || is_wp_error( $rn ) ) {
					continue;
				}

				if ( $this->is_notification_error( $rn ) ) {
					continue;
				}

				if ( isset( $rn->slug ) && $this->is_notice_dismissed( $rn->slug ) ) {
					continue;
				}

				if ( $this->is_post_type_restricted( $rn ) ) {
					continue;
				}

				if ( ! $this->is_notification_started( $rn ) ) {
					continue;
				}

				if ( $this->has_notification_ended( $rn ) ) {
					continue;
				}

				// Output the admin notice.
				$message = isset( $rn->message ) ? $rn->message : '';
				$slug    = isset( $rn->slug ) ? $rn->slug : '';
				$this->create_admin_notice( $message, $this->get_notice_class( isset( $rn->style ) ? $rn->style : 'updated' ), $this->get_notice_dismissal_url( $slug ) );

			}
		}

		/**
		 * Check if the notification has been dismissed.
		 *
		 * @since 1.2.0
		 *
		 * @param string $slug Slug of the notice to check.
		 *
		 * @return bool
		 */
		protected function is_notice_dismissed( $slug ) {

			global $current_user;

			$dismissed = array_filter( (array) get_user_meta( $current_user->ID, '_rn_dismissed', true ) );

			if ( in_array( $slug, $dismissed ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Check if the notification can be displayed for the current post type.
		 *
		 * @since 1.2.0
		 *
		 * @param object $notification The notification object.
		 *
		 * @return bool
		 */
		protected function is_post_type_restricted( $notification ) {

			/* If the type array isn't empty we have a limitation. */
			if ( isset( $notification->type ) && is_array( $notification->type ) && ! empty( $notification->type ) ) {

				/* Get current post type. */
				$pt = get_post_type();

				/**
				 * If the current post type can't be retrieved
				 * or if it's not in the allowed post types,
				 * then we don't display the admin notice.
				 */
				if ( false === $pt || ! in_array( $pt, $notification->type ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Get the admin notice class attribute.
		 *
		 * @since 1.3.0
		 *
		 * @param string $style Notification style
		 *
		 * @return string
		 */
		protected function get_notice_class( $style ) {

			switch ( $style ) {
				case 'updated':
					$class = $style;
					break;

				case 'error':
					$class = 'updated error';
					break;

				default:
					$class = "updated rn-alert rn-alert-$style";
			}

			return $class;
		}

		/**
		 * Prepare the dismissal URL for the notice.
		 *
		 * @since 1.3.0
		 *
		 * @param string $slug Notice slug
		 *
		 * @return string
		 */
		protected function get_notice_dismissal_url( $slug ) {

			$args                 = array_map( 'sanitize_text_field', wp_unslash( $_GET ) );
			$args['rn']           = wp_create_nonce( 'rn-dismiss' );
			$args['notification'] = trim( $slug );

			return esc_url( add_query_arg( $args, '' ) );
		}

		/**
		 * Create the actual admin notice.
		 *
		 * @since 1.3.0
		 *
		 * @param string $contents Notice contents
		 * @param string $class    Wrapper class
		 * @param string $dismiss  Dismissal link
		 *
		 * @return void
		 */
		protected function create_admin_notice( $contents, $class, $dismiss ) {
			?>
			<div class="<?php echo esc_attr( $class ); ?>">
				<a href="<?php echo esc_url( $dismiss ); ?>" id="rn-dismiss" class="rn-dismiss-btn" title="<?php esc_attr_e( 'Dismiss notification', 'loginpress' ); ?>">&times;</a>
				<p><?php echo wp_kses_post( html_entity_decode( $contents ) ); ?></p>
			</div>
			<?php
		}

		/**
		 * Dismiss notice
		 *
		 * When the user dismisses a notice, its slug
		 * is added to the _rn_dismissed entry in the DB options table.
		 * This entry is then used to check if a notice has been dismissed
		 * before displaying it on the dashboard.
		 *
		 * @since 0.1.0
		 * @return void
		 */
		public function dismiss() {

			global $current_user;

			/* Check if we have all the vars. */
			if ( ! isset( $_GET['rn'] ) || ! isset( $_GET['notification'] ) ) {
				return;
			}

			/* Validate nonce. */
			if ( ! wp_verify_nonce( sanitize_key( $_GET['rn'] ), 'rn-dismiss' ) ) {
				return;
			}

			/* Get dismissed list. */
			$dismissed = array_filter( (array) get_user_meta( $current_user->ID, '_rn_dismissed', true ) );

			/* Add the current notice to the list if needed. */
			if ( ! in_array( $_GET['notification'], $dismissed ) ) {
				array_push( $dismissed, $_GET['notification'] );
			}

			/* Update option. */
			update_user_meta( $current_user->ID, '_rn_dismissed', $dismissed );
		}

		/**
		 * Adds the script that hooks into the Heartbeat API.
		 *
		 * @since 1.3.0
		 * @return void
		 */
		public function script() {

			$maybe_fetch = array();

			foreach ( $this->get_notifications() as $id => $n ) {
				$maybe_fetch[] = (string) $id;
			}

			if ( false === get_transient( 'loginpress_rdn_fetch_notifications' ) ) {
				?>

				<script type="text/javascript">
					jQuery(document).ready(function ($) {

						// Hook into the heartbeat-send.
						$(document).on('heartbeat-send', function (e, data) {
							data['rdn_maybe_fetch'] = <?php echo json_encode( $maybe_fetch ); ?>;
						});

						// Listen for the custom event "heartbeat-tick" on $(document).
						$(document).on('heartbeat-tick', function (e, data) {

							if (data.rdn_fetch !== '') {

								ajax_data = {
									'action': 'rdn_fetch_notifications',
									'notices': data.rdn_fetch
								};

								$.post(ajaxurl, ajax_data);

							}

						});
					});
				</script>
				<?php
			}
		}

		/**
		 * Hook into the Heartbeat API.
		 *
		 * @since 1.3.0
		 *
		 * @param  array<string, mixed> $response Heartbeat tick response.
		 * @param  array<string, mixed> $data     Heartbeat tick data.
		 *
		 * @return array<string, mixed>           Updated Heartbeat tick response.
		 */
		function heartbeat( $response, $data ) {

			if ( isset( $data['rdn_maybe_fetch'] ) ) {

				$notices = $data['rdn_maybe_fetch'];

				if ( ! is_array( $notices ) ) {
					$notices = array( $notices );
				}

				foreach ( $notices as $notice_id ) {

					$fetch = get_option( "rdn_fetch_$notice_id", false );

					if ( 'fetch' === $fetch ) {

						if ( ! isset( $response['rdn_fetch'] ) ) {
							$response['rdn_fetch'] = array();
						}

						$response['rdn_fetch'][] = $notice_id;

					}
				}
			}

			return $response;
		}

		/**
		 * Triggers the remote requests that fetches notices for this particular instance.
		 *
		 * @since 1.3.0
		 * @return void
		 */
		public function remote_get_notice_ajax() {
			// Transient set for 1 week.
			set_transient( 'loginpress_rdn_fetch_notifications', 'rdn_fetch_notifications', 604800 );

			if ( isset( $_POST['notices'] ) ) {
				$notices = sanitize_text_field( wp_unslash( $_POST['notices'] ) );
			} else {
				echo 'No notice ID';
				die();
			}

			if ( ! is_array( $notices ) ) {
				$notices = array( $notices );
			}

			foreach ( $notices as $notice_id ) {

				$notification = $this->get_notification( $notice_id );
				if ( false === $notification ) {
					continue;
				}
				$rn = $this->remote_get_notification( $notification );

				if ( is_wp_error( $rn ) ) {
					echo $rn->get_error_message();
				} else {
					echo json_encode( $rn );
				}
			}

			die();
		}

		/**
		 * Get the remote server URL.
		 *
		 * @since 1.2.0
		 *
		 * @param string $url THe server URL to sanitize.
		 *
		 * @return string
		 */
		protected function get_remote_url( $url ) {

			$url = explode( '?', $url );

			return esc_url( $url[0] );
		}

		/**
		 * Get the payload required for querying the remote server.
		 *
		 * @since 1.2.0
		 *
		 * @param array<string, mixed> $notification The notification data array.
		 *
		 * @return string
		 */
		protected function get_payload( $notification ) {
			$payload = json_encode(
				array(
					'channel' => isset( $notification['channel_id'] ) && ! empty( $notification['channel_id'] ) ? $notification['channel_id'] : '',
					'key'     => isset( $notification['channel_key'] ) && ! empty( $notification['channel_key'] ) ? $notification['channel_key'] : '',
				)
			);
			return base64_encode( $payload ?: '' );
		}

		/**
		 * Get the full URL used for the remote get.
		 *
		 * @since 1.2.0
		 *
		 * @param string $url     The remote server URL
		 * @param string $payload The encoded payload
		 *
		 * @return string
		 */
		protected function build_query_url( $url, $payload ) {
			return add_query_arg(
				array(
					'post_type' => 'notification',
					'payload'   => $payload,
				),
				$this->get_remote_url( $url )
			);
		}
	}

}

/**
 * The main function responsible for returning the unique RDN client.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @since 1.3.0
 * @return object Remote_Dashboard_Notifications_Client
 */
function RDNC() {
	return Remote_Dashboard_Notifications_Client::instance();
}

// Get Awesome Support Running
RDNC();

/**
 * Register a new remote notification.
 *
 * Helper function for registering new notifications through the Remote_Dashboard_Notifications_Client class.
 *
 * @since 1.3.0
 *
 * @param int|false    $channel_id Channel ID.
 * @param string|false $channel_key Channel key.
 * @param string|false $server Server URL.
 * @param int          $cache       Cache lifetime (in hours)
 *
 * @return bool|string
 */
function rdnc_add_notification( $channel_id, $channel_key, $server, $cache = 6 ) {
	/** @phpstan-ignore-next-line */
	return RDNC()->add_notification( $channel_id, $channel_key, $server, $cache );
}

if ( ! class_exists( 'TAV_Remote_Notification_Client' ) ) {

	/**
	 * Class TAV_Remote_Notification_Client
	 *
	 * This class, even though deprecated, is kept here for backwards compatibility. It is now just a wrapper for the new notification registration method.
	 *
	 * @deprecated @1.3.0
	 */
	class TAV_Remote_Notification_Client {

		/**
		 * Constructor for backwards compatibility.
		 *
		 * @param int|false    $channel_id Channel ID.
		 * @param string|false $channel_key Channel key.
		 * @param string|false $server Server URL.
		 */
		public function __construct( $channel_id = false, $channel_key = false, $server = false ) {
			rdnc_add_notification( $channel_id, $channel_key, $server );
		}
	}

}
