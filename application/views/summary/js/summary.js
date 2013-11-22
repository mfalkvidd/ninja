$(document).ready(function() {
	$('#report_mode_form input').on('change', function() {
		set_report_mode(this.value);
	});
	set_report_mode($('#report_mode_form input:checked').val());

	$("#report_period").bind('change', function() {
		show_calendar($(this).val());
	});
	show_calendar($('#report_period').val());

	$(".to_check").bind('submit', function() {
		loopElements();
		return check_form_values(this.form);
	});

	// reset options and reload page
	$('#new_report').click(function() {
		var base_uri = _site_domain + _index_page + '/' + _current_uri;
		self.location.href = base_uri;
	});

	$('.comments').editable(_site_domain + _index_page + '/alert_history/add_comment', {
		data: function(value) {
			var that = $(this);
			that.addClass('editing-comments').on('blur', 'input', function() {
				that.removeClass('editing-comments');
			});
			return $(value).filter('.content').text()
		},
		submitdata: function(value, settings) {
			$(this).removeClass('editing-comments');
			var eventrow = $(this).parents('.eventrow');
			return {
				timestamp: eventrow.data('timestamp'),
				event_type: eventrow.data('statecode'),
				host_name: eventrow.data('hostname'),
				service_description: eventrow.data('servicename'),
				comment: $('input', this).val() // (today) you cannot use value, because it's the original HTML
			}
		},
		width: 'none'
	});
});

function set_report_mode(type)
{
	switch (type) {
		case 'standard':
			$('.standard').show();
			$('.custom').hide();
			break;
		case 'custom':
			$('.standard').hide();
			$('.custom').show();
			break;
	}
}

function set_initial_state(what, val)
{
	var item = '';
	var elem = false;
	switch (what) {
		case 'obj_type':
			item = 'report_type';
			break;
		case '':
			item = '';
			break;
		default:
			item = what;
	}
	if (item) {
		// don't use name field - use ID!
		if ($('#' + item).is(':visible')) {
			$('#' + item + ' option').each(function() {
				if ($(this).val() == val) {
					$(this).attr('selected', true);
				}
			});
		}
	}
}
