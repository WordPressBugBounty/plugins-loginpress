<?php
/**
 * LoginPress Addons Class.
 *
 * This is an Add-ons page. Purpose of this page is to show a list of all the add-ons available to extend the functionality of LoginPress.
 * Loads the LoginPress_Addons class and its required trait if not already defined.
 *
 * @package LoginPress
 * @since 1.0.19
 * @version 3.0.5
 */

// Check if the class doesn't already exist and the plugin directory path is defined.
if ( ! class_exists( 'LoginPress_Addons' ) ) :
	if ( defined( 'LOGINPRESS_DIR_PATH' ) ) {
		// Include the trait required by the LoginPress_Addons class.
		require_once LOGINPRESS_DIR_PATH . 'classes/traits/loginpress-addons-trait.php';
	}

	/**
	 * LoginPress Addons Class.
	 *
	 * Handles the display and management of LoginPress addons.
	 *
	 * @package LoginPress
	 * @since 1.0.19
	 * @version 3.0.5
	 */
	class LoginPress_Addons {
		use LoginPress_Addons_Trait;

		/**
		 * Stores a collection of addon data, or false/null if unavailable.
		 *
		 * @var array<string, mixed>|false|null Addons data retrieved from storage or API.
		 */
		private $addons_array;

		/**
		 * Stores addon metadata for display and functionality.
		 *
		 * @var array<string, mixed>|null Addon metadata retrieved from storage.
		 */
		private $addons_meta;

		/**
		 * List of all installed plugins on the site.
		 *
		 * @var array<string, mixed> WordPress plugins list with activation status.
		 */
		protected $plugins_list;

		/**
		 * Class Constructor.
		 *
		 * @since 1.0.19
		 * @return void
		 */
		public function __construct() {
			$this->includes();
			$this->plugins_list = get_plugins();
			$this->addons_array = get_option( 'loginpress_pro_addons' );
		}

		/**
		 * Include the addons meta file.
		 *
		 * @since 1.0.19
		 * @return void
		 */
		private function includes() {
			if ( defined( 'LOGINPRESS_DIR_PATH' ) ) {
				require_once LOGINPRESS_DIR_PATH . 'classes/class-loginpress-addons-meta.php';
			}
		}

		/**
		 * Construct addons array.
		 *
		 * @since 3.0.5
		 * @return void
		 */
		public function addons_array_construct() {

			$this->addons_array = get_option( 'loginpress_pro_addons' );
			$this->addons_meta  = LoginPress_Addons_Meta::addons_details();
		}

		/**
		 * Render addons page.
		 *
		 * @since 1.0.19
		 * @version 3.0.5
		 * @return void HTML of addons management page with cards and controls.
		 */
		public function show_addon_page() {

			if ( class_exists( 'LoginPress_Pro' ) ) {

				/**
				 * Filter to exclude certain addons from the Addons page.
				 *
				 * @param array $excluded_slugs Array of addon slugs to exclude.
				 * @since 6.1.0
				 */
				$excluded_slugs = apply_filters( 'loginpress_excluded_addons', array() );
				if ( LoginPress_Pro::is_activated() ) {

					$expiration_date = LoginPress_Pro::get_expiration_date();

					if ( 'lifetime' === $expiration_date ) {
						echo esc_html__( 'You have a lifetime license, it will never expire.', 'loginpress' );
					} else {
						echo '<div class="main_notice_msg">' . sprintf(
							// translators: License key validity.
							esc_html__( 'Your (%2$s) license key is valid until %1$s.', 'loginpress' ),
							'<strong>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $expiration_date, current_time( 'timestamp' ) ) ) ) . '</strong>', //phpcs:ignore
							esc_html( LoginPress_Pro::get_license_type() )
						) . '</div>';
					} ?>

					<div class="addon_cards_wraper"> 
						<?php
						if ( ! empty( $this->addons_array ) && false !== $this->addons_array ) {
							foreach ( $this->addons_array as $addon ) {
								if ( in_array( $addon['slug'], $excluded_slugs, true ) ) {
									continue; // Skip excluded addon.
								}
								$this->addon_card( $addon );
							}
						}
						?>
					</div> 
					<?php
				} else {
					$expiration_date = LoginPress_Pro::get_expiration_date();
					$license_data    = LoginPress_Pro::get_registration_data();

					if ( isset( $license_data['license_data']['error'] ) && 'expired' === $license_data['license_data']['error'] ) {
						echo '<div class="main_notice_msg">' . sprintf(
							// translators: License expiration.
							esc_html__( 'Your license key has been expired on %1$s.', 'loginpress' ),
							esc_html( date_i18n( get_option( 'date_format' ), strtotime( $expiration_date, current_time( 'timestamp' ) ) ) )  //phpcs:ignore
						) . '</div>';
					}

					?>
					<div class="addon_cards_wraper"> 
						<?php
						if ( ! empty( $this->addons_array ) && false !== $this->addons_array ) {
							foreach ( $this->addons_array as $addon ) {
								if ( in_array( $addon['slug'], $excluded_slugs, true ) ) {
									continue; // Skip excluded addon.
								}
								$this->addon_card( $addon );
							}
						}
						?>
					</div> 
					<?php
				}
			} else {
				echo '<div class="main_notice_msg">' . sprintf( esc_html__( 'You need to upgrade to LoginPress Pro to access these add-ons.', 'loginpress' ) ) . '</div>';
				?>

				<div class="addon_cards_wraper"> 
				<?php

				if ( isset( $this->addons_array ) && ! empty( $this->addons_array ) ) {
					foreach ( $this->addons_array as $addon ) {
						$this->addon_card_free( $addon );
					}
				}
				?>
				</div> 
		
				<?php
			}
		}

		/**
		 * Generate pro addons card.
		 *
		 * @param array<string, mixed> $addon Addon data array containing title, excerpt, slug, and media.
		 * @since 1.0.19
		 * @version 3.0.5
		 * @return void HTML of individual addon card with title, description, and action buttons.
		 */
		public function addon_card( $addon ) {

			$addon_slug  = $addon['slug'];
			$addon_thumb = ( defined( 'LOGINPRESS_DIR_URL' ) ? LOGINPRESS_DIR_URL : '' ) . 'img/addons/' . $addon_slug . '.png';
			?>

			<div class="loginpress-extension <?php echo esc_attr( true === $addon['is_free'] ? 'loginpress-free-add-ons' : '' ); ?> ">
				<a target="_blank" href="<?php echo esc_url( 'https://wpbrigade.com/wordpress/plugins/loginpress-pro/?utm_source=loginpress-lite&utm_medium=addons-coming-soon&utm_campaign=pro-upgrade' ); ?>"  class="logoinpress_addons_links">
					<h3>
						<img src=<?php echo esc_url( $addon_thumb ); ?> class="logoinpress_addons_thumbnails"/>
						<span><?php echo esc_html( $this->addons_meta[ $addon_slug ]['title'] ?? '' ); ?></span>
					</h3>
				</a>
				<?php echo '<p>' . esc_html( $this->addons_meta[ $addon_slug ]['excerpt'] ?? '' ) . '</p>'; ?>
				<p><?php $this->check_addon_status( $addon ); ?></p>
				<p><?php echo $this->ajax_response( $this->addons_meta[ $addon_slug ]['title'] ?? '', $addon['slug'] ); //phpcs:ignore ?></p>
				</div>
			<?php
		}

		/**
		 * Ajax workflow.
		 *
		 * @param string $text Display text for the AJAX response message.
		 * @param string $slug Plugin slug identifier for the response.
		 * @since 1.0.19
		 * @version 3.0.5
		 * @return string HTML of AJAX response message with success/error styling.
		 */
		public function ajax_response( $text, $slug ) {

			if ( $this->license_life( $slug ) ) {
				// translators: Something wrong.
				$message = sprintf( esc_html__( '%s Something Wrong.', 'loginpress' ), $text );
			} else {
				// translators: Invalid license key.
				$message = esc_html__( 'Your License Key isn\'t valid', 'loginpress' );
			}

			$html = '<div id="loginpressEnableAddon' . esc_attr( $slug ) . '" class="loginpress-addon-enable" style="display:none;">
				<div class="loginpress-logo-container">
				<img src="' . plugins_url( '../../loginpress/img/loginpress-logo-divid-logo.svg', __FILE__ ) . '" alt="loginpress">
				<svg class="circular-loader" viewBox="25 25 50 50" >
					<circle class="loader-path" cx="50" cy="50" r="18" fill="none" stroke="#d8d8d8" stroke-width="1" />
				</svg>
				</div>
				<p>' .
					// translators: Activating the plugin.
				sprintf( esc_html__( 'Activating %s...', 'loginpress' ), esc_html( $text ) ) . '</p>
				</div>';
			$html .= '<div id="loginpressActivatedAddon' . esc_attr( $slug ) . '" class="loginpress-install activated" style="display:none">
				<svg class="circular-loader2" viewBox="25 25 50 50" >
					<circle class="loader-path2" cx="50" cy="50" r="18" fill="none" stroke="#00c853" stroke-width="1" />
				</svg>
				<div class="checkmark draw"></div>
				<p>' .
					// translators: Plugin activated.
				sprintf( esc_html__( '%s Activated.', 'loginpress' ), esc_html( $text ) ) . '</p>
				</div>';
			$html .= '<div id="loginpressUninstallingAddon' . esc_attr( $slug ) . '" class="loginpress-uninstalling activated" style="display:none">
				<div class="loginpress-logo-container">
					<img src="' . plugins_url( '../../loginpress/img/loginpress-logo-divid-logo.svg', __FILE__ ) . '" alt="loginpress">
					<svg class="circular-loader" viewBox="25 25 50 50" >
					<circle class="loader-path" cx="50" cy="50" r="18" fill="none" stroke="#d8d8d8" stroke-width="1" />
					</svg>
				</div>
				<p>' .
					// translators: Deactivating the plugin.
					sprintf( esc_html__( 'Deactivating %s...', 'loginpress' ), esc_html( $text ) ) . '</p>
				</div>';
			$html .= '<div id="loginpressDeactivatedAddon' . esc_attr( $slug ) . '" class="loginpress-uninstall activated" style="display:none">
				<svg class="circular-loader2" viewBox="25 25 50 50" >
					<circle class="loader-path2" cx="50" cy="50" r="18" fill="none" stroke="#ff0000" stroke-width="1" />
				</svg>
				<div class="checkmark draw"></div>
				<p>' .
					// translators: Plugin deactivated.
					sprintf( esc_html__( '%s Deactivated.', 'loginpress' ), esc_html( $text ) ) . '</p>
				</div>';
			$html .= '<div id="loginpressWrongAddon' . esc_attr( $slug ) . '" class="loginpress-wrong activated" style="display:none">
				<svg class="checkmark_login" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
					<circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"></circle>
					<path class="checkmark__check" stroke="#ff0000" fill="none" d="M16 16 36 36 M36 16 16 36"></path>
				</svg>
				<p>' . $message . '</p>
				</div>';

			return $html;
		}

		/**
		 * Render free addons cards.
		 *
		 * @param array<string, mixed> $addon Free addon data array containing slug, title, and media.
		 * @since 1.0.19
		 * @version 3.0.8
		 * @return void HTML of free addon card with install/activate buttons.
		 */
		public function addon_card_free( $addon ) {
			$addon_slug  = $addon['slug'];
			$addon_thumb = ( defined( 'LOGINPRESS_DIR_URL' ) ? LOGINPRESS_DIR_URL : '' ) . 'img/addons/' . $addon_slug . '.png';
			$utm_content = str_replace( ' ', '+', $this->addons_meta[ $addon_slug ]['title'] ?? '' );
			?>

			<div class="loginpress-extension <?php echo esc_attr( true === $addon['is_free'] ? 'loginpress-free-add-ons' : '' ); ?> ">
				<a target="_blank" href="https://loginpress.pro/lite/?utm_source=loginpress-lite&utm_medium=addons&utm_campaign=pro-upgrade&utm_content=<?php echo esc_html( $utm_content ); ?>" class="logoinpress_addons_links">
					<h3>
						<img src=<?php echo esc_url( $addon_thumb ); ?> class="logoinpress_addons_thumbnails"/>
						<span><?php echo esc_html( $this->addons_meta[ $addon_slug ]['title'] ?? '' ); ?></span>
					</h3>
				</a>
				<?php
				echo '<p>' . esc_html( $this->addons_meta[ $addon_slug ]['excerpt'] ?? '' ) . '</p>';
				$this->check_free_addon_status( $addon );
				echo $this->ajax_response( $this->addons_meta[ $addon_slug ]['title'] ?? '', $addon['slug'] ); //phpcs:ignore
				?>
			</div>
			<?php
		}

		/**
		 * Check the license life.
		 *
		 * @param array<string> $categories Array of addon categories to check against license.
		 * @since 1.0.19
		 * @version 3.0.9
		 * @return boolean True if addon is licensed, false otherwise.
		 */
		public function is_addon_licensed( $categories ) {

			if ( ! class_exists( 'LoginPress_Pro' ) ) {
				return false;
			}

			if ( LoginPress_Pro::get_license_id() === '2' && in_array( 'loginpress-pro-agency', $categories, true ) ) {
				return true;
			} elseif ( LoginPress_Pro::get_license_id() === '3' && in_array( 'loginpress-pro-agency', $categories, true ) ) {
				return true;
			} elseif ( LoginPress_Pro::get_license_id() === '4' && in_array( 'loginpress-pro-agency', $categories, true ) ) {
				return true;
			} elseif ( LoginPress_Pro::get_license_id() === '5' ) {
				return true;
			} elseif ( LoginPress_Pro::get_license_id() === '6' ) {
				return true;
			} elseif ( LoginPress_Pro::get_license_id() === '7' && in_array( 'loginpress-pro-agency', $categories, true ) ) {
				return true;
			} elseif ( LoginPress_Pro::get_license_id() === '8' && in_array( 'loginpress-pro-agency', $categories, true ) ) {
				return true;
			} elseif ( LoginPress_Pro::get_license_id() === '9' && in_array( 'loginpress-pro-agency', $categories, true ) ) {
				return true;
			} elseif ( LoginPress_Pro::get_license_id() === '1' && in_array( 'loginpress-free-add-ons', $categories, true ) ) {
				return true;
			} elseif ( LoginPress_Pro::get_license_id() === '10' && in_array( 'loginpress-pro-agency', $categories, true ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Get the Add-Ons data.
		 *
		 * @since 1.0.19
		 * @return mixed
		 */
		public function get_addons() {
			// Get the transient where the addons are stored on-site.
			$data = get_transient( 'loginpress_api_addons' );
			// If we already have data, return it.
			if ( ! empty( $data ) ) {
				return $data;
			} else {
				$json_data = file_get_contents( plugin_dir_path( __FILE__ ) . '../js/loginpress_addons.json' );

				// Decode the JSON into an associative array.
				if ( false !== $json_data ) {
					$data = json_decode( $json_data );
					if ( ! empty( $data ) && is_array( $data ) ) {
						set_transient( 'loginpress_api_addons', $data, 7 * DAY_IN_SECONDS );
						return $data;
					}
				}
				return array( 'error_message' => esc_html__( 'Something went wrong in loading the Add-Ons, Try again later!', 'loginpress' ) );
			}
		}

		/**
		 * Convert the slug into an array.
		 *
		 * @param mixed $categories Categories data to convert.
		 * @since 3.0.5
		 * @return array<string> Array of category slugs.
		 */
		public function convert_to_array( $categories ) {

			$category_slugs = array();
			if ( is_array( $categories ) ) {
				foreach ( $categories as $category ) {
					if ( is_object( $category ) && isset( $category->slug ) ) {
						$category_slugs[] = $category->slug;
					}
				}
			}
			return $category_slugs;
		}

		/**
		 * Check the life of the license, Is it legal or not.
		 *
		 * @param string $addon_slug Slug of the addon to check.
		 * @since 3.0.5
		 * @return boolean True if license is valid, false otherwise.
		 */
		public function license_life( $addon_slug ) {

			$response = $this->get_addons();
			if ( is_array( $response ) && ! isset( $response['error_message'] ) ) {
				foreach ( $response as $key => $value ) {

					if ( 'loginpress-' . $addon_slug === $value->slug ) {
						return $this->is_addon_licensed( $this->convert_to_array( $value->categories ) );
					}
				}
			}
			return false;
		}

		/**
		 * Check addon status.
		 *
		 * @param array<string, mixed> $addon_data Addon data array containing slug and status.
		 * @since 1.0.19
		 * @version 3.0.5
		 *
		 * @return void HTML of addon status indicator and action buttons.
		 */
		public function check_addon_status( $addon_data ) {
			$addon_slug = $addon_data['slug'];

			if ( $addon_data['is_free'] ) {
				$this->check_free_addon_status( $addon_data );
			} else {
				if ( true === $addon_data['is_active'] ) {
					?>

					<input name="loginpress_pro_addon_nonce" type="hidden" value="<?php echo esc_attr( wp_create_nonce( 'uninstall_' . $addon_slug ) ); ?>">
					<input name="loginpress_pro_addon_slug" type="hidden" value="<?php echo esc_attr( $addon_slug ); ?>">
					<input id="<?php echo esc_attr( $addon_slug ); ?>" type="checkbox" checked class="loginpress-radio loginpress-radio-ios loginpress-uninstall-pro-addon" value="<?php echo esc_attr( $addon_slug ); ?>">
					<label for="<?php echo esc_attr( $addon_slug ); ?>" class="loginpress-radio-btn"></label>

				<?php } else { ?>

					<input name="loginpress_pro_addon_nonce" type="hidden" value="<?php echo esc_attr( wp_create_nonce( 'install-plugin_' . $addon_slug ) ); ?>">
					<input name="loginpress_pro_addon_slug" type="hidden" value="<?php echo esc_attr( $addon_slug ); ?>">
					<input name="loginpress_pro_addon_id" type="hidden" value="<?php echo esc_attr( $addon_slug ); ?>">
					<input id="<?php echo esc_attr( $addon_slug ); ?>" type="checkbox" class="loginpress-radio loginpress-radio-ios loginpress-active-pro-addon" value="<?php echo esc_attr( $addon_slug ); ?>">
					<label for="<?php echo esc_attr( $addon_slug ); ?>" class="loginpress-radio-btn"></label>

					<?php
				}
				?>
				<?php
			}
		}

		/**
		 * Check installation status for free addons.
		 *
		 * @param array<string, mixed> $addon_data Addon data array containing slug and status.
		 *
		 * @since 3.0.8
		 * @return void
		 */
		public function check_free_addon_status( $addon_data ) {
			if ( true === $addon_data['is_free'] ) {
				$plugin_file_path = $addon_data['slug'] . '/' . $addon_data['slug'] . '.php';

				if ( is_plugin_active( $plugin_file_path ) ) {
					?>

					<input name="loginpress_pro_addon_nonce" type="hidden" value="<?php echo esc_attr( wp_create_nonce( 'uninstall_' . $addon_data['slug'] ) ); ?>">
					<input name="loginpress_pro_addon_slug" type="hidden" value="<?php echo esc_attr( $addon_data['slug'] ); ?>">
					<input id="<?php echo esc_attr( $addon_data['slug'] ); ?>" type="checkbox" checked class="loginpress-radio loginpress-radio-ios loginpress-uninstall-pro-addon" value="<?php echo esc_attr( $addon_data['slug'] ); ?>">
					<label for="<?php echo esc_attr( $addon_data['slug'] ); ?>" class="loginpress-radio-btn"></label>
					
				<?php } elseif ( array_key_exists( $plugin_file_path, $this->plugins_list ) ) { ?>

					<input name="loginpress_pro_addon_nonce" type="hidden" value="<?php echo esc_attr( wp_create_nonce( 'install-plugin_' . $addon_data['slug'] ) ); ?>">
					<input name="loginpress_pro_addon_slug" type="hidden" value="<?php echo esc_attr( $addon_data['slug'] ); ?>">
					<input id="<?php echo esc_attr( $addon_data['slug'] ); ?>" type="checkbox" class="loginpress-radio loginpress-radio-ios loginpress-active-pro-addon" value="<?php echo esc_attr( $addon_data['slug'] ); ?>">
					<label for="<?php echo esc_attr( $addon_data['slug'] ); ?>" class="loginpress-radio-btn"></label>

				<?php } else { ?>

					<input name="loginpress_pro_addon_nonce" type="hidden" value="<?php echo esc_attr( wp_create_nonce( 'install-plugin_' . $addon_data['slug'] ) ); ?>">
					<input name="loginpress_pro_addon_slug" type="hidden" value="<?php echo esc_attr( $addon_data['slug'] ); ?>">
					<input id="<?php echo esc_attr( $addon_data['slug'] ); ?>" type="checkbox" class="loginpress-radio loginpress-radio-ios loginpress-install-pro-addon" value="<?php echo esc_attr( $addon_data['slug'] ); ?>">
					<label for="<?php echo esc_attr( $addon_data['slug'] ); ?>" class="loginpress-radio-btn"></label>

					<?php
				}
			} else {
				$utm_content = str_replace( ' ', '+', $addon_data['title'] ?? '' );
				?>
				<p><a target="_blank" href="https://loginpress.pro/lite/?utm_source=loginpress-lite&utm_medium=addons&utm_campaign=pro-upgrade&utm_content=<?php echo esc_html( $utm_content ); ?>" class="button-primary"><?php esc_html_e( 'UPGRADE NOW', 'loginpress' ); ?></a></p>
				<?php
			}
		}
	}

endif;
