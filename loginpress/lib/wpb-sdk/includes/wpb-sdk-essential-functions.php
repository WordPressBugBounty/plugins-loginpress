<?php
/**
 * Shared helpers for all WPBrigade SDK products.
 *
 * @package wpbrigade_sdk
 */

if ( ! function_exists( 'wpb_sdk_runtime_is_complete' ) ) {
	/**
	 * Whether the full centralized SDK runtime is loaded (not a legacy partial bundle).
	 *
	 * @return bool
	 */
	function wpb_sdk_runtime_is_complete() {
		return class_exists( 'WPBRIGADE_Opt_Manager', false )
			&& class_exists( 'WPBRIGADE_Logger', false );
	}
}

if ( ! function_exists( 'wpb_sdk_register_opt_manager_for_module' ) ) {
	/**
	 * Register opt-in/out row links (required when an old Logger runtime is already loaded).
	 *
	 * @param array<string, mixed> $module Module config.
	 * @return void
	 */
	function wpb_sdk_register_opt_manager_for_module( $module ) {
		if ( ! class_exists( 'WPBRIGADE_Opt_Manager', false ) || ! is_array( $module ) ) {
			return;
		}

		static $initiated = array();

		if ( function_exists( 'wpb_sdk_apply_module_defaults' ) ) {
			$module = wpb_sdk_apply_module_defaults( $module );
		}

		$slug = ! empty( $module['slug'] ) ? (string) $module['slug'] : '';
		if ( '' !== $slug && ! empty( $initiated[ $slug ] ) ) {
			return;
		}

		// Legacy plugins (wpb_dynamic_init, no provider sdk_version) own their own Opt In/Out links.
		if ( empty( $module['sdk_version'] ) || version_compare( (string) $module['sdk_version'], '3.2.0', '<' ) ) {
			return;
		}

		if ( '' !== $slug ) {
			$initiated[ $slug ] = true;
		}

		if ( ! empty( $module['sdk_views_dir'] ) ) {
			WPBRIGADE_Opt_Manager::register_module( $module );
		}
	}
}

if ( ! function_exists( 'wpb_sdk_get_provider_for_slug' ) ) {
	/**
	 * Bundled SDK provider registered by this product's copy of start.php.
	 *
	 * @param string $slug Product slug (provider_key).
	 * @return array<string, mixed>
	 */
	function wpb_sdk_get_provider_for_slug( $slug ) {
		$slug = (string) $slug;
		if (
			isset( $GLOBALS['wpb_sdk_registry']['providers'][ $slug ] )
			&& is_array( $GLOBALS['wpb_sdk_registry']['providers'][ $slug ] )
		) {
			return $GLOBALS['wpb_sdk_registry']['providers'][ $slug ];
		}

		return array();
	}
}

