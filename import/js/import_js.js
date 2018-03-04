/*
  Plugin Name: Import Zoopla UK Property Listings
  Plugin URI: http://www.alanwheeler.co.uk
  Description: Import listings from Zoopla.
  Author: Alan Wheeler
  Version: 1.00
  Author URI: http://www.alanwheeler.co.uk
  Text Domain: import-listings-zoopla
  Domain Path: lang
*/

jQuery(window).load(function(){

	jQuery( '#run_import' ).click(function(){
		// is the api key field filled in
		if( api_key == '' ) {
			alert( 'Please enter the Zoopla Api Key and click \'Save Changes\'' )
		}
		else if( !jQuery( '#import_option_api_key' ).val() ) {
			alert( 'Please enter the Zoopla Api Key and click \'Save Changes\'' )
		}else{
			jQuery( '#response' ).hide();
			jQuery( '#save_changes' ).addClass( 'disabled' ).attr( 'disabled', 'disabled' );
			jQuery( '#run_import' ).addClass( 'disabled' ).text( 'Import Running' ).attr( 'disabled', 'disabled' );
			console.log( 'We will run the import now' );

			var data = { 'action' : 'handle_ajax' }

			jQuery.post( ajaxurl, data, function(response) {

				console.log(response);

				jQuery ('#response' ).html(response).show();
				jQuery( '#save_changes' ).removeClass( 'disabled' ).attr( 'disabled', false );
				jQuery( '#run_import' ).removeClass( 'disabled' ).text( 'Run Import' ).attr( 'disabled', false );

			});


		}
	});
	
});