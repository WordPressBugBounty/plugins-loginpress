<?php
/**
 * LoginPress Customizer Entities Class.
 *
 * Some functions are moved into traits to keep main file slimmer.
 *
 * @package   LoginPress
 * @since 1.0.0
 * @version 3.0.0
 */

/**
 * Ignore next line for PHPStan analysis.
 *
 * @phpstan-ignore-next-line
 */
require_once LOGINPRESS_DIR_PATH . 'classes/customizer/loginpress-customizer-core-trait.php';
/**
 * Ignore next line for PHPStan analysis.
 *
 * @phpstan-ignore-next-line
 */
require_once LOGINPRESS_DIR_PATH . 'classes/customizer/loginpress-customizer-layout-trait.php';
/**
 * Ignore next line for PHPStan analysis.
 *
 * @phpstan-ignore-next-line
 */
require_once LOGINPRESS_DIR_PATH . 'classes/customizer/loginpress-customizer-sections-trait.php';
/**
 * Ignore next line for PHPStan analysis.
 *
 * @phpstan-ignore-next-line
 */
require_once LOGINPRESS_DIR_PATH . 'classes/customizer/loginpress-customizer-background-trait.php';
/**
 * Ignore next line for PHPStan analysis.
 *
 * @phpstan-ignore-next-line
 */
require_once LOGINPRESS_DIR_PATH . 'classes/customizer/loginpress-customizer-form-trait.php';
/**
 * Ignore next line for PHPStan analysis.
 *
 * @phpstan-ignore-next-line
 */
require_once LOGINPRESS_DIR_PATH . 'classes/customizer/loginpress-customizer-messages-trait.php';

/**
 * LoginPress Customizer Entities Class.
 *
 * Handles customizer functionality for LoginPress.
 *
 * @package   LoginPress
 * @since 1.0.0
 * @version 3.0.0
 */
class LoginPress_Customizer {
	use LoginPress_Customizer_Core;
	use LoginPress_Customizer_Layout;
	use LoginPress_Customizer_Sections;
	use LoginPress_Customizer_Background;
	use LoginPress_Customizer_Form;
	use LoginPress_Customizer_Messages;

	/**
	 * Variable that Check for LoginPress Key.
	 *
	 * @var array<string, mixed>|string
	 * @since 1.0.0
	 * @version 3.0.0
	 */
	public $loginpress_key;

	/**
	 * Variable that Check for LoginPress Settings.
	 *
	 * @var array<string, mixed>
	 * @since 6.0.0
	 */
	public $loginpress_settings;