if ( ! function_exists( 'wpb_sdk_detect_bundled_sdk_dir' ) ) {
	/**
	 * Directory of the wpb-sdk folder that registered for this slug.
	 *
	 * @param string $slug Product slug.
	 * @return string Absolute path to lib/wpb-sdk (no trailing slash).
	 */
	function wpb_sdk_detect_bundled_sdk_dir( $slug = '' ) {
		$provider = wpb_sdk_get_provider_for_slug( $slug );
		if ( ! empty( $provider['sdk_dir'] ) ) {
			$dir = function_exists( 'wp_normalize_path' )
				? wp_normalize_path( (string) $provider['sdk_dir'] )
				: (string) $provider['sdk_dir'];
			if ( is_dir( $dir ) ) {
				return $dir;
			}
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Resolve caller bundle only.
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 12 );
		foreach ( $trace as $frame ) {
			if ( empty( $frame['file'] ) ) {
				continue;
			}
			$file = function_exists( 'wp_normalize_path' )
				? wp_normalize_path( $frame['file'] )
				: str_replace( '\\', '/', $frame['file'] );
			if ( preg_match( '#/wpb-sdk/(?:start|require)\\.php$#', $file ) ) {
				return dirname( $file );
			}
		}

		if ( defined( 'WPBRIGADE_SDK_DIR' ) ) {
			return function_exists( 'wp_normalize_path' )
				? wp_normalize_path( WPBRIGADE_SDK_DIR )
				: WPBRIGADE_SDK_DIR;
		}

		$slug = (string) $slug;
		if ( '' !== $slug && defined( 'WP_PLUGIN_DIR' ) ) {
			$candidate = WP_PLUGIN_DIR . '/' . $slug . '/lib/wpb-sdk';
			if ( is_dir( $candidate ) ) {
				return function_exists( 'wp_normalize_path' )
					? wp_normalize_path( $candidate )
					: $candidate;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'wpb_sdk_detect_host_plugin_root' ) ) {
	/**
	 * Host plugin root directory (parent of lib/wpb-sdk).
	 *
	 * @param string $slug Product slug.
	 * @return string
	 */
	function wpb_sdk_detect_host_plugin_root( $slug = '' ) {
		$sdk_dir = wpb_sdk_detect_bundled_sdk_dir( $slug );
		if ( '' === $sdk_dir ) {
			return '';
		}

		$sdk_dir = function_exists( 'wp_normalize_path' )
			? wp_normalize_path( $sdk_dir )
			: $sdk_dir;

		if ( preg_match( '#/lib/wpb-sdk$#', $sdk_dir ) ) {
			return dirname( dirname( $sdk_dir ) );
		}

		return '';
	}
}

if ( ! function_exists( 'wpb_sdk_detect_calling_plugin_file' ) ) {
	/**
	 * Main plugin PHP file that invoked wpb_sdk_dynamic_init() (Freemius-style).
	 *
	 * @return string Absolute path or empty.
	 */
	function wpb_sdk_detect_calling_plugin_file() {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Caller plugin file detection.
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 15 );
		foreach ( $trace as $frame ) {
			if ( empty( $frame['file'] ) ) {
				continue;
			}
			$file = function_exists( 'wp_normalize_path' )
				? wp_normalize_path( $frame['file'] )
				: str_replace( '\\', '/', $frame['file'] );
			if ( false !== strpos( $file, '/lib/wpb-sdk/' ) ) {
				continue;
			}
			if ( false !== strpos( $file, '/wp-content/plugins/' ) && is_readable( $file ) ) {
				return $file;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'wpb_sdk_detect_plugin_main_file' ) ) {
	/**
	 * Resolve host plugin main file from slug and bundled SDK location.
	 *
	 * @param string $slug Product slug.
	 * @return string Absolute path or empty.
	 */
	function wpb_sdk_detect_plugin_main_file( $slug ) {
		$slug = (string) $slug;
		if ( '' === $slug ) {
			return '';
		}

		$caller = wpb_sdk_detect_calling_plugin_file();
		if ( '' !== $caller && is_readable( $caller ) ) {
			return $caller;
		}

		$root = wpb_sdk_detect_host_plugin_root( $slug );
		if ( '' !== $root ) {
			$candidates = array(
				$root . '/' . basename( $root ) . '.php',
				$root . '/' . $slug . '.php',
			);
			foreach ( $candidates as $path ) {
				if ( is_readable( $path ) ) {
					return function_exists( 'wp_normalize_path' )
						? wp_normalize_path( $path )
						: $path;
				}
			}
		}

		if ( defined( 'WP_PLUGIN_DIR' ) ) {
			$fallback = WP_PLUGIN_DIR . '/' . $slug . '/' . $slug . '.php';
			if ( is_readable( $fallback ) ) {
				return function_exists( 'wp_normalize_path' )
					? wp_normalize_path( $fallback )
					: $fallback;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'wpb_sdk_default_ajax_prefix' ) ) {
	/**
	 * Default AJAX action prefix from slug (wp-analytify → analytify).
	 *
	 * @param string $slug Product slug.
	 * @return string
	 */
	function wpb_sdk_default_ajax_prefix( $slug ) {
		$slug   = (string) $slug;
		$prefix = preg_replace( '#^wp-#', '', $slug );

		return str_replace( '-', '_', $prefix );
	}
}

if ( ! function_exists( 'wpb_sdk_find_optout_view' ) ) {
	/**
	 * Locate opt-out view in this product's bundled SDK views directory.
	 *
	 * @param string $views_dir Absolute path to views folder.
	 * @param string $slug      Product slug.
	 * @return string Filename or empty.
	 */
	function wpb_sdk_find_optout_view( $views_dir, $slug ) {
		$views_dir = trailingslashit( $views_dir );
		if ( ! is_dir( $views_dir ) ) {
			return '';
		}

		$prefix = wpb_sdk_default_ajax_prefix( $slug );
		$try    = array(
			'wpb-sdk-optout-form.php',
			$prefix . '-optout-form.php',
			$slug . '-optout-form.php',
		);
		$try    = array_unique( $try );
		foreach ( $try as $name ) {
			if ( is_readable( $views_dir . $name ) ) {
				return $name;
			}
		}

		$glob = glob( $views_dir . '*-optout-form.php' );
		if ( ! empty( $glob[0] ) && is_readable( $glob[0] ) ) {
			return basename( $glob[0] );
		}

		return '';
	}
}

if ( ! function_exists( 'wpb_sdk_product_manages_own_opt_action_links' ) ) {
	/**
	 * Products that add Opt In/Out on plugins.php via their own plugin_action_links handler.
	 *
	 * @param string $slug Product slug.
	 * @return bool
	 */
	function wpb_sdk_product_manages_own_opt_action_links( $slug ) {
		$slug = (string) $slug;

		if (
			isset( $GLOBALS['wpb_sdk_registry']['modules'][ $slug ] )
			&& is_array( $GLOBALS['wpb_sdk_registry']['modules'][ $slug ] )
		) {
			$module = $GLOBALS['wpb_sdk_registry']['modules'][ $slug ];
			if ( empty( $module['sdk_version'] ) || version_compare( (string) $module['sdk_version'], '3.2.0', '<' ) ) {
				return true;
			}

			return false;
		}

		$legacy = array();

		/**
		 * Filter slugs that manage their own Opt In/Out plugin row links (not the SDK).
		 *
		 * @param string[] $legacy Product slugs.
		 * @param string   $slug   Current product slug.
		 */
		$legacy = apply_filters( 'wpb_sdk_legacy_opt_action_link_slugs', $legacy, $slug );

		return in_array( $slug, $legacy, true );
	}
}

if ( ! function_exists( 'wpb_sdk_uses_custom_optin_form' ) ) {
	/**
	 * Products with a bespoke opt-in screen (not the shared SDK default).
	 *
	 * @param string $slug Product slug.
	 * @return bool
	 */
	function wpb_sdk_uses_custom_optin_form( $slug ) {
		$custom = array( 'loginpress', 'wp-analytify' );

		/**
		 * Filter products that use a custom opt-in form instead of wpb-sdk-optin-form.php.
		 *
		 * @param string[] $custom Product slugs.
		 * @param string   $slug   Current product slug.
		 */
		$custom = apply_filters( 'wpb_sdk_custom_optin_slugs', $custom, $slug );

		return in_array( $slug, $custom, true );
	}
}

if ( ! function_exists( 'wpb_sdk_resolve_optin_logo_url' ) ) {
	/**
	 * Logo URL for the shared opt-in splash.
	 *
	 * @param array<string, mixed> $module Module config.
	 * @return string Empty when no logo is configured or found.
	 */
	function wpb_sdk_resolve_optin_logo_url( array $module ) {
		$optin = isset( $module['optin'] ) && is_array( $module['optin'] ) ? $module['optin'] : array();
		if ( ! empty( $optin['logo_url'] ) ) {
			return (string) $optin['logo_url'];
		}

		$plugin_file = ! empty( $module['plugin_file'] ) ? (string) $module['plugin_file'] : '';
		if ( '' === $plugin_file || ! is_readable( $plugin_file ) ) {
			return '';
		}

		$candidates = array();
		if ( ! empty( $optin['logo_path'] ) ) {
			$candidates[] = (string) $optin['logo_path'];
		}

		$prefix     = wpb_sdk_default_ajax_prefix( (string) $module['slug'] );
		$slug       = (string) $module['slug'];
		$candidates = array_merge(
			$candidates,
			array(
				'assets/images/' . $prefix . '_icon.png',
				'assets/images/' . $prefix . '-icon.png',
				'assets/images/' . $prefix . '_logo.png',
				'assets/images/' . $prefix . '-logo.svg',
				'assets/images/' . $prefix . '-logo.png',
				'assets/images/' . $prefix . '-brand.svg',
				'assets/img/' . $prefix . '-logo.svg',
				'assets/img/' . $prefix . '_logo.png',
				'img/' . $prefix . '.png',
				'img/' . $prefix . '_icon.png',
				'img/icon.png',
				'img/logo.png',
				'img/review-icon.png',
				'asset/img/logo.svg',
				'asset/img/logo.png',
				'assets/images/logo.svg',
				'assets/images/logo.png',
				'assets/images/icon.png',
			)
		);
		if ( str_starts_with( $slug, 'wp-' ) ) {
			$short_slug   = substr( $slug, 3 );
			$candidates[] = 'assets/images/' . $short_slug . '-logo.svg';
			$candidates[] = 'assets/images/' . $short_slug . '_icon.png';
		}

		foreach ( $candidates as $relative ) {
			$path = plugin_dir_path( $plugin_file ) . ltrim( $relative, '/\\' );
			if ( is_readable( $path ) ) {
				return plugins_url( $relative, $plugin_file );
			}
		}

		/**
		 * Last-resort logo URL when no bundled asset exists (e.g. plugins without assets/images/).
		 *
		 * @param string               $url    Empty by default.
		 * @param array<string, mixed> $module Module config.
		 */
		$filtered = apply_filters( 'wpb_sdk_optin_logo_url', '', $module );
		if ( '' !== $filtered ) {
			return (string) $filtered;
		}

		$use_wporg = ! isset( $optin['wporg_logo_fallback'] ) || false !== $optin['wporg_logo_fallback'];
		if ( ! $use_wporg ) {
			return '';
		}

		$wporg_slug = ! empty( $optin['wporg_slug'] ) ? (string) $optin['wporg_slug'] : $slug;
		if ( '' === $wporg_slug ) {
			return '';
		}

		return 'https://ps.w.org/' . rawurlencode( $wporg_slug ) . '/assets/icon-256x256.png';
	}
}

if ( ! function_exists( 'wpb_sdk_resolve_optin_hero_url' ) ) {
	/**
	 * Hero/illustration URL for the shared opt-in splash (falls back to logo).
	 *
	 * @param array<string, mixed> $module Module config.
	 * @return string
	 */
	function wpb_sdk_resolve_optin_hero_url( array $module ) {
		$optin = isset( $module['optin'] ) && is_array( $module['optin'] ) ? $module['optin'] : array();
		if ( ! empty( $optin['hero_image_url'] ) ) {
			return (string) $optin['hero_image_url'];
		}

		$plugin_file = ! empty( $module['plugin_file'] ) ? (string) $module['plugin_file'] : '';
		if ( '' !== $plugin_file && is_readable( $plugin_file ) ) {
			if ( ! empty( $optin['hero_image_path'] ) ) {
				$relative = (string) $optin['hero_image_path'];
				$path     = plugin_dir_path( $plugin_file ) . ltrim( $relative, '/\\' );
				if ( is_readable( $path ) ) {
					return plugins_url( $relative, $plugin_file );
				}
			}

			$prefix     = wpb_sdk_default_ajax_prefix( (string) $module['slug'] );
			$candidates = array(
				'assets/images/welcome-' . $prefix . '.png',
				'assets/images/' . $prefix . '-welcome.png',
				'assets/images/social_button.svg',
			);
			foreach ( $candidates as $relative ) {
				$path = plugin_dir_path( $plugin_file ) . ltrim( $relative, '/\\' );
				if ( is_readable( $path ) ) {
					return plugins_url( $relative, $plugin_file );
				}
			}
		}

		return wpb_sdk_resolve_optin_logo_url( $module );
	}
}

if ( ! function_exists( 'wpb_sdk_get_optin_view_path' ) ) {
	/**
	 * Absolute path to the opt-in view for a product.
	 *
	 * @param string $slug Product slug.
	 * @return string
	 */
	function wpb_sdk_get_optin_view_path( $slug ) {
		$module = wpb_sdk_get_registered_module( $slug );
		if ( empty( $module['sdk_views_dir'] ) ) {
			return '';
		}

		$views_dir = trailingslashit( (string) $module['sdk_views_dir'] );
		$optin     = isset( $module['optin'] ) && is_array( $module['optin'] ) ? $module['optin'] : array();

		if ( ! empty( $optin['optin_view'] ) ) {
			$path = $views_dir . ltrim( (string) $optin['optin_view'], '/\\' );
			return is_readable( $path ) ? $path : '';
		}

		if ( wpb_sdk_uses_custom_optin_form( $slug ) ) {
			return '';
		}

		$default = $views_dir . 'wpb-sdk-optin-form.php';

		return is_readable( $default ) ? $default : '';
	}
}

if ( ! function_exists( 'wpb_sdk_render_optin_form' ) ) {
	/**
	 * Render the shared (or configured) opt-in admin page for a product.
	 *
	 * @param string $slug Product slug.
	 * @return void
	 */
	function wpb_sdk_render_optin_form( $slug ) {
		$module = wpb_sdk_get_registered_module( $slug );
		if ( empty( $module ) ) {
			return;
		}

		$path = wpb_sdk_get_optin_view_path( $slug );
		if ( '' === $path ) {
			return;
		}

		$optin  = isset( $module['optin'] ) && is_array( $module['optin'] ) ? $module['optin'] : array();
		$prefix = ! empty( $optin['ajax_prefix'] )
			? sanitize_key( (string) $optin['ajax_prefix'] )
			: wpb_sdk_default_ajax_prefix( $slug );

		$wpb_sdk_product_name = ! empty( $optin['product_name'] )
			? (string) $optin['product_name']
			: $slug;
		$wpb_sdk_ajax_prefix  = $prefix;
		$wpb_sdk_optin_option = ! empty( $optin['option_name'] ) ? (string) $optin['option_name'] : '';
		$wpb_sdk_optin_nonce  = wp_create_nonce( $prefix . '_optin_page_nonce' );
		$wpb_sdk_logo_url     = wpb_sdk_resolve_optin_logo_url( $module );
		$wpb_sdk_optin_page   = ! empty( $optin['optin_page'] ) ? (string) $optin['optin_page'] : '';

		$redirect_override = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['redirect-page'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$redirect_override = sanitize_key( wp_unslash( (string) $_GET['redirect-page'] ) );
		}
		$wpb_sdk_redirect_url = wpb_sdk_resolve_optin_redirect_url( $slug, $redirect_override );

		include $path;
	}
}

if ( ! function_exists( 'wpb_sdk_apply_module_defaults' ) ) {
	/**
	 * Fill module config from bundled SDK path and slug (minimal init like Freemius).
	 *
	 * Required keys only: id, slug, public_key, (+ product-specific optin/settings).
	 *
	 * @param array<string, mixed> $module Module config from the host plugin.
	 * @return array<string, mixed>
	 */
	function wpb_sdk_apply_module_defaults( $module ) {
		if ( ! is_array( $module ) || empty( $module['slug'] ) ) {
			return $module;
		}

		$slug    = (string) $module['slug'];
		$sdk_dir = wpb_sdk_detect_bundled_sdk_dir( $slug );

		if ( empty( $module['type'] ) ) {
			$module['type'] = 'plugin';
		}

		if ( empty( $module['api_endpoint'] ) ) {
			$provider = wpb_sdk_get_provider_for_slug( $slug );
			if ( ! empty( $provider['api_endpoint'] ) ) {
				$module['api_endpoint'] = (string) $provider['api_endpoint'];
			}
		}

		// Inject the plugin's own SDK version from its provider registration.
		// Used downstream to detect whether this plugin manages its own action links
		// (old SDK ≤ 3.1.x) or delegates to WPBRIGADE_Opt_Manager (new SDK ≥ 3.2.0).
		if ( empty( $module['sdk_version'] ) ) {
			$provider = wpb_sdk_get_provider_for_slug( $slug );
			if ( ! empty( $provider['sdk_version'] ) ) {
				$module['sdk_version'] = (string) $provider['sdk_version'];
			}
		}

		if ( empty( $module['plugin_file'] ) ) {
			$detected = wpb_sdk_detect_plugin_main_file( $slug );
			if ( '' !== $detected ) {
				$module['plugin_file'] = $detected;
			}
		}

		if ( empty( $module['sdk_views_dir'] ) && '' !== $sdk_dir ) {
			$module['sdk_views_dir'] = trailingslashit( $sdk_dir ) . 'views';
		}

		if ( empty( $module['text_domain'] ) && ! empty( $module['plugin_file'] ) && is_readable( $module['plugin_file'] ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$header = get_plugin_data( $module['plugin_file'], false, false );
			if ( ! empty( $header['TextDomain'] ) ) {
				$module['text_domain'] = (string) $header['TextDomain'];
			}
		}
		if ( empty( $module['text_domain'] ) ) {
			$module['text_domain'] = $slug;
		}

		$optin = isset( $module['optin'] ) && is_array( $module['optin'] ) ? $module['optin'] : array();
		if ( empty( $optin['ajax_prefix'] ) ) {
			$optin['ajax_prefix'] = wpb_sdk_default_ajax_prefix( $slug );
		}
		if ( ! empty( $module['sdk_views_dir'] ) ) {
			$shared_optout = trailingslashit( $module['sdk_views_dir'] ) . 'wpb-sdk-optout-form.php';
			if ( is_readable( $shared_optout ) ) {
				$optin['optout_view'] = 'wpb-sdk-optout-form.php';
			} elseif ( empty( $optin['optout_view'] ) ) {
				$found = wpb_sdk_find_optout_view( $module['sdk_views_dir'], $slug );
				if ( '' !== $found ) {
					$optin['optout_view'] = $found;
				}
			}
		}
		if (
			empty( $optin['optin_view'] )
			&& ! wpb_sdk_uses_custom_optin_form( $slug )
			&& ! empty( $module['sdk_views_dir'] )
			&& is_readable( trailingslashit( $module['sdk_views_dir'] ) . 'wpb-sdk-optin-form.php' )
		) {
			$optin['optin_view'] = 'wpb-sdk-optin-form.php';
		}
		if ( empty( $optin['product_name'] ) && ! empty( $module['plugin_file'] ) && is_readable( $module['plugin_file'] ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$header = get_plugin_data( $module['plugin_file'], false, false );
			if ( ! empty( $header['Name'] ) ) {
				$optin['product_name'] = (string) $header['Name'];
			}
		}
		$module['optin'] = $optin;

		/**
		 * Filter module config after SDK defaults are applied.
		 *
		 * @param array<string, mixed> $module Module config.
		 */
		return apply_filters( 'wpb_sdk_module_config', $module );
	}
}

if ( ! function_exists( 'wpb_sdk_get_registered_module' ) ) {
	/**
	 * Module config stored by wpb_sdk_dynamic_init().
	 *
	 * @param string $slug Product slug.
	 * @return array<string, mixed>
	 */
	function wpb_sdk_get_registered_module( $slug ) {
		if (
			isset( $GLOBALS['wpb_sdk_registry']['modules'][ $slug ] )
			&& is_array( $GLOBALS['wpb_sdk_registry']['modules'][ $slug ] )
		) {
			return $GLOBALS['wpb_sdk_registry']['modules'][ $slug ];
		}

		return array();
	}
}

if ( ! function_exists( 'wpb_sdk_get_optin_decision' ) ) {
	/**
	 * Stored opt-in choice for a product (empty = user has not decided yet).
	 *
	 * @param string $slug Product slug.
	 * @return string yes|no|skip|'' or legacy when product has no opt-in gate.
	 */
	function wpb_sdk_get_optin_decision( $slug ) {
		$module = wpb_sdk_get_registered_module( $slug );
		$optin  = isset( $module['optin'] ) && is_array( $module['optin'] ) ? $module['optin'] : array();
		$name   = ! empty( $optin['option_name'] ) ? (string) $optin['option_name'] : '';

		if ( '' === $name ) {
			return 'legacy';
		}

		$value = ! empty( $optin['use_site_option'] )
			? get_site_option( $name, '' )
			: get_option( $name, '' );

		return is_string( $value ) ? $value : '';
	}
}

if ( ! function_exists( 'wpb_sdk_sdk_option_is_enabled' ) ) {
	/**
	 * Whether an SDK sharing flag is on (handles "1", 1, true, etc.).
	 *
	 * @param mixed $value Raw option value.
	 * @return bool
	 */
	function wpb_sdk_sdk_option_is_enabled( $value ) {
		if ( true === $value || 1 === $value ) {
			return true;
		}

		return in_array( strtolower( (string) $value ), array( '1', 'true', 'yes', 'on' ), true );
	}
}

if ( ! function_exists( 'wpb_sdk_default_post_optin_redirect_url' ) ) {
	/**
	 * Safe fallback when a product has no registered settings admin screen.
	 *
	 * @return string
	 */
	function wpb_sdk_default_post_optin_redirect_url() {
		return admin_url( 'plugins.php' );
	}
}

if ( ! function_exists( 'wpb_sdk_admin_screen_path_is_available' ) ) {
	/**
	 * Whether a path under wp-admin exists (e.g. nav-menus.php).
	 *
	 * @param string $admin_path Path relative to wp-admin.
	 * @return bool
	 */
	function wpb_sdk_admin_screen_path_is_available( $admin_path ) {
		$admin_path = ltrim( (string) $admin_path, '/' );
		if ( '' === $admin_path ) {
			return false;
		}

		$file = strtok( $admin_path, '?' );
		if ( ! is_string( $file ) || '' === $file ) {
			return false;
		}

		return file_exists( ABSPATH . 'wp-admin/' . $file );
	}
}

if ( ! function_exists( 'wpb_sdk_admin_menu_page_slug_exists' ) ) {
	/**
	 * Whether ?page= slug is registered in the admin menu.
	 *
	 * @param string $page_slug Admin page query arg.
	 * @return bool
	 */
	function wpb_sdk_admin_menu_page_slug_exists( $page_slug ) {
		$page_slug = sanitize_key( (string) $page_slug );
		if ( '' === $page_slug ) {
			return false;
		}

		global $admin_page_hooks, $submenu;

		if ( isset( $admin_page_hooks[ $page_slug ] ) ) {
			return true;
		}

		if ( ! is_array( $submenu ) ) {
			return false;
		}

		foreach ( $submenu as $items ) {
			if ( ! is_array( $items ) ) {
				continue;
			}
			foreach ( $items as $item ) {
				if ( ! is_array( $item ) || empty( $item[2] ) ) {
					continue;
				}
				$hook = (string) $item[2];
				if ( $page_slug === $hook || false !== strpos( $hook, 'page=' . $page_slug ) ) {
					return true;
				}
			}
		}

		return false;
	}
}

if ( ! function_exists( 'wpb_sdk_admin_url_for_registered_page_slug' ) ) {
	/**
	 * Return the admin.php?page= URL only when that page is registered.
	 *
	 * @param string $page_slug Admin page query arg.
	 * @return string Empty when not registered.
	 */
	function wpb_sdk_admin_url_for_registered_page_slug( $page_slug ) {
		$page_slug = sanitize_key( (string) $page_slug );
		if ( '' === $page_slug || ! wpb_sdk_admin_menu_page_slug_exists( $page_slug ) ) {
			return '';
		}

		return admin_url( 'admin.php?page=' . $page_slug );
	}
}

if ( ! function_exists( 'wpb_sdk_resolve_optin_redirect_url' ) ) {
	/**
	 * Redirect target after opt-in Allow/Skip (or email verification).
	 *
	 * Only registered plugin admin.php?page= slugs are valid. Core screens (e.g. nav-menus.php)
	 * and unknown slugs fall back to plugins.php.
	 *
	 * Order: redirect-page override (if registered), settings_page, product slug, plugins.php.
	 *
	 * @param string $slug              Product slug.
	 * @param string $redirect_override Optional redirect-page query value.
	 * @return string
	 */
	function wpb_sdk_resolve_optin_redirect_url( $slug, $redirect_override = '' ) {
		$fallback = wpb_sdk_default_post_optin_redirect_url();
		$module   = wpb_sdk_get_registered_module( $slug );
		$optin    = ( ! empty( $module['optin'] ) && is_array( $module['optin'] ) )
			? $module['optin']
			: array();

		$redirect_override = sanitize_key( (string) $redirect_override );

		if ( '' !== $redirect_override ) {
			$url = wpb_sdk_admin_url_for_registered_page_slug( $redirect_override );
			if ( '' === $url ) {
				$url = $fallback;
			}

			return apply_filters( 'wpb_sdk_optin_redirect_url', $url, $slug, $optin, $redirect_override );
		}

		if ( ! empty( $optin['settings_page'] ) ) {
			$url = wpb_sdk_admin_url_for_registered_page_slug( (string) $optin['settings_page'] );
			if ( '' !== $url ) {
				return apply_filters( 'wpb_sdk_optin_redirect_url', $url, $slug, $optin, $redirect_override );
			}
		}

		$url = wpb_sdk_admin_url_for_registered_page_slug( $slug );
		if ( '' === $url ) {
			$url = $fallback;
		}

		return apply_filters( 'wpb_sdk_optin_redirect_url', $url, $slug, $optin, $redirect_override );
	}
}

if ( ! function_exists( 'wpb_sdk_build_uninstall_option_names_from_module' ) ) {
	/**
	 * Option names the SDK may persist for a product (for uninstall cleanup).
	 *
	 * @param string               $slug   Product slug.
	 * @param array<string, mixed> $module Module definition.
	 * @return string[]
	 */
	function wpb_sdk_build_uninstall_option_names_from_module( $slug, array $module ) {
		$slug    = sanitize_key( (string) $slug );
		$options = array(
			'wpb_sdk_' . $slug,
			'wpb_sdk_' . $slug . '_initial_log_sent',
			'wpb_sdk_' . $slug . '_fallback_verify_token',
			'wpb_sdk_' . $slug . '_uninstall_manifest',
		);

		$optin = isset( $module['optin'] ) && is_array( $module['optin'] ) ? $module['optin'] : array();
		if ( ! empty( $optin['option_name'] ) ) {
			$options[] = (string) $optin['option_name'];
		}
		if ( ! empty( $optin['legacy_token_option'] ) ) {
			$options[] = (string) $optin['legacy_token_option'];
		}
		if ( ! empty( $optin['verified_by_option'] ) ) {
			$options[] = (string) $optin['verified_by_option'];
		}

		if ( ! empty( $module['settings'] ) && is_array( $module['settings'] ) ) {
			foreach ( array_keys( $module['settings'] ) as $key ) {
				$key = (string) $key;
				if ( '' === $key ) {
					continue;
				}
				if (
					0 === strpos( $key, 'wpb_sdk_' )
					|| 0 === strpos( $key, '_' )
				) {
					$options[] = $key;
				}
			}
		}

		$options[] = 'wpb_sdk_module_id';
		$options[] = 'wpb_sdk_module_slug';

		return array_values( array_unique( array_filter( $options ) ) );
	}
}

if ( ! function_exists( 'wpb_sdk_verification_notice_dismissed_meta_key' ) ) {
	/**
	 * User meta key: verification email admin notice dismissed for a product.
	 *
	 * @param string $slug Product slug.
	 * @return string
	 */
	function wpb_sdk_verification_notice_dismissed_meta_key( $slug ) {
		$slug = sanitize_key( (string) $slug );

		return '' === $slug ? '' : 'wpb_sdk_' . $slug . '_verify_notice_dismissed';
	}
}

if ( ! function_exists( 'wpb_sdk_build_uninstall_user_meta_keys_from_module' ) ) {
	/**
	 * User meta keys used for opt-in verification (for uninstall cleanup).
	 *
	 * @param array<string, mixed> $module Module definition.
	 * @return string[]
	 */
	function wpb_sdk_build_uninstall_user_meta_keys_from_module( array $module ) {
		$keys     = array();
		$meta     = isset( $module['optin_user_meta'] ) && is_array( $module['optin_user_meta'] )
			? $module['optin_user_meta']
			: array();
		$token    = ! empty( $meta['token'] ) ? (string) $meta['token'] : '';
		$verified = ! empty( $meta['verified'] ) ? (string) $meta['verified'] : '';

		if ( '' !== $token ) {
			$keys[]  = $token;
			$expires = function_exists( 'wpb_sdk_verification_token_expires_meta_key' )
				? wpb_sdk_verification_token_expires_meta_key( $module )
				: $token . '_expires';
			if ( '' !== $expires ) {
				$keys[] = $expires;
			}
		}
		if ( '' !== $verified ) {
			$keys[] = $verified;
		}

		if ( ! empty( $module['slug'] ) ) {
			$dismiss_key = wpb_sdk_verification_notice_dismissed_meta_key( (string) $module['slug'] );
			if ( '' !== $dismiss_key ) {
				$keys[] = $dismiss_key;
			}
		}

		return array_values( array_unique( array_filter( $keys ) ) );
	}
}

if ( ! function_exists( 'wpb_sdk_store_uninstall_cleanup_manifest' ) ) {
	/**
	 * Persist option/user-meta lists so uninstall cleanup works without loading the plugin.
	 *
	 * @param array<string, mixed> $module Module definition.
	 * @return void
	 */
	function wpb_sdk_store_uninstall_cleanup_manifest( array $module ) {
		if ( empty( $module['slug'] ) ) {
			return;
		}

		$slug  = sanitize_key( (string) $module['slug'] );
		$optin = isset( $module['optin'] ) && is_array( $module['optin'] ) ? $module['optin'] : array();

		$manifest = array(
			'options'         => wpb_sdk_build_uninstall_option_names_from_module( $slug, $module ),
			'user_meta'       => wpb_sdk_build_uninstall_user_meta_keys_from_module( $module ),
			'use_site_option' => ! empty( $optin['use_site_option'] ),
			'optin_option'    => ! empty( $optin['option_name'] ) ? (string) $optin['option_name'] : '',
		);

		update_option(
			'wpb_sdk_' . $slug . '_uninstall_manifest',
			wp_json_encode( $manifest ),
			false
		);
	}
}

if ( ! function_exists( 'wpb_sdk_get_uninstall_cleanup_manifest' ) ) {
	/**
	 * @param string $slug Product slug.
	 * @return array{options: string[], user_meta: string[], use_site_option: bool, optin_option: string}
	 */
	function wpb_sdk_get_uninstall_cleanup_manifest( $slug ) {
		$empty = array(
			'options'         => array(),
			'user_meta'       => array(),
			'use_site_option' => false,
			'optin_option'    => '',
		);

		$slug = sanitize_key( (string) $slug );
		if ( '' === $slug ) {
			return $empty;
		}

		$raw = get_option( 'wpb_sdk_' . $slug . '_uninstall_manifest', '' );
		if ( is_array( $raw ) ) {
			$data = $raw;
		} else {
			$data = json_decode( (string) $raw, true );
		}

		if ( ! is_array( $data ) ) {
			return $empty;
		}

		return array(
			'options'         => ! empty( $data['options'] ) && is_array( $data['options'] )
				? array_map( 'strval', $data['options'] )
				: array(),
			'user_meta'       => ! empty( $data['user_meta'] ) && is_array( $data['user_meta'] )
				? array_map( 'strval', $data['user_meta'] )
				: array(),
			'use_site_option' => ! empty( $data['use_site_option'] ),
			'optin_option'    => ! empty( $data['optin_option'] ) ? (string) $data['optin_option'] : '',
		);
	}
}

if ( ! function_exists( 'wpb_sdk_query_option_names_by_prefix' ) ) {
	/**
	 * @param string $prefix Option name prefix (no trailing %).
	 * @return string[]
	 */
	function wpb_sdk_query_option_names_by_prefix( $prefix ) {
		global $wpdb;

		$prefix = (string) $prefix;
		if ( '' === $prefix ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( $prefix ) . '%'
			)
		);

		return is_array( $rows ) ? array_map( 'strval', $rows ) : array();
	}
}

if ( ! function_exists( 'wpb_sdk_delete_all_users_meta_key' ) ) {
	/**
	 * @param string $meta_key User meta key.
	 * @return void
	 */
	function wpb_sdk_delete_all_users_meta_key( $meta_key ) {
		$meta_key = (string) $meta_key;
		if ( '' === $meta_key ) {
			return;
		}

		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Bulk delete on uninstall.
		$wpdb->delete(
			$wpdb->usermeta,
			array( 'meta_key' => $meta_key ),
			array( '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	}
}

if ( ! function_exists( 'wpb_sdk_foreach_site_on_uninstall' ) ) {
	/**
	 * Run cleanup on the current site or every blog on multisite.
	 *
	 * @param callable $callback Receives blog ID.
	 * @return void
	 */
	function wpb_sdk_foreach_site_on_uninstall( $callback ) {
		if ( ! is_callable( $callback ) ) {
			return;
		}

		if ( ! is_multisite() ) {
			$callback( (int) get_current_blog_id() );
			return;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
		if ( ! is_array( $blog_ids ) ) {
			return;
		}

		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( (int) $blog_id );
			$callback( (int) $blog_id );
			restore_current_blog();
		}
	}
}

if ( ! function_exists( 'wpb_sdk_cleanup_data_on_uninstall' ) ) {
	/**
	 * Remove all SDK options and opt-in user meta for a product on uninstall.
	 *
	 * @param string $slug Product slug.
	 * @return void
	 */
	function wpb_sdk_cleanup_data_on_uninstall( $slug ) {
		$slug = sanitize_key( (string) $slug );
		if ( '' === $slug ) {
			return;
		}

		/**
		 * Whether the SDK should delete its options and user meta on uninstall.
		 *
		 * @param bool   $cleanup Default true.
		 * @param string $slug    Product slug.
		 */
		if ( ! apply_filters( 'wpb_sdk_should_cleanup_on_uninstall', true, $slug ) ) {
			return;
		}

		do_action( 'wpb_sdk_bootstrap_module', $slug );

		$manifest   = wpb_sdk_get_uninstall_cleanup_manifest( $slug );
		$options    = $manifest['options'];
		$user_meta  = $manifest['user_meta'];
		$use_site   = $manifest['use_site_option'];
		$optin_name = $manifest['optin_option'];

		$options = array_unique(
			array_merge( $options, wpb_sdk_query_option_names_by_prefix( 'wpb_sdk_' . $slug ) )
		);

		$module = function_exists( 'wpb_sdk_get_registered_module' )
			? wpb_sdk_get_registered_module( $slug )
			: array();
		if ( ! empty( $module ) ) {
			$options   = array_unique(
				array_merge( $options, wpb_sdk_build_uninstall_option_names_from_module( $slug, $module ) )
			);
			$user_meta = array_unique(
				array_merge( $user_meta, wpb_sdk_build_uninstall_user_meta_keys_from_module( $module ) )
			);
		}

		/**
		 * @param string[] $options  Option names to delete.
		 * @param string   $slug     Product slug.
		 */
		$options = apply_filters( 'wpb_sdk_uninstall_option_names', $options, $slug );

		$dismiss_meta = wpb_sdk_verification_notice_dismissed_meta_key( $slug );
		if ( '' !== $dismiss_meta ) {
			$user_meta[] = $dismiss_meta;
		}
		$user_meta = array_values( array_unique( array_filter( $user_meta ) ) );

		/**
		 * @param string[] $user_meta User meta keys to delete for all users.
		 * @param string   $slug      Product slug.
		 */
		$user_meta = apply_filters( 'wpb_sdk_uninstall_user_meta_keys', $user_meta, $slug );

		delete_transient( 'wpb_sdk_' . $slug . '_pending_verify_notice' );

		if ( $use_site && '' !== $optin_name && is_multisite() ) {
			delete_site_option( $optin_name );
		}

		wpb_sdk_foreach_site_on_uninstall(
			static function () use ( $options, $user_meta, $use_site, $optin_name ) {
				foreach ( $options as $option_name ) {
					if ( '' === $option_name ) {
						continue;
					}
					if ( $use_site && $option_name === $optin_name && is_multisite() ) {
						delete_site_option( $option_name );
					}
					delete_option( $option_name );
				}

				foreach ( $user_meta as $meta_key ) {
					wpb_sdk_delete_all_users_meta_key( $meta_key );
				}
			}
		);
	}
}

if ( ! function_exists( 'wpb_sdk_should_redirect_from_optin_page' ) ) {
	/**
	 * Leave the opt-in admin screen only after the user has allowed (yes).
	 *
	 * Skip and no must stay on the opt-in page so Opt In from Plugins can show the splash again.
	 *
	 * @param string $slug Product slug.
	 * @return bool
	 */
	function wpb_sdk_should_redirect_from_optin_page( $slug ) {
		$decision = wpb_sdk_get_optin_decision( $slug );

		/**
		 * Filter whether visiting the opt-in page should redirect away.
		 *
		 * @param bool   $redirect True when decision is yes (already opted in).
		 * @param string $slug     Product slug.
		 * @param string $decision Raw opt-in option value.
		 */
		return (bool) apply_filters(
			'wpb_sdk_should_redirect_from_optin_page',
			'yes' === $decision,
			$slug,
			$decision
		);
	}
}

if ( ! function_exists( 'wpb_sdk_module_requires_optin_consent' ) ) {
	/**
	 * Whether this product shows an opt-in modal before telemetry may be sent.
	 *
	 * @param string $slug Product slug.
	 * @return bool
	 */
	function wpb_sdk_module_requires_optin_consent( $slug ) {
		$module = wpb_sdk_get_registered_module( $slug );
		$optin  = isset( $module['optin'] ) && is_array( $module['optin'] ) ? $module['optin'] : array();

		return ! empty( $optin['option_name'] );
	}
}

if ( ! function_exists( 'wpb_sdk_has_telemetry_consent' ) ) {
	/**
	 * True when the admin has completed the opt-in modal (yes, no, or skip).
	 *
	 * @param string $slug Product slug.
	 * @return bool
	 */
	function wpb_sdk_has_telemetry_consent( $slug ) {
		if ( ! wpb_sdk_module_requires_optin_consent( $slug ) ) {
			return true;
		}

		$decision = wpb_sdk_get_optin_decision( $slug );

		return in_array( $decision, array( 'yes', 'no', 'skip' ), true );
	}
}

if ( ! function_exists( 'wpb_sdk_user_skipped_optin' ) ) {
	/**
	 * Whether the user chose "Skip" (no ongoing telemetry).
	 *
	 * @param string $slug Product slug.
	 * @return bool
	 */
	function wpb_sdk_user_skipped_optin( $slug ) {
		if ( 'skip' === wpb_sdk_get_optin_decision( $slug ) ) {
			return true;
		}

		$sdk_data = json_decode( (string) get_option( 'wpb_sdk_' . $slug, '' ), true );

		return is_array( $sdk_data )
			&& isset( $sdk_data['user_skip'] )
			&& '1' === (string) $sdk_data['user_skip'];
	}
}

if ( ! function_exists( 'wpb_sdk_allows_ongoing_telemetry' ) ) {
	/**
	 * Whether recurring / lifecycle telemetry may be sent (opt-in "yes" only).
	 *
	 * Skip and no send at most one activate payload; see wpb_sdk_may_send_telemetry_action().
	 *
	 * @param string $slug Product slug.
	 * @return bool
	 */
	function wpb_sdk_allows_ongoing_telemetry( $slug ) {
		if ( ! wpb_sdk_module_requires_optin_consent( $slug ) ) {
			return true;
		}

		return 'yes' === wpb_sdk_get_optin_decision( $slug );
	}
}

if ( ! function_exists( 'wpb_sdk_verification_token_ttl_days' ) ) {
	/**
	 * Days until the email verification link expires (per-product override).
	 *
	 * @param string $slug Product slug.
	 * @return int
	 */
	function wpb_sdk_verification_token_ttl_days( $slug ) {
		$module    = wpb_sdk_get_registered_module( $slug );
		$telemetry = isset( $module['telemetry'] ) && is_array( $module['telemetry'] ) ? $module['telemetry'] : array();
		$days      = isset( $telemetry['verification_token_ttl_days'] )
			? (int) $telemetry['verification_token_ttl_days']
			: 14;

		return max( 1, min( 90, $days ) );
	}
}

if ( ! function_exists( 'wpb_sdk_verification_token_expires_meta_key' ) ) {
	/**
	 * User meta key storing verification token expiry (Unix timestamp).
	 *
	 * @param array<string, mixed> $module Module definition.
	 * @return string
	 */
	function wpb_sdk_verification_token_expires_meta_key( array $module ) {
		$token_meta = ! empty( $module['optin_user_meta']['token'] )
			? (string) $module['optin_user_meta']['token']
			: '';

		return '' !== $token_meta ? $token_meta . '_expires' : '';
	}
}

if ( ! function_exists( 'wpb_sdk_verification_token_expires_at' ) ) {
	/**
	 * Unix timestamp when the current verification token should expire.
	 *
	 * @param array<string, mixed> $module Module definition.
	 * @return int
	 */
	function wpb_sdk_verification_token_expires_at( array $module ) {
		$slug = isset( $module['slug'] ) ? (string) $module['slug'] : '';
		$days = '' !== $slug ? wpb_sdk_verification_token_ttl_days( $slug ) : 14;

		return time() + ( $days * DAY_IN_SECONDS );
	}
}

if ( ! function_exists( 'wpb_sdk_ensure_verification_token_expiry_meta' ) ) {
	/**
	 * Return token expiry timestamp; backfill TTL when a token exists but expiry meta is missing.
	 *
	 * @param string $slug    Product slug.
	 * @param int    $user_id Optional admin user ID.
	 * @return int Unix expiry timestamp, or 0 when no token.
	 */
	function wpb_sdk_ensure_verification_token_expiry_meta( $slug, $user_id = 0 ) {
		$module = wpb_sdk_get_registered_module( $slug );
		if ( empty( $module['optin_user_meta']['token'] ) ) {
			return 0;
		}

		$user_id = (int) $user_id;
		if ( $user_id < 1 ) {
			$admins  = get_users(
				array(
					'role'    => 'administrator',
					'number'  => 1,
					'orderby' => 'ID',
					'order'   => 'ASC',
					'fields'  => 'ID',
				)
			);
			$user_id = ! empty( $admins[0] ) ? (int) $admins[0] : 0;
		}

		if ( $user_id < 1 ) {
			return 0;
		}

		$token_meta   = (string) $module['optin_user_meta']['token'];
		$expires_meta = wpb_sdk_verification_token_expires_meta_key( $module );
		$token        = get_user_meta( $user_id, $token_meta, true );

		if ( ! is_string( $token ) || '' === $token || '' === $expires_meta ) {
			return 0;
		}

		$expires_at = (int) get_user_meta( $user_id, $expires_meta, true );

		if ( $expires_at < 1 ) {
			$expires_at = wpb_sdk_verification_token_expires_at( $module );
			update_user_meta( $user_id, $expires_meta, $expires_at );
		}

		return $expires_at;
	}
}

if ( ! function_exists( 'wpb_sdk_is_verification_token_expired' ) ) {
	/**
	 * Whether the stored verification token is past its TTL.
	 *
	 * @param string $slug    Product slug.
	 * @param int    $user_id Optional admin user ID.
	 * @return bool
	 */
	function wpb_sdk_is_verification_token_expired( $slug, $user_id = 0 ) {
		$expires_at = wpb_sdk_ensure_verification_token_expiry_meta( $slug, $user_id );

		if ( $expires_at < 1 ) {
			return true;
		}

		return time() > $expires_at;
	}
}

if ( ! function_exists( 'wpb_sdk_has_pending_verification_token' ) ) {
	/**
	 * Whether a verification email was issued and the link was not used yet.
	 *
	 * @param string $slug    Product slug.
	 * @param int    $user_id Optional WordPress user ID.
	 * @return bool
	 */
	function wpb_sdk_has_pending_verification_token( $slug, $user_id = 0 ) {
		$module = wpb_sdk_get_registered_module( $slug );
		if ( empty( $module['optin_user_meta']['token'] ) ) {
			return false;
		}

		$user_id = (int) $user_id;
		if ( $user_id < 1 ) {
			$admins  = get_users(
				array(
					'role'    => 'administrator',
					'number'  => 1,
					'orderby' => 'ID',
					'order'   => 'ASC',
					'fields'  => 'ID',
				)
			);
			$user_id = ! empty( $admins[0] ) ? (int) $admins[0] : 0;
		}

		$token_meta = (string) $module['optin_user_meta']['token'];
		if ( $user_id > 0 ) {
			$pending_token = get_user_meta( $user_id, $token_meta, true );
			if ( is_string( $pending_token ) && '' !== $pending_token ) {
				return true;
			}
		}

		$fallback = get_option( 'wpb_sdk_' . $slug . '_fallback_verify_token', '' );

		return is_string( $fallback ) && '' !== $fallback;
	}
}

if ( ! function_exists( 'wpb_sdk_is_legacy_optin_grandfather_eligible' ) ) {
	/**
	 * Opted-in before email verification / initial_log_sent existed (pre-centralized SDK).
	 *
	 * New Allow clicks set wpb_sdk_{slug}_pending_verify_notice; legacy upgrades do not.
	 *
	 * @param string $slug    Product slug.
	 * @param int    $user_id Optional WordPress user ID.
	 * @return bool
	 */
	function wpb_sdk_is_legacy_optin_grandfather_eligible( $slug, $user_id = 0 ) {
		if ( ! function_exists( 'wpb_sdk_get_optin_decision' ) || 'yes' !== wpb_sdk_get_optin_decision( $slug ) ) {
			return false;
		}

		if ( '1' === (string) get_option( 'wpb_sdk_' . $slug . '_initial_log_sent', '' ) ) {
			return false;
		}

		if ( get_transient( 'wpb_sdk_' . $slug . '_pending_verify_notice' ) ) {
			return false;
		}

		if ( function_exists( 'wpb_sdk_has_pending_verification_token' )
			&& wpb_sdk_has_pending_verification_token( $slug, $user_id ) ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'wpb_sdk_apply_legacy_optin_grandfather' ) ) {
	/**
	 * One-time: mark pre-verification opt-ins as verified and allow ongoing telemetry.
	 *
	 * @param string $slug    Product slug.
	 * @param int    $user_id Optional WordPress user ID.
	 * @return void
	 */
	function wpb_sdk_apply_legacy_optin_grandfather( $slug, $user_id = 0 ) {
		update_option( 'wpb_sdk_' . $slug . '_initial_log_sent', '1', false );

		$user_id = (int) $user_id;
		if ( $user_id < 1 ) {
			$admins  = get_users(
				array(
					'role'    => 'administrator',
					'number'  => 1,
					'orderby' => 'ID',
					'order'   => 'ASC',
					'fields'  => 'ID',
				)
			);
			$user_id = ! empty( $admins[0] ) ? (int) $admins[0] : 0;
		}

		$module = wpb_sdk_get_registered_module( $slug );
		if ( $user_id > 0 && ! empty( $module['optin_user_meta']['verified'] ) ) {
			update_user_meta( $user_id, (string) $module['optin_user_meta']['verified'], 'yes' );
		}

		delete_transient( 'wpb_sdk_' . $slug . '_pending_verify_notice' );
		delete_option( 'wpb_sdk_' . $slug . '_fallback_verify_token' );
	}
}

if ( ! function_exists( 'wpb_sdk_is_optin_email_verified' ) ) {
	/**
	 * Whether the site admin completed email verification for this product.
	 *
	 * @param string $slug    Product slug.
	 * @param int    $user_id Optional WordPress user ID.
	 * @return bool
	 */
	function wpb_sdk_is_optin_email_verified( $slug, $user_id = 0 ) {
		$module = wpb_sdk_get_registered_module( $slug );
		if ( empty( $module['optin_user_meta']['verified'] ) ) {
			return true;
		}

		$user_id = (int) $user_id;
		if ( $user_id < 1 ) {
			$admins  = get_users(
				array(
					'role'    => 'administrator',
					'number'  => 1,
					'orderby' => 'ID',
					'order'   => 'ASC',
					'fields'  => 'ID',
				)
			);
			$user_id = ! empty( $admins[0] ) ? (int) $admins[0] : 0;
		}

		if ( $user_id < 1 ) {
			return false;
		}

		$verified_meta = (string) $module['optin_user_meta']['verified'];
		$verified_raw  = get_user_meta( $user_id, $verified_meta, true );

		if ( is_string( $verified_raw ) && 'yes' === strtolower( $verified_raw ) ) {
			return true;
		}

		if ( function_exists( 'wpb_sdk_has_pending_verification_token' )
			&& wpb_sdk_has_pending_verification_token( $slug, $user_id ) ) {
			return false;
		}

		$initial_sent = (string) get_option( 'wpb_sdk_' . $slug . '_initial_log_sent', '' );

		if ( '1' === $initial_sent && function_exists( 'wpb_sdk_get_optin_decision' ) && 'yes' === wpb_sdk_get_optin_decision( $slug ) ) {
			$optin    = isset( $module['optin'] ) && is_array( $module['optin'] ) ? $module['optin'] : array();
			$use_site = ! empty( $optin['use_site_option'] );
			$sdk_raw  = $use_site
				? get_site_option( 'wpb_sdk_' . $slug, '' )
				: get_option( 'wpb_sdk_' . $slug, '' );
			$sdk_data = json_decode( (string) $sdk_raw, true );
			if ( is_array( $sdk_data ) ) {
				foreach ( array( 'communication', 'diagnostic_info', 'extensions' ) as $flag ) {
					$value = isset( $sdk_data[ $flag ] ) ? $sdk_data[ $flag ] : '0';
					if ( wpb_sdk_sdk_option_is_enabled( $value ) ) {
						return true;
					}
				}
			}
		}

		if ( function_exists( 'wpb_sdk_is_legacy_optin_grandfather_eligible' )
			&& wpb_sdk_is_legacy_optin_grandfather_eligible( $slug, $user_id ) ) {
			wpb_sdk_apply_legacy_optin_grandfather( $slug, $user_id );
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'wpb_sdk_may_send_telemetry_action' ) ) {
	/**
	 * Whether a telemetry action may be sent for this product.
	 *
	 * - No modal yet: nothing.
	 * - Yes: all actions (daily only after email verified).
	 * - Skip: one activate only (until initial log succeeds).
	 * - No: nothing.
	 *
	 * @param string $slug   Product slug.
	 * @param string $action activate|deactivate|uninstall|daily|''.
	 * @return bool
	 */
	function wpb_sdk_may_send_telemetry_action( $slug, $action = '' ) {
		if ( ! wpb_sdk_module_requires_optin_consent( $slug ) ) {
			return true;
		}

		if ( wpb_sdk_allows_ongoing_telemetry( $slug ) ) {
			if (
				'daily' === $action
				&& function_exists( 'wpb_sdk_is_optin_email_verified' )
				&& ! wpb_sdk_is_optin_email_verified( $slug )
			) {
				return false;
			}

			return true;
		}

		if ( 'activate' === $action && wpb_sdk_user_skipped_optin( $slug ) ) {
			$sent_key = 'wpb_sdk_' . $slug . '_initial_log_sent';

			return '1' !== (string) get_option( $sent_key, '' );
		}

		return false;
	}
}

if ( ! function_exists( 'wpb_sdk_scan_active_plugin_basename' ) ) {
	/**
	 * Find an active plugin basename whose folder or main file matches the slug.
	 *
	 * @param string $slug Product slug (e.g. loginpress, wp-analytify).
	 * @return string Plugin basename relative to wp-content/plugins, or empty.
	 */
	function wpb_sdk_scan_active_plugin_basename( $slug ) {
		$slug = (string) $slug;
		if ( '' === $slug ) {
			return '';
		}

		$files = array();
		foreach ( (array) get_option( 'active_plugins', array() ) as $file ) {
			if ( is_string( $file ) && '' !== $file ) {
				$files[] = $file;
			}
		}

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			foreach ( array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) as $file ) {
				if ( is_string( $file ) && '' !== $file ) {
					$files[] = $file;
				}
			}
		}

		$file_pattern = '#/' . preg_quote( $slug, '#' ) . '\\.php$#';
		foreach ( array_unique( $files ) as $file ) {
			$dir = dirname( $file );
			if ( $dir === $slug || basename( $dir ) === $slug ) {
				return $file;
			}
			if ( preg_match( $file_pattern, $file ) ) {
				return $file;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'wpb_sdk_get_plugin_path' ) ) {
	/**
	 * Resolve the main plugin file path for a product slug.
	 *
	 * Order: module plugin_file → filter → active plugin scan → {slug}/{slug}.php.
	 *
	 * @param string $slug Product slug.
	 * @return string Absolute path to main plugin file (may not exist yet).
	 */
	function wpb_sdk_get_plugin_path( $slug ) {
		$slug = (string) $slug;
		if ( '' === $slug || ! defined( 'WP_PLUGIN_DIR' ) ) {
			return '';
		}

		$module = wpb_sdk_get_registered_module( $slug );
		if ( ! empty( $module['plugin_file'] ) ) {
			$path = function_exists( 'wp_normalize_path' )
				? wp_normalize_path( (string) $module['plugin_file'] )
				: (string) $module['plugin_file'];
			if ( is_readable( $path ) ) {
				return $path;
			}
		}

		/**
		 * Filter the resolved main plugin file before scanning active plugins.
		 *
		 * @param string $path Empty string or absolute path.
		 * @param string $slug Product slug.
		 */
		$filtered = apply_filters( 'wpb_sdk_plugin_main_file', '', $slug );
		if ( is_string( $filtered ) && '' !== $filtered ) {
			$filtered = function_exists( 'wp_normalize_path' )
				? wp_normalize_path( $filtered )
				: $filtered;
			if ( is_readable( $filtered ) ) {
				return $filtered;
			}
		}

		$basename = wpb_sdk_scan_active_plugin_basename( $slug );
		if ( '' !== $basename ) {
			$path = WP_PLUGIN_DIR . '/' . ltrim( $basename, '/\\' );
			if ( function_exists( 'wp_normalize_path' ) ) {
				$path = wp_normalize_path( $path );
			}
			if ( is_readable( $path ) ) {
				return $path;
			}
		}

		$fallback = WP_PLUGIN_DIR . '/' . $slug . '/' . $slug . '.php';

		return function_exists( 'wp_normalize_path' )
			? wp_normalize_path( $fallback )
			: $fallback;
	}
}

if ( ! function_exists( 'wpb_sdk_resolve_plugin_basename' ) ) {
	/**
	 * Active plugin basename for plugins.php (deactivate link / nonce).
	 *
	 * @param string $slug Product slug.
	 * @return string Plugin basename or empty string.
	 */
	function wpb_sdk_resolve_plugin_basename( $slug ) {
		$path = wpb_sdk_get_plugin_path( $slug );
		if ( is_readable( $path ) ) {
			return plugin_basename( $path );
		}

		$scanned = wpb_sdk_scan_active_plugin_basename( $slug );

		return is_string( $scanned ) ? $scanned : '';
	}
}

if ( ! function_exists( 'wpb_sdk_get_plugin_details' ) ) {
	/**
	 * Get plugin header data by slug.
	 *
	 * @param string $slug Plugin slug.
	 * @return array<string, string>|null
	 */
	function wpb_sdk_get_plugin_details( $slug ) {
		$plugin_path = wpb_sdk_get_plugin_path( $slug );
		if ( ! is_readable( $plugin_path ) ) {
			return null;
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return get_plugin_data( $plugin_path );
	}
}

if ( ! function_exists( 'wpb_get_plugin_details' ) ) {
	/**
	 * Legacy wrapper.
	 *
	 * @param string $slug Plugin slug.
	 * @return array<string, string>|null
	 */
	function wpb_get_plugin_details( $slug ) {
		return wpb_sdk_get_plugin_details( $slug );
	}
}

if ( ! function_exists( 'wpb_get_plugin_path' ) ) {
	/**
	 * Legacy wrapper.
	 *
	 * @param string $slug Plugin slug.
	 * @return string
	 */
	function wpb_get_plugin_path( $slug ) {
		return wpb_sdk_get_plugin_path( $slug );
	}
}

if ( ! function_exists( 'wpb_sdk_dev_view_resolve_product' ) ) {
	/**
	 * Resolve slug and module id for SDK dev views (account, debug).
	 *
	 * @return array{slug: string, module_id: string}
	 */
	function wpb_sdk_dev_view_resolve_product() {
		$slug      = '';
		$module_id = '1';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Dev-only slug picker; views require manage_options.
		if ( isset( $_GET['wpb_sdk_slug'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$requested = sanitize_key( wp_unslash( (string) $_GET['wpb_sdk_slug'] ) );
			if (
				'' !== $requested
				&& function_exists( 'wpb_sdk_get_registered_module' )
				&& ! empty( wpb_sdk_get_registered_module( $requested ) )
			) {
				$slug = $requested;
			}
		}

		if ( '' === $slug && ! empty( $GLOBALS['wpb_sdk_registry']['modules'] ) && is_array( $GLOBALS['wpb_sdk_registry']['modules'] ) ) {
			$registry_slugs = array_keys( $GLOBALS['wpb_sdk_registry']['modules'] );
			if ( ! empty( $registry_slugs[0] ) ) {
				$slug = (string) $registry_slugs[0];
			}
		}

		if (
			'' === $slug
			&& ! empty( $GLOBALS['wpb_sdk_registry']['initialized_modules'] )
			&& is_array( $GLOBALS['wpb_sdk_registry']['initialized_modules'] )
		) {
			$registry_slugs = array_keys( array_filter( $GLOBALS['wpb_sdk_registry']['initialized_modules'] ) );
			if ( ! empty( $registry_slugs[0] ) ) {
				$slug = (string) $registry_slugs[0];
			}
		}

		if ( '' !== $slug && function_exists( 'wpb_sdk_get_registered_module' ) ) {
			$module = wpb_sdk_get_registered_module( $slug );
			if ( ! empty( $module['id'] ) ) {
				$module_id = (string) $module['id'];
			}
		}

		return array(
			'slug'      => $slug,
			'module_id' => $module_id,
		);
	}
}

if ( ! function_exists( 'wpb_sdk_dev_view_load_logs_data' ) ) {
	/**
	 * Build telemetry payload for SDK dev views without calling instance() with empty ids.
	 *
	 * @param string $slug Product slug.
	 * @return array<string, mixed>
	 */
	function wpb_sdk_dev_view_load_logs_data( $slug ) {
		if ( ! class_exists( 'WPBRIGADE_Logger' ) || '' === $slug ) {
			return array();
		}

		return WPBRIGADE_Logger::get_logs_data( $slug );
	}
}

if ( ! function_exists( 'wpb_sdk_dev_view_default_logs_data' ) ) {
	/**
	 * Empty payload shape for dev views when no module is bootstrapped.
	 *
	 * @return array<string, mixed>
	 */
	function wpb_sdk_dev_view_default_logs_data() {
		return array(
			'user_info'      => array(
				'user_nickname' => '',
				'user_email'    => '',
			),
			'product_info'   => array(
				'name'    => '',
				'version' => '',
			),
			'authentication' => array(
				'public_key' => '',
			),
		);
	}
}

if ( ! function_exists( 'wpb_sdk_custom_admin_menu' ) ) {
	/**
	 * Register SDK debug menu in dev mode.
	 *
	 * @return void
	 */
	function wpb_sdk_custom_admin_menu() {
		if ( ! defined( 'WPBRIGADE_SDK__DEV_MODE' ) || true !== WPBRIGADE_SDK__DEV_MODE ) {
			return;
		}

		$version = defined( 'WP_WPBRIGADE_SDK_VERSION' ) ? WP_WPBRIGADE_SDK_VERSION : '';

		add_menu_page(
			__( 'WPB SDK Debug', 'wpbrigade-sdk' ),
			$version ? 'WPB-SDK Debug [' . $version . ']' : 'WPB-SDK Debug',
			'manage_options',
			'wpb-debug-mode',
			'wpb_sdk_custom_page_content'
		);
	}
}

if ( ! function_exists( 'wpb_sdk_custom_page_content' ) ) {
	/**
	 * Render SDK debug page.
	 *
	 * @return void
	 */
	function wpb_sdk_custom_page_content() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to access this page.', 'wpbrigade-sdk' ),
				'',
				array( 'response' => 403 )
			);
		}

		if ( defined( 'WPBRIGADE_SDK__DEV_MODE' ) && true === WPBRIGADE_SDK__DEV_MODE ) {
			$debug_view = __DIR__ . '/../views/wpb-debug.php';
			if ( is_readable( $debug_view ) ) {
				include_once $debug_view;
			}
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WPB SDK Debug', 'wpbrigade-sdk' ); ?></h1>
		</div>
		<?php
	}
}

if ( ! function_exists( 'wpb_sdk_custom_account_menu' ) ) {
	/**
	 * Register temporary SDK account menu in dev mode.
	 *
	 * @return void
	 */
	function wpb_sdk_custom_account_menu() {
		if ( defined( 'WPBRIGADE_SDK__DEV_MODE' ) && true === WPBRIGADE_SDK__DEV_MODE ) {
			add_menu_page(
				__( 'WPB SDK Account', 'wpbrigade-sdk' ),
				__( 'account', 'wpbrigade-sdk' ),
				'manage_options',
				'account',
				'wpb_sdk_account_page_content'
			);
		}

		add_action( 'admin_enqueue_scripts', 'wpb_sdk_delayed_remove_menu_page' );
	}
}

if ( ! function_exists( 'wpb_sdk_account_page_content' ) ) {
	/**
	 * Render SDK account page.
	 *
	 * @return void
	 */
	function wpb_sdk_account_page_content() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to access this page.', 'wpbrigade-sdk' ),
				'',
				array( 'response' => 403 )
			);
		}

		if ( defined( 'WPBRIGADE_SDK__DEV_MODE' ) && true === WPBRIGADE_SDK__DEV_MODE ) {
			$account_view = __DIR__ . '/../views/account.php';
			if ( is_readable( $account_view ) ) {
				include_once $account_view;
			}
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WPB SDK Account', 'wpbrigade-sdk' ); ?></h1>
		</div>
		<?php
	}
}

if ( ! function_exists( 'wpb_sdk_delayed_remove_menu_page' ) ) {
	/**
	 * Remove temporary SDK account page.
	 *
	 * @return void
	 */
	function wpb_sdk_delayed_remove_menu_page() {
		remove_menu_page( 'account' );
	}
}

add_action( 'wp_wpb_sdk_after_uninstall', 'wpb_sdk_cleanup_data_on_uninstall', 5 );

add_action( 'admin_menu', 'wpb_sdk_custom_admin_menu', 999 );
add_action( 'admin_menu', 'wpb_sdk_custom_account_menu' );
