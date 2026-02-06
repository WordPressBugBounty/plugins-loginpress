<?php

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LoginPress Remote Notification Client Trait
 *
 * This trait is part of the Remote Dashboard Notifications plugin.
 * Method originally defined in `class-remote-notification-client.php` to keep the main file slim.
 *
 * @package   LoginPress
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @link      http://wordpress.org/plugins/remote-dashboard-notifications/
 * @link      https://github.com/ThemeAvenue/Remote-Dashboard-Notifications
 * @copyright 2016 ThemeAvenue
 */

if ( ! trait_exists( 'LoginPress_Remote_Notification_Trait' ) ) {
	/**
	 * LoginPress Remote Notification Client Trait
	 *
	 * This trait is part of the Remote Dashboard Notifications plugin.
	 * Method originally defined in `class-remote-notification-client.php` to keep the main file slim.
	 *
	 * @package   LoginPress
	 * @subpackage Traits
	 * @since     6.1.0
	 */
	trait LoginPress_Remote_Notification_Trait {

		/**
		 * Register a new remote notification.
		 *
		 * @since 1.3.0
		 *
		 * @param int    $channel_id  Channel ID on the remote server.
		 * @param string $channel_key Channel key for authentication with the server.
		 * @param string $server      Notification server URL.
		 * @param int    $cache       Cache lifetime (in hours).
		 *
		 * @return bool|string
		 */
		public function add_notification( $channel_id, $channel_key, $server, $cache = 6 ) {

			$notification = array(
				'channel_id'     => (int) $channel_id,
				'channel_key'    => $channel_key,
				'server_url'     => esc_url( $server ),
				'cache_lifetime' => apply_filters( 'rn_notice_caching_time', $cache ),
			);

			// Generate the notice unique ID.
			$notification['notice_id'] = $notification['channel_id'] . substr( $channel_key, 0, 5 );

			// Double check that the required info is here.
			if ( empty( $notification['channel_id'] ) || empty( $notification['channel_key'] ) || empty( $notification['server_url'] ) ) {
				return false;
			}

			// Check that there is no notification with the same ID.
			if ( array_key_exists( $notification['notice_id'], $this->notifications ) ) {
				return false;
			}

			$this->notifications[ $notification['notice_id'] ] = $notification;

			return $notification['notice_id'];
		}

		/**
		 * Remove a registered notification.
		 *
		 * @since 1.3.0
		 *
		 * @param string $notice_id ID of the notice to remove.
		 *
		 * @return void
		 */
		public function remove_notification( $notice_id ) {
			if ( array_key_exists( $notice_id, $this->notifications ) ) {
				unset( $this->notifications[ $notice_id ] );
			}
		}

		/**
		 * Get all registered notifications.
		 *
		 * @since 1.3.0
		 * @return array<string, array<string, mixed>>
		 */
		public function get_notifications() {
			return $this->notifications;
		}

		/**
		 * Get a specific notification.
		 *
		 * @since 1.3.0
		 *
		 * @param string $notice_id ID of the notice to retrieve.
		 *
		 * @return array<string, mixed>|false
		 */
		public function get_notification( $notice_id ) {

			if ( ! array_key_exists( $notice_id, $this->notifications ) ) {
				return false;
			}

			return $this->notifications[ $notice_id ];
		}

		/**
		 * Check if the notification has started yet.
		 *
		 * @since 1.2.0
		 *
		 * @param object $notification The notification object.
		 *
		 * @return bool
		 */
		protected function is_notification_started( $notification ) {

			if ( ! isset( $notification->date_start ) ) {
				return true;
			}

			if ( empty( $notification->date_start ) || strtotime( $notification->date_start ) < time() ) {
					return true;
			}

			return false;
		}

		/**
		 * Check if the notification has expired.
		 *
		 * @since 1.2.0
		 *
		 * @param object $notification The notification object.
		 *
		 * @return bool
		 */
		protected function has_notification_ended( $notification ) {

			if ( ! isset( $notification->date_end ) ) {
				return false;
			}

			if ( empty( $notification->date_end ) || strtotime( $notification->date_end ) > time() ) {
				return false;
			}

			return true;
		}

		/**
		 * Get the remote notification object.
		 *
		 * @since 1.3.0
		 *
		 * @param array<string, mixed> $notification The notification data array.
		 *
		 * @return object|false
		 */
		protected function get_remote_notification( $notification ) {

			$content = get_transient( 'rn_last_notification_' . $notification['notice_id'] );

			if ( false === $content ) {
				add_option( 'rdn_fetch_' . $notification['notice_id'], 'fetch' );
			}

			return $content;
		}


		/**
		 * Maybe get a notification from the remote server.
		 *
		 * @since 1.2.0
		 *
		 * @param array<string, mixed> $notification The notification data array.
		 *
		 * @return string|WP_Error
		 */
		protected function remote_get_notification( $notification ) {

			/* Query the server. */
			$response = wp_remote_get( $this->build_query_url( $notification['server_url'], $this->get_payload( $notification ) ), array( 'timeout' => apply_filters( 'rn_http_request_timeout', 5 ) ) );

			/* If we have a WP_Error object we abort. */
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				$response_code = wp_remote_retrieve_response_code( $response );
				// translators: Invalid code.
				$message = sprintf( esc_html__( 'The server response was invalid (code %s)', 'loginpress' ), $response_code ?: 'unknown' );
				return new WP_Error( 'invalid_response', $message );
			}

			$body = wp_remote_retrieve_body( $response );

			if ( empty( $body ) ) {
				return new WP_Error( 'empty_response', esc_html__( 'The server response is empty', 'loginpress' ) );
			}

			$body = json_decode( $body );

			if ( is_null( $body ) ) {
				return new WP_Error( 'json_decode_error', esc_html__( 'Cannot decode the response content', 'loginpress' ) );
			}

			set_transient( 'rn_last_notification_' . $notification['notice_id'], $body, $notification['cache_lifetime'] * 60 * 60 );
			delete_option( 'rdn_fetch_' . $notification['notice_id'] );

			if ( $this->is_notification_error( $body ) ) {
				/** @phpstan-ignore-next-line */
				return new WP_Error( 'notification_error', $this->get_notification_error_message( $body ) );
			}

			return $body;
		}

		/**
		 * Check if the notification returned by the server is an error.
		 *
		 * @since 1.2.0
		 *
		 * @param object $notification Notification returned.
		 *
		 * @return bool
		 */
		protected function is_notification_error( $notification ) {

			if ( false === $this->get_notification_error_message( $notification ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Get the error message returned by the remote server.
		 *
		 * @since 1.2.0
		 *
		 * @param object $notification Notification returned.
		 *
		 * @return bool|string
		 */
		protected function get_notification_error_message( $notification ) {

			/** @phpstan-ignore-next-line */
			if ( ! is_object( $notification ) ) {
				return false;
			}

			if ( ! isset( $notification->error ) ) {
				return false;
			}

			return sanitize_text_field( $notification->error );
		}
	}
}
