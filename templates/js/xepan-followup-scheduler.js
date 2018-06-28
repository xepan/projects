$.each({

	showFollowupCalendar: function(obj,events_passed, employee_list, employee_field_to_set, startingdate_field_to_set,form){
		
		var form_html = "<div class='row'><div class='col-md-4 col-lg-4 col-sm-12'><select id='xepan-schedule-to-employee'><option value='0'>Please Select</option>";
  		$.each(employee_list,function(id,name){
  			form_html += '<option value="'+id+'">'+name+'</option>';
  		});

  		form_html += "</select></div>";
  		form_html += '<div class="col-md-4 col-lg-4 col-sm-12"><input id="xepan-schedule-datetime"></div>';
  		form_html += '<div class="col-md-4 col-lg-4 col-sm-12"><button id="xepan-schedule-submit-btn" class="btn btn-primary">Update</button></div></div>';

  		$(form_html).appendTo($(form));

	   	$('#xepan-schedule-datetime').appendDtpicker({
			'minuteInterval':15,
			'closeOnSelected': true,
			'dateFormat':'YYYY-MM-DD hh:mm:00',
			'autodateOnStart':false
    	});

	   	$('#xepan-schedule-submit-btn').click(function(){
	   		$(startingdate_field_to_set).val($('#xepan-schedule-datetime').val());
	   		$(employee_field_to_set).val($('#xepan-schedule-to-employee').val());
	   		$(this).closest('.dialog').dialog('close');
	   	});

  		// console.log(obj);
		$(obj).fullCalendar({
			header: { center: 'month,agendaWeek' },
			events: events_passed,
			viewRender: function (view,element){
				// console.log(view.intervalStart.format('YYYY-MM-DD'));
				// console.log(view);
			},
			dayClick: function(date) {
				// add a form with time field and employee list
				// on ok set 
				// $form = '<form id="employee_select_field"></form>';
				// OR =====
				// var dlg=this.dialogBox($.extend({title: title, width: 450, height: 200},options));
		  //       dlg.html("<form></form>"+text);
		  //       dlg.find('form').submit(function(ev){ ev.preventDefault(); if(fn)fn(); dlg.dialog('close'); });
		  //       dlg.dialog('open');

		  // 		var emp_select = "<select id='xepan-schedule-to-employee'>";
		  // 		$.each(employee_list,function(id,name){
		  // 			emp_select += '<option value="'+id+'">'+name+'</option>';
		  // 		});
		  // 		emp_select += "</select>";
		  // 		emp_select += '<input id="xepan-schedule-datetime">';

				// $.univ().dialogOK('Select Employee',emp_select,null,{
				// 	buttons: {
		  //               'Ok': function(){
		                	
		  //               }
		  //           }
				// });
				// date.format('YYYY-MM-DD HH:II:SS'
			    $('#xepan-schedule-datetime').handleDtpicker('setDate',date.format('YYYY-MM-DD HH:mm:00'));
			  }
		});
	}

}, $.univ._import);