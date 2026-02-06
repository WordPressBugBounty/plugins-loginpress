/**
 * This file will work outside of the plugin context
 * @param {jQuery} $ - jQuery object
 * @since 6.1.1
 */
(function ($) {

    // Make admin menu upgrade link open in a new tab
    $( document ).ready( function () {
        $( '.loginpress-sidebar-upgrade-pro a' ).attr( 'target', '_blank' ).attr( 'rel', 'noopener noreferrer' );
    } );
})( jQuery );