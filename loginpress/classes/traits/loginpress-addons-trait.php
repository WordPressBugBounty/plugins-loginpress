<?php
/**
 * LoginPress Addons Trait
 *
 * Contains method to generate the markup of addons.
 * Method originally defined in `class-loginpress-addons.php` to keep the main file slim.
 *
 * @package   LoginPress
 * @subpackage Traits
 * @since     6.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'LoginPress_Addons_Trait' ) ) {
	/**
	 * LoginPress Addons Trait
	 *
	 * Handles addons display and functionality.
	 *
	 * @package   LoginPress
	 * @subpackage Traits
	 * @since     6.1.0
	 */
	trait LoginPress_Addons_Trait {
		/**
		 * Generate the markup for addons.
		 *
		 * @since 1.0.19
		 * @version 3.0.5
		 * @return void HTML of addons management interface with cards and controls
		 */
		public function addon_html() {

			?>
			<!-- Style for Add-ons Page -->
			<style media="screen">
				.loginpress_page_loginpress-addons #wpcontent .loginpress-addons-wrap{
					padding: 0px 20px 0 0;
					max-width: 1370px;
					width: 100%;
					margin: 0 auto;
					box-sizing: border-box;
				}
				.loginpress_page_loginpress-addons{
					background-color: #F6F9FF;
				}
				.loginpress-extension p:empty {
					display: none;
				}
					#wpbody-content .loginpress-extension .button-primary{
					border:0;
					text-shadow:none;
					background: #516885;
					padding: 12px 18px;
					height:auto;
					font-size:15px;
					cursor: pointer;
					position: absolute;
					bottom: 20px;
					left: 50%;
					transform: translateX(-50%);
					box-shadow:none;
					border-radius:5px;
					transition: background-color .3s;
					font-size: 16px;
					line-height: 24px;
					color: #fff;
					font-family: "Poppins", sans-serif;
					font-weight: 500;
					text-decoration: none;
					}
				#wpbody-content .loginpress-extension .button-primary:active,
				#wpbody-content .loginpress-extension .button-primary:hover,
				#wpbody-content .loginpress-extension .button-primary:focus{
					background: #2B3D54;
					box-shadow: none;
					outline: none;
					}
				.notice_msg{
					box-shadow: rgba(0, 0, 0, 0.1) 0px 1px 1px 0px;
					background: rgb(255, 255, 255);
					border-left: 4px solid #46b450;
					margin: 5px 0 20px;
					padding: 15px;
				}
				.loginpress-extension button.button-primary{
					background: #f9fafa;
					border-radius: 0;
					box-shadow: none;
					color: #444;
					position: absolute;
					bottom: 15px;
					left: 50%;
					transform: translateX(-50%);
					border: 2px solid #a5dff6 !important;
					background: #d3f3ff54 !important;
					cursor: default;
					transition: background-color .3s;
				}
				.loginpress-extension button.button-primary:visited,
				.loginpress-extension button.button-primary:active,
				.loginpress-extension button.button-primary:hover,
				.loginpress-extension button.button-primary:focus{
					background: #36bcf2;
					color: #444;
					border: 0;
					outline: none;
					box-shadow: none;
				}
				.logoinpress_addons_thumbnails{
					max-width: 100px;
					position: absolute;
					top: 5px;
					inset-inline-start: 10px;
					height: auto;
					width: auto;
					max-height: 75px;
					position: static;
					vertical-align: middle;
					margin-inline-end: 20px;
					margin-top: 0;
				}
				.loginpress-extension p {
					margin: 0;
					padding: 10px 20px;
					color: #5C7697;
					font-size: 13px;
					font-family: "Poppins", sans-serif;
				}
				.loginpress-addons-loading-errors {
					padding-top: 15px;
				}
				.loginpress-addons-loading-errors img {
					float: left;
					padding-right: 10px;
				}
				.loginpress-free-add-ons h3:after{
					content: "Free";
					position: absolute;
					top: 10px;
					right: -30px;
					width: 100px;
					height: 30px;
					background-color: #7FC22B;
					color: #fff;
					transform: rotate(45deg);
					line-height: 30px;
					text-align: center;
					font-size: 13px;
				}

				.loginpress-extension .logoinpress_addons_links{
					position: relative;
					background-color: #DEE5F2;
					text-decoration: none !important;
					display: inline-block;
					width: 100%;
					line-height: 90px;
					padding-bottom: 0px;
					height: auto;
				}

				@media only screen and (min-width: 1700px) {
					.loginpress-extension{
						width: calc(25% - 30px);
					}
				}
				@media only screen and (max-width: 1400px) {
					.loginpress-extension{
						width: calc(50% - 30px);
					}
				}
				@media only screen and (max-width: 670px) {
					.loginpress-extension:nth-child(n){
						width:calc(100% - 15px);
						margin: 0 0 20px;
					}

					.addon_cards_wraper{
						margin: 0;
					}
				}
				.loginpress-addon-enable{
					position: absolute;
					top: -2px;;
					left: -2px;
					bottom: -2px;
					right: -2px;
					background: #fff;
					z-index: 100;
				}
				.loginpress-logo-container{
					position: absolute;
					top: 50%;
					left: 50%;
					transform: translate(-50%, -50%);
					width: 250px;
					height: 250px;
					display: flex;
					flex-direction: column;
					align-items: center;
				}
				.loginpress-logo-container img{
					height: auto;
					position: absolute;
					top: 50%;
					left: 50%;
					transform: translate(-50%, -50%);
					width: 100%;
					max-width: 100px;
				}
				.loginpress-addon-enable p{
					font-weight: 700;
					position: absolute;
					bottom: 0;
					left: 0;
					width: 100%;
					text-align: center;
					box-sizing: border-box;
				}
				.loader-path {
					stroke-dasharray: 150,200;
					stroke-dashoffset: -10;
					-webkit-animation: dash 1.5s ease-in-out infinite, color 6s ease-in-out infinite;
					animation: dash 1.5s ease-in-out infinite, color 6s ease-in-out infinite;
					stroke-linecap: round;
				}
				@-webkit-keyframes rotate {
					100% {
						-webkit-transform: rotate(360deg);
						transform: rotate(360deg);
					}
				}

				@keyframes rotate {
					100% {
						-webkit-transform: rotate(360deg);
						transform: rotate(360deg);
					}
				}
				.circular-loader{
					-webkit-animation: rotate 2s ease-in-out infinite, color 6s ease-in-out infinite;
					animation: rotate 2s ease-in-out infinite, color 6s ease-in-out infinite;
					stroke-linecap: round;
				}
				@keyframes loader-spin {
					0% {
						transform: rotate(0deg);
					}
					100% {
						transform: rotate(360deg);
					}
				}
				@keyframes dash {
					0% {
						stroke-dasharray: 1,200;
						stroke-dashoffset: 0;
					}
					50% {
						stroke-dasharray: 89,200;
						stroke-dashoffset: -35;
					}
					100% {
						stroke-dasharray: 89,200;
						stroke-dashoffset: -124;
					}
				}
				.loginpress-install,.loginpress-uninstall,.loginpress-uninstalling, .loginpress-wrong{
					position: absolute;
					top: -2px;;
					left: -2px;
					bottom: -2px;
					right: -2px;
					background: rgb(255,255,255);
					z-index: 100;
				}
				.loader-path2{
					stroke-dasharray: 150,200;
					stroke-dashoffset: 150px;
					-webkit-animation: dashtwo 1s ease-in-out 1 forwards;
					animation: dashtwo 1s ease-in-out 1 forwards;
				}
				.checkmark__circle {
					stroke-width: 2;
					stroke: #ff0000;
				}
				.checkmark_login {
					width: 150px;
					height: 150px;
					border-radius: 50%;
					display: block;
					stroke-width: 2;
					stroke: #fff;
					stroke-miterlimit: 10;
					margin: 10% auto;
					animation: scale .3s ease-in-out .2s both;
					position: absolute;
					top: 50%;
					left: 50%;
					margin: -75px 0 0 -75px;
				}
				.checkmark__check {
					transform-origin: 50% 50%;
					stroke-dasharray: 29;
					stroke-dashoffset: 29;
					animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.5s forwards;
				}
				@keyframes stroke {
					100% {
						stroke-dashoffset: 0;
					}
				}
				@keyframes scale {
					0%, 100% {
						transform: none;
					}
					50% {
						transform: scale3d(1.1, 1.1, 1);
					}
				}
				@keyframes fill {
					100% {
						box-shadow: inset 0px 0px 0px 30px #7ac142;
					}
				}
				@keyframes dashtwo {
					0% {
						stroke-dashoffset: 150px;
					}
					100% {
						stroke-dashoffset: 20px;
					}
				}
				.circular-loader2, .circular-loader3{
					width: 200px;
					height: 200px;
					top: 50%;
					left: 50%;
					transform: translate(-50%, -50%) rotate(-90deg);
					position: absolute;
				}
				.loginpress-install.activated p{
					position: absolute;
					bottom: 0;
					left: 0;
					text-align: center;
					width: 100%;
					box-sizing: border-box;
				}
				.loginpress-wrong.activated p{
					position: absolute;
					bottom: 0;
					left: 0;
					text-align: center;
					width: 100%;
					box-sizing: border-box;
					color: #ff0000;
					font-weight: 700;
				}
				.checkmark {
					top: 50%;
					position: absolute;
					left: 50%;
					transform: translate(-50%, -50%);
					width: 140px;
					height: 140px;
				}
				.checkmark.draw:after {
					animation-duration: 800ms;
					animation-delay: 1s;
					animation-timing-function: ease;
					animation-name: checkmark;
					transform: scaleX(-1) rotate(135deg);
					opacity: 0;
					animation-fill-mode: forwards;
				}
				.checkmark:after {
					height: 4em;
					width: 2em;
					transform-origin: left top;
					border-right: 2px solid #00c853;
					border-top: 2px solid #00c853;
					content: '';
					left: 42px;
					top: 70px;
					position: absolute;
				}
				.loginpress-uninstall .checkmark:after{
					border-right: 2px solid #ff0000;
					border-top: 2px solid #ff0000;
				}
				.loginpress-uninstall p, .loginpress-uninstalling p{
					position: absolute;
					bottom: 0;
					left: 0;
					text-align: center;
					width: 100%;
					box-sizing: border-box;
				}
				@keyframes checkmark {
					0% {
						height: 0;
						width: 0;
						opacity: 1;
					}
					20% {
						height: 0;
						width: 2em;
						opacity: 1;
					}
					40% {
						height: 4em;
						width: 2em;
						opacity: 1;
					}
					100% {
						height: 4em;
						width: 2em;
						opacity: 1;
					}
				}
				.loginpress-extension input[type="checkbox"]{
					display: none;
				}
				.loginpress-extension .loginpress-radio-btn{
						outline: 0;
					display: block;
					width: 36px;
					height: 18px;
					position: relative;
					cursor: pointer;
					-webkit-user-select: none;
					-moz-user-select: none;
					-ms-user-select: none;
					user-select: none;
				}
				.loginpress-extension input[type=checkbox].loginpress-radio-ios + .loginpress-radio-btn {
					background: #fff;
					border-radius: 2em;
					padding: 2px;
					-webkit-transition: all .4s ease;
					transition: all .4s ease;
					border: 2px solid #D2DDF2;
					position: absolute;
					bottom: 20px;
					left: 50%;
					transform: translateX(-50%);
				}
				.loginpress-extension input[type=checkbox].loginpress-radio + .loginpress-radio-btn:after{
					position: relative;
					display: block;
					content: "";
					width: 18px;
					height: 18px;
				}
				.loginpress-extension input[type=checkbox].loginpress-radio-ios + .loginpress-radio-btn:after {
					border-radius: 2em;
					background: #fbfbfb;
					-webkit-transition: left 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), padding 0.3s ease, margin 0.3s ease;
					transition: left 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), padding 0.3s ease, margin 0.3s ease;
					border: 2px solid #D2DDF2;
					box-sizing: border-box;
					left: 0;
				}
				.loginpress-extension input[type=checkbox].loginpress-radio + .loginpress-radio-btn:hover {
					background-color: #e2e4e7;
				}
				.loginpress-extension input[type=checkbox].loginpress-radio-ios + .loginpress-radio-btn:active:after {
					border-width: 9px;
				}
				.loginpress-extension input[type=checkbox].loginpress-radio:checked + .loginpress-radio-btn:after {
					left: 18px;
					border-color: #fff;
					background: #33b3db;
					border-width: 9px;
				}
				.loginpress-extension input[type=checkbox].loginpress-radio:checked + .loginpress-radio-btn{
					background: #07003B;
					border-color: #07003B;
				}
				</style>

			<div class="wrap loginpress-addons-wrap">
				<h2 class="opt-title"><?php esc_html_e( 'Extend the functionality of LoginPress with these awesome Add-ons', 'loginpress' ); ?></h2>
				<div class="tabwrapper">
					<?php $this->show_addon_page(); ?>
				</div>
			</div>
			<?php
		}
	}
}
