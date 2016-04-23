$.each({
	
	getDateEvents : function(field){
		events_list = $('#calendar').fullCalendar('clientEvents');
		event_data = [];

		$.each(events_list,function(index,value){
			event_data.push({task:value.task_name,date:value.created_at});
		});
		$(field).val(JSON.stringify(event_data));
	},

	taskDate: function(schedule_task){

		element = this.jquery;
		var date = new Date();
		var d = date.getDate();
		var m = date.getMonth();
		var y = date.getFullYear();

		options={
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,agendaWeek,agendaDay'
			},
			isRTL: $('body').hasClass('rtl'), //rtl support for calendar
			selectable: true,
			selectHelper: true,
			select: function(start, end, allDay) {
				calendar.fullCalendar('unselect');
			},
			events: schedule_task,
		};

		$(element).fullCalendar(options);
		// event_trash = $($element).children('.fc-toolbar').children('.fc-left').children('.fc-button-group')
		// .append('<button id="calendarTrash" class="btn btn-danger" type="button"><span class="fa fa-trash-o"></span></button>');
		
	},

	schedularDays: function(){

	}
}, $.univ._import);