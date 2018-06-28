$.each({

	showFollowupCalendar: function(obj,events_passed, employee_list, employee_field_to_set, startingdate_field_to_set){
		// console.log(events_passed);
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

				$.univ().dialogOK('Select Employee','employee list here',null,{
					buttons: {
		                'Ok': function(){
		                	alert('OKAY');
		                }
		            }
				});
			    alert('a day has been clicked! ' + date.format('YYYY-MM-DD HH:II:SS'));
			  }
		});
	}

}, $.univ._import);