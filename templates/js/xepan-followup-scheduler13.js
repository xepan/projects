$.each({

	showFollowupCalendar: function(obj,events_passed,defaultView, employee_list, add_employee_filter, add_task_types_filter,default_task_type, add_task_sub_types_filter, task_sub_types, employee_field_to_set, startingdate_field_to_set,form,detail_url,follow_type_field_to_set){
					
			var form_html = "<div class='row main-box xepan-followup-schedule' style='padding:5px;margin:0px 0px 5px 0px;'>";
			
			if(add_employee_filter !== false){
				form_html += "<div class='col-md-3 col-lg-3 col-sm-12'><label>Employee</label><select id='xepan-schedule-to-employee'><option value='0'>All Employee</option>";
		  		$.each(employee_list,function(id,name){
		  			form_html += '<option value="'+id+'">'+name+'</option>';
		  		});
	  			form_html += "</select></div>";
		  	}
  			
  			

	  		if(startingdate_field_to_set !==null){
		  		form_html += '<div class="col-md-3 col-lg-3 col-sm-12"><label>Followup On</label><input id="xepan-schedule-datetime"></div>';
	  		}


	  		if(add_task_types_filter !==false){
	  			form_html += '<div class="col-md-2 col-lg-2 col-sm-12"><label>Task Type</label>\
	  						<select id="xepan-schedule-task_type">\
	  						<option value="0">Any Task Type</option>\
	  						<option value="Task" '+((default_task_type == 'Task')?'SELECTED':'')+'>Task</option>\
	  						<option value="Followup" '+((default_task_type == 'Followup')?'SELECTED':'')+'>Followup</option>\
	  						<option value="Reminder" '+((default_task_type == 'Reminder')?'SELECTED':'')+'>Reminder</option>\
	  						</select></div>';
	  		}

	  		if(add_task_sub_types_filter !==false){
	  			form_html += '<div class="col-md-2 col-lg-2 col-sm-12"><label>Task Sub Type</label>';
	  			form_html += '<select id="xepan-schedule-task_sub_type"><option value="0">Any Task Sub Type</option>';
	  			$.each(task_sub_types, function(index, val) {
	  				form_html += '<option value="'+val+'">'+val+'</option>';
	  			});
	  			form_html += '</select>';
	  			form_html +='</div>';
	  		}

	  		if(add_employee_filter !==false && employee_field_to_set !==null){
	  			form_html += '<div class="col-md-2 col-lg-2 col-sm-12"><button id="xepan-schedule-submit-btn" class="btn btn-primary">Update</button></div>';
	  		}
  			form_html += "</div>";

	  		$(form_html).appendTo($(form));

	  		$('#xepan-schedule-task_type').select2().on('change',function(e){
	  			$(obj).fullCalendar('rerenderEvents');
	  		});

	  		$('#xepan-schedule-to-employee').select2().on('change',function(e){
	  			$(obj).fullCalendar('rerenderEvents');
	  		});

	  		$('#xepan-schedule-task_sub_type').select2().on('change',function(e){
	  			$(obj).fullCalendar('rerenderEvents');
	  		});



	  		if(startingdate_field_to_set !==null){
			   	$('#xepan-schedule-datetime').appendDtpicker({
					'minuteInterval':15,
					'closeOnSelected': true,
					'dateFormat':'YYYY-MM-DD HH:mm:00',
					'autodateOnStart':false
		    	});
	  		}

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
		   		$(follow_type_field_to_set).val($('#xepan-schedule-task_sub_type').val());
		   		$(follow_type_field_to_set).trigger('change');

		   		$(this).closest('.dialog').dialog('close');
		   	});

  		// console.log(obj);
		$(obj).fullCalendar({
			header: { center: 'month,agendaWeek,agendaDay' },
			events: events_passed,
			defaultView: defaultView,
			viewRender: function (view,element){
				// console.log(view.intervalStart.format('YYYY-MM-DD'));
				// console.log(view);
			},
			eventRender: function(event, element) {
	        	$(element).popover({title: event.title,content: event.desc, html:true,trigger: 'hover',placement: 'bottom',container: '.xepan-followup-schedule'});
	        	if(event.icon){          
			        element.find(".fc-title").prepend("<i class='fa fa-"+event.icon+"'></i> ");
			    }
	        	to_show=true;
	        	if(add_employee_filter)
	        		to_show = to_show && ['0', event.assign_to_id].indexOf($('#xepan-schedule-to-employee').val()) >= 0
	        	if(add_task_types_filter)
	        		to_show = to_show && ['0', event.type].indexOf($('#xepan-schedule-task_type').val()) >= 0
	        	if(add_task_sub_types_filter)
	        		to_show = to_show && ['0', event.sub_type].indexOf($('#xepan-schedule-task_sub_type').val()) >= 0

	        	return to_show;
	        },
			dayClick: function(date) {
			    $('#xepan-schedule-datetime').handleDtpicker('setDate',date.format('YYYY-MM-DD HH:mm:00'));
			},
			eventClick: function(calEvent, jsEvent, view){
				$.univ().frameURL(calEvent.title,detail_url+'&task_id='+calEvent.task_id);
			}
		});
	}

}, $.univ._import);