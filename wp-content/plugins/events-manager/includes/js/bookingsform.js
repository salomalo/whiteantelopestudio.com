$('#em-booking-form').submit( function(e){
	e.preventDefault();
	var em_booking_doing_ajax = false;
	$.ajax({
		url: EM.bookingajaxurl,
		data:$('#em-booking-form').serializeArray(),
		dataType: 'jsonp',
		type:'post',
		beforeSend: function(formData, jqForm, options) {
			if(em_booking_doing_ajax){
				alert(EM.bookingInProgress);
				return false;
			}
			em_booking_doing_ajax = true;
			$('.em-booking-message').remove();
			$('#em-booking').append('<div id="em-loading"></div>');
		},
		success : function(response, statusText, xhr, $form) {
			$('#em-loading').remove();
			$('.em-booking-message').remove();
			$('.em-booking-message').remove();
			//show error or success message
			if(response.result){
				$('<div class="em-booking-message-success em-booking-message">'+response.message+'</div>').insertBefore('#em-booking-form');
				$('#em-booking-form').hide();
				$('.em-booking-login').hide();
			}else{
				if( response.errors != null ){
					if( $.isArray(response.errors) && response.errors.length > 0 ){
						var error_msg;
						response.errors.each(function(i, el){ 
							error_msg = error_msg + el;
						});
						$('<div class="em-booking-message-error em-booking-message">'+error_msg.errors+'</div>').insertBefore('#em-booking-form');
					}else{
						$('<div class="em-booking-message-error em-booking-message">'+response.errors+'</div>').insertBefore('#em-booking-form');							
					}
				}else{
					$('<div class="em-booking-message-error em-booking-message">'+response.message+'</div>').insertBefore('#em-booking-form');
				}
			}
			//run extra actions after showing the message here
			if( response.gateway != null ){
				$(document).trigger('em_booking_gateway_add_'+response.gateway, [response]);
			}
			if( !response.result && typeof Recaptcha != 'undefined'){
				Recaptcha.reload();
			}
		},
		complete : function(){
			em_booking_doing_ajax = false;
			$('#em-loading').remove();
		}
	});
	return false;	
});