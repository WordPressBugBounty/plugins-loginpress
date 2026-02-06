<?php
/**
 * File: class-loginpress-misc-control.php
 *
 * @package LoginPress
 * @since 1.0.23
 */

/**
 * LoginPress Miscellaneous Control Class.
 *
 * Class for Miscellaneous Control.
 *
 * @since 1.0.23
 * @version 3.0.0
 * @access public
 */
class LoginPress_Misc_Control extends WP_Customize_Control {

	/**
	 * The type of customize control being rendered.
	 *
	 * @since  1.0.23
	 * @access public
	 * @var    string
	 */
	public $type = '';

	/**
	 * Enqueue scripts/styles.
	 *
	 * @since 1.0.23
	 * @access public
	 * @return void
	 */
	public function enqueue() {
	}

	/**
	 * Displays the control content.
	 *
	 * @since  1.0.23
	 * @access public
	 * @return void
	 */
	public function render_content() {

		switch ( $this->type ) {
			default:
			case 'hr':
				echo '<hr />';
				break;
		}
	}
}