	/**
	 * LoginPress template name.
	 *
	 * @since 1.6.4
	 * @var string LoginPress template name.
	 */
	public $loginpress_preset;

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 * @version 3.0.0
	 * @return void
	 */
	public function __construct() {
		$this->loginpress_key      = get_option( 'loginpress_customization' );
		$this->loginpress_settings = get_option( 'loginpress_setting' );
		$this->loginpress_preset   = get_option( 'customize_presets_settings', true );
		$this->hooks();
	}


	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 * @version 6.0.0
	 * @return void
	 */
	private function hooks() {
		add_filter( 'login_title', array( $this, 'login_page_title' ), 99 );
		add_filter( 'login_headerurl', array( $this, 'login_page_logo_url' ) );
		if ( version_compare( $GLOBALS['wp_version'], '5.2', '<' ) ) {
			add_filter( 'login_headertitle', array( $this, 'login_page_logo_title' ) );
		} else {
			add_filter( 'login_headertext', array( $this, 'login_page_logo_title' ) );
		}

		add_filter( 'login_errors', array( $this, 'login_error_messages' ) );
		add_filter( 'login_message', array( $this, 'change_welcome_message' ), 1, 1 );
		add_action( 'customize_register', array( $this, 'customize_login_panel' ) );
		add_action( 'login_footer', array( $this, 'login_page_custom_footer' ) );
		add_filter( 'site_icon_meta_tags', array( $this, 'login_page_custom_favicon' ), 1, 1 );
		add_action( 'login_head', array( $this, 'login_page_custom_head' ) );
		add_action( 'woocommerce_login_form', array( $this, 'loginpress_wc_login_page_url_redirection' ) );
		add_action( 'init', array( $this, 'redirect_to_custom_page' ) );
		add_action( 'admin_menu', array( $this, 'menu_url' ), 10 );
		add_filter( 'wp_login_errors', array( $this, 'remove_error_messages_in_wp_customizer' ), 10, 2 );
		add_action( 'login_enqueue_scripts', array( $this, 'loginpress_login_page_scripts' ) );

		if ( version_compare( $GLOBALS['wp_version'], '5.9', '>=' ) ) {
			add_filter( 'login_display_language_dropdown', array( $this, 'loginpress_language_switch' ) );
		}

		/**
		 * This function enqueues scripts and styles in the Customizer.
		 */
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'loginpress_customizer_js' ) );

		/**
		 * This function is triggered on the initialization of the Previewer in the Customizer.
		 * We add actions that pertain to the Previewer window here.
		 * The actions added here are triggered only in the Previewer and not in the Customizer.
		 *
		 * @since 1.0.23
		 * @version 6.0.0
		 */
		add_action( 'customize_preview_init', array( $this, 'loginpress_customizer_previewer_js' ) );
		add_filter( 'woocommerce_process_login_errors', array( $this, 'loginpress_woo_login_errors' ), 10, 3 );

		if (
			isset( $this->loginpress_settings['enable_special_chars'] ) && 'on' === $this->loginpress_settings['enable_special_chars']
			&& isset( $this->loginpress_settings['allowed_username_characters'] ) && ! empty( $this->loginpress_settings['allowed_username_characters'] )
		) {
			add_filter( 'sanitize_user', array( $this, 'loginpress_sanitize_username' ), 10, 3 );
		}
	}

	/**
	 * Login Page Custom Favicon ( Overwrites the default meta tags if favicon is set from LoginPress ).
	 *
	 * @param array<string> $meta_tags default meta tags for login page.
	 * @return array<string> $meta_tags modified meta tags for login page.
	 *
	 * @since 3.0.0
	 */
	public function login_page_custom_favicon( $meta_tags ) {
		$login_favicon = 'off';
		if ( is_array( $this->loginpress_key ) && isset( $this->loginpress_key['login_favicon'] ) ) {
			$login_favicon = $this->loginpress_key['login_favicon'];
		}

		if ( has_site_icon() && ! empty( $login_favicon ) ) {
			/**
			 * If Login favicon is set then only show the favicon of login page else site icon will be shown.
			 */
			if ( 'off' !== $login_favicon && function_exists( 'login_header' ) ) {
				unset( $meta_tags );
				$meta_tags[] = '<link rel="shortcut icon" href="' . esc_url( $login_favicon ) . '" type="image/x-icon" />';
			}
		}
		return $meta_tags;
	}

	/**
	 * Login Page YouTube Video Background scripts.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function loginpress_login_page_scripts() {
		$loginpress_customization = get_option( 'loginpress_customization' );
		$loginpress_yt_id         = isset( $loginpress_customization['yt_video_id'] ) && ! empty( $loginpress_customization['yt_video_id'] ) ? $loginpress_customization['yt_video_id'] : false;

		if ( $loginpress_yt_id ) {
			wp_enqueue_script( 'loginpress-yt-iframe', 'https://www.youtube.com/iframe_api', array(), LOGINPRESS_VERSION, true );
		}
	}

	/**
	 * Enqueue jQuery and use wp_localize_script.
	 *
	 * @since 1.0.9
	 * @version 6.0.0
	 * @return void
	 */
	public function loginpress_customizer_js() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'loginpress-customize-control', plugins_url( 'classes/customizer/js/customize-controls.js', LOGINPRESS_ROOT_FILE ), array( 'jquery' ), LOGINPRESS_VERSION, true );
		wp_enqueue_script( 'loginpress-color-picker-iris-script', plugins_url( 'classes/customizer/js/controls/loginpress-color-picker-alpha-iris.js', LOGINPRESS_ROOT_FILE ), array( 'wp-color-picker' ), LOGINPRESS_VERSION, true );

		/*
		 * Our Customizer script.
		 *
		 * Dependencies: Customizer Controls script (core).
		 */
		wp_enqueue_script( 'loginpress-control-script', plugins_url( 'classes/customizer/js/customizer.js', LOGINPRESS_ROOT_FILE ), array( 'customize-controls' ), LOGINPRESS_VERSION, true );

		// Get Background URL for use in Customizer JS.
		$user              = wp_get_current_user();
		$name              = empty( $user->user_firstname ) ? ucfirst( $user->display_name ) : ucfirst( $user->user_firstname );
		$loginpress_bg     = get_option( 'loginpress_customization' );
		$cap_type          = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot'; // 1.2.1
		$loginpress_bg_url = $loginpress_bg['setting_background'] ? $loginpress_bg['setting_background'] : false;

		/**
		 * Included in version 1.2.0.
		 */
		if ( isset( $_GET['autofocus'] ) && sanitize_text_field( wp_unslash( $_GET['autofocus'] ) ) === 'loginpress_panel' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for display purposes.
			$loginpress_auto_focus = true;
		} else {
			$loginpress_auto_focus = false;
		}

		// Array for localize.
		$loginpress_localize = array(
			'admin_url'          => admin_url(),
			'ajaxurl'            => admin_url( 'admin-ajax.php' ),
			'plugin_url'         => plugins_url(),
			'login_theme'        => $this->loginpress_preset,
			'loginpress_bg_url'  => $loginpress_bg_url,
			'preset_nonce'       => wp_create_nonce( 'loginpress-preset-nonce' ),
			'attachment_nonce'   => wp_create_nonce( 'loginpress-attachment-nonce' ),
			'preset_loader'      => includes_url( 'js/tinymce/skins/lightgray/img/loader.gif' ),
			'autoFocusPanel'     => $loginpress_auto_focus,
			'recaptchaType'      => $cap_type,
			'filter_bg'          => apply_filters( 'loginpress_default_bg', '' ),
			'translated_strings' => array(
				'wrong_yt_id' => _x( 'Wrong YouTube Video ID', 'Wrong YouTube Video ID (Customizer)', 'loginpress' ),
			),
		);

		$loginpress_customizer_localize = array(
			'translations' => array(
				'title'    => _x( 'Add Images to Gallery', 'Add Images to Gallery (Customizer)', 'loginpress' ),
				'btn_text' => _x( 'Add to Gallery', 'Add to Gallery Button Text (Customizer)', 'loginpress' ),
			),
		);

		wp_localize_script( 'loginpress-customize-control', 'loginpress_script', $loginpress_localize );
		wp_localize_script( 'loginpress-control-script', 'loginpress_customizer', $loginpress_customizer_localize );
	}

	/**
	 * This function is called only on the Previewer and enqueues scripts and styles.
	 * Our Customizer script.
	 *
	 * Dependencies: Customizer Preview script (core).
	 *
	 * @since 1.0.23
	 * @return void
	 */
	public function loginpress_customizer_previewer_js() {
		wp_enqueue_style( 'loginpress-customizer-previewer-style', plugins_url( 'classes/customizer/css/style-previewer.css', LOGINPRESS_ROOT_FILE ), array(), LOGINPRESS_VERSION );
		wp_enqueue_script( 'loginpress-customizer-previewer-script', plugins_url( 'classes/customizer/js/customizer-previewer.js', LOGINPRESS_ROOT_FILE ), array( 'customize-preview' ), LOGINPRESS_VERSION, true );
	}

	/**
	 * Creates a method for setting and controlling LoginPress_Range_Control.
	 *
	 * @param mixed                       $wp_customize The WordPress Customize object.
	 * @param array<string>               $control The control name.
	 * @param array<string>               $default The default value.
	 * @param array<string>               $label The label for the control.
	 * @param array<array<string, mixed>> $input_attr Additional input attributes.
	 * @param array<string>               $unit The unit for the control value.
	 * @param string                      $section The section name.
	 * @param int                         $index The index of the control.
	 * @param string|int                  $priority To set the Priority of the section.
	 *
	 * @return mixed The modified WordPress Customize object.
	 *
	 * @since 1.1.3
	 */
	public function loginpress_range_setting( $wp_customize, $control, $default, $label, $input_attr, $unit, $section, $index, $priority = '' ) { // phpcs:ignore
		$wp_customize->add_setting(
			"loginpress_customization[{$control[$index]}]",
			array(
				'default'           => $default[ $index ],
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'absint',
			)
		);

		$wp_customize->add_control(
			new LoginPress_Range_Control(
				$wp_customize,
				"loginpress_customization[{$control[$index]}]",
				array(
					'type'        => 'loginpress-range',
					'label'       => $label[ $index ],
					'section'     => $section,
					'priority'    => $priority,
					'settings'    => "loginpress_customization[{$control[$index]}]",
					'default'     => $default[ $index ],
					'input_attrs' => $input_attr[ $index ],
					'unit'        => $unit[ $index ],
				)
			)
		);
	}


	/**
	 * Creates a method for setting and controlling LoginPress_Group_Control.
	 *
	 * @param mixed         $wp_customize The WordPress Customize object.
	 * @param array<string> $control The control name.
	 * @param array<string> $label The label for the control.
	 * @param array<string> $info_test The information text.
	 * @param string        $section The section name.
	 * @param int           $index The index of the control.
	 * @param string|int    $priority To set the Priority of the section.
	 *
	 * @return mixed The modified WordPress Customize object.
	 *
	 * @since 1.1.3
	 */
	public function loginpress_group_setting( $wp_customize, $control, $label, $info_test, $section, $index, $priority = '' ) {
		$wp_customize->add_setting(
			"loginpress_customization[{$control[$index]}]",
			array(
				'type'       => 'option',
				'capability' => 'manage_options',
				'transport'  => 'postMessage',
			)
		);

		$wp_customize->add_control(
			new LoginPress_Group_Control(
				$wp_customize,
				"loginpress_customization[{$control[$index]}]",
				array(
					'settings'  => "loginpress_customization[{$control[$index]}]",
					'label'     => $label[ $index ],
					'section'   => $section,
					'type'      => 'group',
					'info_text' => $info_test[ $index ],
					'priority'  => $priority,
				)
			)
		);
	}

	/**
	 * Creates a method for setting and controlling WP_Customize_Color_Control.
	 *
	 * @param mixed         $wp_customize The WordPress Customize object.
	 * @param array<string> $control The control name.
	 * @param array<string> $label The label for the control.
	 * @param string        $section The section name.
	 * @param int           $index The index of the control.
	 * @param string|int    $priority To set the Priority of the section.
	 *
	 * @return mixed The modified WordPress Customize object.
	 * @since 1.1.3
	 */
	public function loginpress_color_setting( $wp_customize, $control, $label, $section, $index, $priority = '' ) {
		$wp_customize->add_setting(
			"loginpress_customization[{$control[$index]}]",
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'sanitize_text_field', // validates 3 or 6 digit HTML hex color code.
			)
		);

		$wp_customize->add_control(
			new LoginPress_Color_Picker_Alpha(
				$wp_customize,
				"loginpress_customization[{$control[$index]}]",
				array(
					'label'       => $label[ $index ],
					'section'     => $section,
					'settings'    => "loginpress_customization[{$control[$index]}]",
					'priority'    => $priority,
					'input_attrs' => array(
						'name'               => $label[ $index ],
						'data-alpha-enabled' => 'true',
					),
				)
			)
		);
	}

	/**
	 * Creates a thematic break.
	 *
	 * @param mixed         $wp_customize The WordPress Customize object.
	 * @param array<string> $control The control name.
	 * @param string        $section The section name.
	 * @param int           $index The index of the control.
	 * @param string|int    $priority To set the Priority of the section.
	 *
	 * @return void
	 * @since 1.1.3
	 * @version 3.0.0
	 */
	public function loginpress_hr_setting( $wp_customize, $control, $section, $index, $priority = '' ) {
		if ( isset( $control[ $index ] ) ) {
			$wp_customize->add_setting(
				"loginpress_customization[{$control[$index]}]",
				array(
					'sanitize_callback' => 'sanitize_text_field',
				)
			);

			$wp_customize->add_control(
				new LoginPress_Misc_Control(
					$wp_customize,
					"loginpress_customization[{$control[$index]}]",
					array(
						'section'  => $section,
						'type'     => 'hr',
						'priority' => $priority,
					)
				)
			);
		}
	}



	/**
	 * Manage the Login Footer Links.
	 *
	 * @return void
	 * @since   1.0.0
	 * @version 3.0.0
	 */
	public function login_page_custom_footer() {
		/**
		 * Add brand position class.
		 *
		 * @since   1.1.3
		 * @version 3.0.0
		 */
		$position = ''; // Empty variable for storing position class.
		if ( is_array( $this->loginpress_key ) && isset( $this->loginpress_key['show_love_position'] ) && 'left' === $this->loginpress_key['show_love_position'] ) {
			$position = ' love-position';
		}

		/**
		 * Add functionality of disabling the templates of LoginPress.
		 *
		 * @since 1.6.4
		 */
		$disable_default_style = (bool) apply_filters( 'loginpress_disable_default_style', false );

		if ( 'default1' === $this->loginpress_preset && $disable_default_style ) {
			require_once LOGINPRESS_DIR_PATH . 'include/login-footer.php';
		}

		$show_love = true;
		if ( is_array( $this->loginpress_key ) && isset( $this->loginpress_key['loginpress_show_love'] ) && 1 != $this->loginpress_key['loginpress_show_love'] ) { // phpcs:ignore
			$show_love = false;
		}
		if ( empty( $this->loginpress_key ) || $show_love ) {
			echo '<div class="loginpress-show-love' . esc_attr( $position ) . '">' . esc_html__( 'Powered by:', 'loginpress' ) . ' <a href="https://wpbrigade.com" target="_blank" rel="noopener noreferrer">' . esc_html__( 'LoginPress', 'loginpress' ) . '</a></div>';
		}

		echo '<div class="footer-wrapper">';
		echo '<div class="footer-cont">';

		if ( $this->loginpress_key ) {
			do_action( 'loginpress_footer_menu' );

			if ( isset( $this->loginpress_key['login_copy_right_display'] ) && true === $this->loginpress_key['login_copy_right_display'] ) {
				/**
				 * Replace the "$YEAR$" with current year if and where found.
				 *
				 * @since 1.5.4
				 */
				if ( isset( $this->loginpress_key['login_footer_copy_right'] ) && ! empty( $this->loginpress_key['login_footer_copy_right'] ) && strpos( $this->loginpress_key['login_footer_copy_right'], '$YEAR$' ) !== false ) {
					$year = gmdate( 'Y' );
					// Setting the value with current year and saving in the 'login_footer_copy_right' key.
					$this->loginpress_key['login_footer_copy_right'] = str_replace( '$YEAR$', $year, $this->loginpress_key['login_footer_copy_right'] );
				}

				// Show a default value if not changed or show the changed text string for 'login_footer_copy_right'.
				$footer_text = ( array_key_exists( 'login_footer_copy_right', $this->loginpress_key ) && ! empty( $this->loginpress_key['login_footer_copy_right'] ) ) ? $this->loginpress_key['login_footer_copy_right'] : sprintf(
					// translators: Rights Reserved.
					esc_html__( '© %1$s %2$s, All Rights Reserved.', 'loginpress' ),
					gmdate( 'Y' ),
					get_bloginfo( 'name' )
				);

				echo '<div class="copyRight">' . wp_kses_post( apply_filters( 'loginpress_footer_copyright', $footer_text ) ) . '</div>';
			}
		}
		echo '</div></div>';

		/**
		 * Include LoginPress script in footer.
		 *
		 * @since 1.2.2
		 */
		require_once LOGINPRESS_DIR_PATH . 'js/script-login.php';
	}

	/**
	 * Manage the Login Head
	 *
	 * @return void
	 * @since 1.0.0
	 * @version 6.0.0
	 */
	public function login_page_custom_head() {
		add_filter( 'gettext', array( $this, 'change_lostpassword_message' ), 20, 3 );
		add_filter( 'gettext', array( $this, 'change_username_label' ), 20, 3 );
		// Include CSS File in header.
		$has_custom_js = false;
		if ( is_array( $this->loginpress_key ) && isset( $this->loginpress_key['loginpress_custom_js'] ) && ! empty( $this->loginpress_key['loginpress_custom_js'] ) ) {
			$has_custom_js = true;
		}
		if ( $has_custom_js ) { // 1.2.2
			wp_enqueue_script( 'jquery' );
		}

		/**
		 * Add functionality of disabling the templates of LoginPress.
		 *
		 * @since 1.6.4
		 */
		$disable_default_style = (bool) apply_filters( 'loginpress_disable_default_style', false );

		if ( ! $disable_default_style || 'default1' !== $this->loginpress_preset ) {
			require_once LOGINPRESS_DIR_PATH . 'css/style-presets.php';
			require_once LOGINPRESS_DIR_PATH . 'css/style-login.php';
		}

		do_action( 'loginpress_header_menu' );

		/**
		 * Filter for changing the lost password URL of lifter LMS plugin to default Lost Password URL of WordPress.
		 * By using this filter, you can prevent the redirection of lost password to Lifter LMS's lost password page over lost password link.
		 *
		 * @param bool $value
		 *
		 * @since 1.5.3
		 */
		if ( apply_filters( 'loginpress_llms_lostpassword_url', false ) ) {
			remove_filter( 'lostpassword_url', 'llms_lostpassword_url', 10 );
		}

		if ( ! has_site_icon() && ! is_customize_preview() ) {
			$login_favicon = 'off';
			if ( is_array( $this->loginpress_key ) && isset( $this->loginpress_key['login_favicon'] ) ) {
				$login_favicon = $this->loginpress_key['login_favicon'];
			}

			if ( 'off' !== $login_favicon && function_exists( 'login_header' ) ) {
				echo '<link rel="shortcut icon" href="' . esc_url( $login_favicon ) . '" />';
			}
		}
	}

	/**
	 * Redirecting the WooCommerce lost password url to default WP lost password url.
	 *
	 * @since 3.0.8
	 * @return void
	 */
	public function loginpress_wc_login_page_url_redirection() {
		$loginpress_setting = get_option( 'loginpress_setting' );
		$lostpassword_url   = isset( $loginpress_setting['lostpassword_url'] ) ? $loginpress_setting['lostpassword_url'] : 'off';

		if ( 'on' === $lostpassword_url ) {
			remove_filter( 'lostpassword_url', 'wc_lostpassword_url', 10 );
		}
	}

	/**
	 * Filters the Languages select input activation on the login screen.
	 *
	 * @since 1.5.11
	 * @version 6.0.0
	 * @return bool
	 */
	public function loginpress_language_switch() { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Parameter required by WordPress filter.
		$language_switcher = isset( $this->loginpress_settings['enable_language_switcher'] ) ? $this->loginpress_settings['enable_language_switcher'] : 'off';

		if ( 'off' === $language_switcher ) {
			return true;
		} else {
			return false;
		}
	}
}
