$.each({

	showFollowupCalendar: function(obj,events_passed, employee_list, employee_field_to_set, startingdate_field_to_set,form, defaultView){
		
		if(employee_field_to_set !== null){

			var form_html = "<div class='row'><div class='col-md-4 col-lg-4 col-sm-12'><select id='xepan-schedule-to-employee'><option value='0'>Please Select</option>";
	  		$.each(employee_list,function(id,name){
	  			form_html += '<option value="'+id+'">'+name+'</option>';
	  		});

	  		form_html += "</select></div>";
	  		form_html += '<div class="col-md-4 col-lg-4 col-sm-12"><input id="xepan-schedule-datetime"></div>';
	  		form_html += '<div class="col-md-4 col-lg-4 col-sm-12"><button id="xepan-schedule-submit-btn" class="btn btn-primary">Update</button></div></div>';

	  		$(form_html).appendTo($(form));

	  		$('#xepan-schedule-to-employee').select2();

		   	$('#xepan-schedule-datetime').appendDtpicker({
				'minuteInterval':15,
				'closeOnSelected': true,
				'dateFormat':'YYYY-MM-DD HH:mm:00',
				'autodateOnStart':false
	    	});

		   	$('#xepan-schedule-submit-btn').click(function(){
		   		if($('#xepan-schedule-datetime').val()=='') {
		   			alert('Please select time');
		   			return;
		   		}

		   		if($('#xepan-schedule-to-employee').val()=='0') {
		   			alert('Please select employee');
		   			return;
		   		}
		   		$(startingdate_field_to_set).val($('#xepan-schedule-datetime').val());
		   		$(employee_field_to_set).val($('#xepan-schedule-to-employee').val());
		   		$(employee_field_to_set).trigger('change');
		   		$(this).closest('.dialog').dialog('close');
		   	});
		}


  		// console.log(obj);
		$(obj).fullCalendar({
			header: { center: 'month,agendaWeek' },
			events: events_passed,
			defaultView: defaultView,
			viewRender: function (view,element){
				// console.log(view.intervalStart.format('YYYY-MM-DD'));
				// console.log(view);
			},
			eventRender: function(event, element) {
	        	$(element).tooltip({title: event.desc, html:true});
	        },
			dayClick: function(date) {
			    $('#xepan-schedule-datetime').handleDtpicker('setDate',date.format('YYYY-MM-DD HH:mm:00'));
			  }
		});
	}

}, $.univ._import);