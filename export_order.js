jQuery(document).ready(function() {

	jQuery('.wpg-datepicker').datepicker({
			dateFormat : 'yy-mm-dd'
		}
	);

	/**
	 * Export csv ajax
	 */
	jQuery('.wpg-order-export').on('click', function(e){

		e.preventDefault();
		var data = jQuery(this).parents('form').serialize();

		jQuery.post( ajaxurl, data, function(response){

			response = jQuery.parseJSON(response);
			if( response.error === false ) {
				window.location = window.location.href+'&filename='+response.msg+'&oe=1';
			}else{
				jQuery('.wpg-response-msg').html( response.msg ).addClass('wpg-error');
			}

		});
	});

	/**
	 * Advanced options
	 */
	jQuery('#woo-soe-advanced').on('click', function(e){
		e.preventDefault();
		jQuery('.woo-soe-advanced').slideToggle();
	});
	
});