<?php
/**
 * File: class-loginpress-color-picker-alpha.php
 *
 * @package LoginPress
 * @since 6.0.0
 */

/**
 * LoginPress Color Picker Alpha Control Class.
 *
 * Custom Color Picker Control with Alpha Channel Support.
 *
 * Extends WordPress core color control to add transparency/alpha.
 * channel support. Compatible with hex, rgba, and hsla color formats.
 *
 * @since 6.0.0
 * @access public
 */
class LoginPress_Color_Picker_Alpha extends WP_Customize_Color_Control {

	/**
	 * The control type.
	 *
	 * @since 6.0.0
	 * @var string
	 */
	public $type = 'alpha_color';

	/**
	 * Enqueue scripts/styles for the color picker.
	 *
	 * @since 6.0.0
	 * @uses WP_Customize_Color_Control::enqueue()
	 * @return void
	 */
	public function enqueue() {
		parent::enqueue();

		wp_enqueue_script( 'loginpress-color-picker-alpha', ( defined( 'LOGINPRESS_DIR_URL' ) ? LOGINPRESS_DIR_URL : '' ) . 'classes/customizer/js/controls/loginpress-color-picker-alpha.js', array( 'customize-controls', 'jquery' ), defined( 'LOGINPRESS_VERSION' ) ? LOGINPRESS_VERSION : '1.0.0', true );
	}

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 *
	 * @since 6.0.0
	 * @uses WP_Customize_Color_Control::to_json()
	 * @return void
	 */
	public function to_json() {
		parent::to_json();

		// Create the json alpha option using the input attrs.
		ob_start();
		$this->input_attrs();
		$data = ob_get_clean();

		$this->json['alpha'] = $data;
	}

	/**
	 * Render a JS template for the content of the color picker control.
	 *
	 * @since 6.0.0
	 * @return void
	 */
	public function content_template() {
		?>
		<# var inputDataAttr = '', isHueSlider = data.mode === 'hue';
		if ( isHueSlider ) {
			inputDataAttr = 'data-type="hue"';
			if ( data.defaultValue && _.isString( data.defaultValue ) ) {
				inputDataAttr += ' data-default-color="' + data.defaultValue + '"';
			}
		} else if ( data.alpha && _.isString( data.alpha ) ) {
			inputDataAttr = data.alpha;
		} #>
		<# if ( data.label ) { #>
			<span class="customize-control-title">{{{ data.label }}}</span>
		<# } #>
		<# if ( data.description ) { #>
			<span class="description customize-control-description">{{{ data.description }}}</span>
		<# } #>
		<div class="customize-control-content">
			<label><span class="screen-reader-text">{{{ data.label }}}</span>
				<input class="color-picker-customize" type="text" {{{ inputDataAttr }}} />
			</label>
		</div>
		<?php
	}
}
