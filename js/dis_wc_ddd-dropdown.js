// dropdown datepicker play

 jQuery(function($) {
    $("#wc_bookings_field_resource").change(function() {
        var product_id = $("input[name='add-to-cart']").val();
        $.post(WooBookingsDropdown.ajax_url,{product_id: product_id, action: 'wswp_refresh_dates',security: 	WooBookingsDropdown.secure,resource_id:$(this).val()},function(response) {
            if (response.success) {
	            console.log('this is happening');
                $("#wc_bookings_field_start_date").html('');
                if(repsonse.dates.length >1) {
                $.each(response.dates,function(key,value) {
                    $("#wc_bookings_field_start_date").append("<option value='"+key+"'>"+value+"</option>");
                });
                }
                else {
	                $("#wc-bookings-booking-form").html('<div class="alert danger">There are currently no bookable dates</div>'); 
                }
            }
        })
    })
    
    // if there are no dates, hide the form box
    if ($('#no-dates').length){
         $("#wc-bookings-booking-form").html('<div class="alert alert-danger">There are currently no bookable dates</div>'); 
    }
    
     $(".picker-chooser").insertBefore('.wc-bookings-date-picker-date-fields');
     //need to add unique ID to page with dropdown picker then use to filter here and above****
      		$(".dis-dropdown-class #wc_bookings_field_start_date").prepend("<option value='' selected='selected'>Choose course</option>");
                $("select#wc_bookings_field_start_date").on('change', function() {
	            
				var selectedDate = $(this).val()
				var selectedDateBreakdown = selectedDate.split("-");
				
				$( "input[name*='wc_bookings_field_start_date_year']" ).val( selectedDateBreakdown[0] );
				$( "input[name*='wc_bookings_field_start_date_month']" ).val( selectedDateBreakdown[1] );
				$( "input[name*='wc_bookings_field_start_date_day']" ).val( selectedDateBreakdown[2] );
			});

})
