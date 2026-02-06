<?php // phpcs:ignore

/**
 * LoginPress Custom JS Script
 *
 * This file handles custom JavaScript injection and functionality for the LoginPress login page.
 *
 * @package LoginPress
 * @since 1.2.2
 * @version 1.2.3
 */

/**
 * Script-login.php is created for adding JS code in login page footer.
 *
 * @param string $loginpress_key The key to retrieve from loginpress_customization option.
 * @since 1.2.2
 * @version 1.2.3
 * @return string|null The custom JS code if found, null otherwise.
 */
function loginpress_custom_js( $loginpress_key ) {

	$loginpress_array = (array) get_option( 'loginpress_customization' );
	if ( array_key_exists( $loginpress_key, $loginpress_array ) ) {

		if ( 'loginpress_custom_js' === $loginpress_key ) {
			return $loginpress_array[ $loginpress_key ];
		}
	}
}

$loginpress_setting   = get_option( 'loginpress_setting' );
$loginpress_autorm    = isset( $loginpress_setting['auto_remember_me'] ) ? $loginpress_setting['auto_remember_me'] : 'off';
$loginpress_capslock  = __( 'Caps Lock is on', 'loginpress' );
$loginpress_custom_js = loginpress_custom_js( 'loginpress_custom_js' );

if ( ! empty( $loginpress_custom_js ) ) : ?>
	<?php
	// WordPress 5.7+ function that properly handles script tag escaping.
	if ( function_exists( 'wp_get_inline_script_tag' ) ) {
		echo wp_get_inline_script_tag( $loginpress_custom_js ); // phpcs:ignore
	} else {
		?>
		<script>
		<?php
			// Remove HTML tags to prevent XSS while preserving JavaScript syntax.
			echo wp_strip_all_tags( $loginpress_custom_js ); // phpcs:ignore
		?>
		</script>
		<?php
	}
	?>
<?php endif; ?>
<script>
	document.addEventListener( 'DOMContentLoaded', function() {

		if (navigator.userAgent.indexOf("Firefox") != -1) {
			var body = document.body;
			body.classList.add("firefox");
		}
		// your code goes here
		if ( document.getElementById('user_pass') ) {

			var loginpress_user_pass = document.getElementById('user_pass');
			var loginpress_wrapper   = document.createElement('div');
			loginpress_wrapper.classList.add('user-pass-fields');
			// insert wrapper before el in the DOM tree
			user_pass.parentNode.insertBefore(loginpress_wrapper, loginpress_user_pass);

			// move el into wrapper
			loginpress_wrapper.appendChild(loginpress_user_pass);
		var loginpress_user_ps  = document.getElementsByClassName('user-pass-fields');
		var loginpress_node     = document.createElement("div");
		loginpress_node.classList.add('loginpress-caps-lock');
		var loginpress_textnode = document.createTextNode('<?php echo esc_js( $loginpress_capslock ); ?>');
		loginpress_node.appendChild(loginpress_textnode);
			loginpress_user_ps[0].appendChild(loginpress_node);
		}

	}, false );
	window.onload = function(e) {

		var capsLock      = 'off';
		var passwordField = document.getElementById("user_pass");
		if ( passwordField ) {
			
			passwordField.onkeydown = function(e) {
				var el   = this;
				var caps = event.getModifierState && event.getModifierState( 'CapsLock' );
				if ( caps ) {
					capsLock = 'on';
					el.nextElementSibling.style.display = "block";
				} else {
					capsLock = 'off';
					el.nextElementSibling.style.display = "none";
				}
			};

			passwordField.onblur = function(e) {
				var el = this;
				el.nextElementSibling.style.display = "none";
			};

			passwordField.onfocus = function(e) {
				var el = this;
				if ( capsLock == 'on' ) {
					el.nextElementSibling.style.display = "block";
				} else {
					el.nextElementSibling.style.display = "none";
				}
			};
		}

		// if ( document.getElementById("loginform") ) {
		//   document.getElementById("loginform").addEventListener( "submit", _LoginPressFormSubmitLoader );
		// }
		// if ( document.getElementById("registerform") ) {
		//   document.getElementById("registerform").addEventListener( "submit", _LoginPressFormSubmitLoader );
		// }
		// if ( document.getElementById("lostpasswordform") ) {
		//   document.getElementById("lostpasswordform").addEventListener( "submit", _LoginPressFormSubmitLoader );
		// }

		function _LoginPressFormSubmitLoader() {

		var subButton = document.getElementsByClassName("submit");
		var myButton  = document.getElementById("wp-submit");
		var image     = document.createElement("img");

		myButton.setAttribute('disabled', 'disabled');
		image.setAttribute( "src", "<?php echo esc_url( admin_url( 'images/loading.gif' ) ); ?>" );
		image.setAttribute( "width", "20" );
			image.setAttribute( "height", "20" );
			image.setAttribute( "alt", "Login Loader" );
			image.setAttribute( "style", "display: block;margin: 0 auto;position: absolute;top: 50%;left: 50%;transform: translate(-50%, -50%);" );
			subButton[0].appendChild(image);
		}
		if(jQuery('.login-container').length==0){
			jQuery('.language-switcher').appendTo(jQuery('#login'));
		}else{
			jQuery('.language-switcher').appendTo(jQuery('.login-container'));
		}
	};

	<?php if ( 'off' !== $loginpress_autorm ) { ?>
		var _LoginPressRMChecked = document.getElementById("rememberme");
		if ( null != _LoginPressRMChecked ) {
			_LoginPressRMChecked.checked = true;
		}
	<?php } ?>
</script>
