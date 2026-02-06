<?php
/**
 * LoginPress Page Create Class
 *
 * Create LoginPress Page.
 *
 * @package   LoginPress
 * @author    WPBrigade
 * @since     1.1.3
 * @version   1.1.25
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create a LoginPress page for Multisite.
 */

if ( ! class_exists( 'LoginPress_Page_Create' ) ) :

	/**
	 * LoginPress Page Create Class.
	 *
	 * Handles creation of LoginPress pages for multisite.
	 *
	 * @package   LoginPress
	 * @since     1.1.3
	 * @version   1.1.25
	 */
	class LoginPress_Page_Create {

		/**
		 * Class constructor.
		 *
		 * @since 1.1.3
		 * @return void
		 */
		public function __construct() {
			$this->init();
			$this->hooks();
		}

		/**
		 * Add hooks.
		 *
		 * @since 1.1.3
		 * @return void
		 */
		public function hooks() {
			add_action( 'wpmu_new_blog', array( $this, 'loginpress_new_site_created' ), 10, 6 );
		}

		/**
		 * Initialize the page creation process.
		 *
		 * @since 1.1.3
		 * @return void
		 */
		public function init() {
			global $wpdb;

			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			if ( is_multisite() ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query needed for multisite blog enumeration.
				foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs LIMIT 100" ) as $blog_id ) {
					switch_to_blog( $blog_id );
					$this->loginpress_run_install();
					restore_current_blog();
				}
			} else {
				$this->loginpress_run_install();
			}
		}

		/**
		 * Run the LoginPress install process.
		 *
		 * @return void
		 */
		public function loginpress_run_install() {

			/* translators: 1: Name of this plugin. */
			$post_content = sprintf( __( '<p>This page is used by %1$s to preview the login page in the Customizer.</p>', 'loginpress' ), 'LoginPress' ); // @codingStandardsIgnoreLine.

			$pages = apply_filters(
				'loginpress_create_pages',
				array(
					'loginpress' => array(
						'name'    => _x( 'loginpress', 'Page slug', 'loginpress' ),
						'title'   => _x( 'LoginPress', 'Page title', 'loginpress' ),
						'content' => $post_content,
					),
				)
			);

			foreach ( $pages as $key => $page ) {
				$this->loginpress_create_page( esc_sql( $page['name'] ), 'loginpress_page', $page['title'], $page['content'] );
			}
		}

		/**
		 * Create a page and store the ID in an option.
		 *
		 * @param mixed  $slug Slug for the new page.
		 * @param string $option Option name to store the page's ID.
		 * @param string $page_title (default: '') Title for the new page.
		 * @param string $page_content (default: '') Content for the new page.
		 * @return int   page ID
		 */
		public function loginpress_create_page( $slug, $option = '', $page_title = '', $page_content = '' ) {
			global $wpdb;

			// Set up options.
			$options = array();

			// Pull options from WP.
			$loginpress_setting = get_option( 'loginpress_setting', array() );
			if ( ! is_array( $loginpress_setting ) && empty( $loginpress_setting ) ) {
				$loginpress_setting = array();
			}
			$option_value = array_key_exists( 'loginpress_page', $loginpress_setting ) ? $loginpress_setting['loginpress_page'] : false;

			$page_object = null;
			if ( $option_value > 0 ) {
				$page_object = get_post( $option_value );
			}
			if ( $page_object ) {
				if ( 'page' === $page_object->post_type && ! in_array( $page_object->post_status, array( 'pending', 'trash', 'future', 'auto-draft' ), true ) ) {
					// Valid page is already in place.
					return $page_object->ID;
				}
			}

			// Search for an existing page with the specified page slug.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query needed for page existence check.
			$loginpress_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' )  AND post_name = %s LIMIT 1;", $slug ) );

			$loginpress_page_found = apply_filters( 'loginpress_create_page_id', $loginpress_page_found, $slug, $page_content );

			if ( $loginpress_page_found ) {

				if ( $option ) {

					$options['loginpress_page'] = $loginpress_page_found;
					$loginpress_page_found      = $loginpress_page_found;
					$merged_options             = array_merge( $loginpress_setting, $options );
					$loginpress_setting         = $merged_options;

					update_option( 'loginpress_setting', $loginpress_setting );
				}
				return $loginpress_page_found;
			}

			// Search for an existing page with the specified page slug.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query needed for trashed page check.
			$loginpress_trashed_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_name = %s LIMIT 1;", $slug ) );

			if ( $loginpress_trashed_found ) {
				$page_id   = $loginpress_trashed_found;
				$page_data = array(
					'ID'          => $page_id,
					'post_status' => 'publish',
				);

				wp_update_post( $page_data );

			} else {

				$page_data = array(
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'post_author'    => 1,
					'post_name'      => $slug,
					'post_title'     => $page_title,
					'post_content'   => $page_content,
					'comment_status' => 'closed',
				);

				$page_id = wp_insert_post( $page_data );
			}

			if ( $option ) {

				$options['loginpress_page'] = $page_id;
				$page_id                    = $page_id;
				$merged_options             = array_merge( $loginpress_setting, $options );
				$loginpress_setting         = $merged_options;

				update_option( 'loginpress_setting', $loginpress_setting );
			}

			// Assign the LoginPress template.
			$this->loginpress_attach_template_to_page( $page_id, 'template-loginpress.php' );

			return $page_id;
		}

		/**
		 * Attaches the specified template to the page identified by the specified name.
		 *
		 * @param int    $page The id of the page to attach the template.
		 * @param string $template The template's filename (assumes .php' is specified).
		 *
		 * @return int -1 if the page does not exist; otherwise, the ID of the page.
		 */
		public function loginpress_attach_template_to_page( $page, $template ) {

			// Only attach the template if the page exists.
			if ( -1 !== $page ) {
				update_post_meta( $page, '_wp_page_template', $template );
			}

			return $page;
		}

		/**
		 * When a new Blog is created in multisite, check if LoginPress is network activated, and run the installer.
		 *
		 * @param int $blog_id The Blog ID created.
		 * @return void
		 */
		public function loginpress_new_site_created( $blog_id ) {
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Parameters required by WordPress multisite hook.

			if ( is_plugin_active_for_network( plugin_basename( LOGINPRESS_ROOT_FILE ) ) ) {

				switch_to_blog( $blog_id );
				$this->init();
				restore_current_blog();

			}
		}
	}

endif;
