document.getElementById("wdfyfirsttab").focus();
(function($) {
	
	$(document).on( 'click', '.nav-tab-wrapper a', function() {
		$('section').hide();
		$('section').eq($(this).index()).show();		
		return false;
	})
	
})( jQuery );

(function($) {
	
	$(document).on( 'focus', '#wdfy_wodp_empty', function() {
		
		$( "#wdfy_wodp_empty" ).clone(true,true).appendTo( "#wdfy_wpubtable" );
		this.id ="";
		return false;
	})
	
})( jQuery );

(function($) {
	
	$(document).on( 'focus', '#wdfy_apip_empty', function() {
		
		$( "#wdfy_apip_empty" ).clone(true,true).appendTo( "#wdfy_apitable" );
		this.id ="";
		return false;
	})
	
})( jQuery );
