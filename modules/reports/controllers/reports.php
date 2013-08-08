<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Reports controller
 *
 * This particular reports controller is meant as a base controller for both
 * SLA and Availability reports, mostly for hysterical reasons.
 *
 *  op5, and the op5 logo are trademarks, servicemarks, registered servicemarks
 *  or registered trademarks of op5 AB.
 *  All other trademarks, servicemarks, registered trademarks, and registered
 *  servicemarks mentioned herein may be the property of their respective owner(s).
 *  The information contained herein is provided AS IS with NO WARRANTY OF ANY
 *  KIND, INCLUDING THE WARRANTY OF DESIGN, MERCHANTABILITY, AND FITNESS FOR A
 *  PARTICULAR PURPOSE.
 */
class Reports_Controller extends Base_reports_Controller
{
	private $status_link = "status/host/";
	private $history_link = "alert_history/generate";


	private static $sla_field_names = array(
		'hosts' => 'PERCENT_TOTAL_TIME_UP',
		'hostgroups' => 'PERCENT_TOTAL_TIME_UP',
		'services' => 'PERCENT_TOTAL_TIME_OK',
		'servicegroups' => 'PERCENT_TOTAL_TIME_OK'
	);

	/**
	*	Display report selection/setup page
	*/
	public function index($input=false)
	{
		$this->setup_options_obj($input);
		$reports_model = new Status_Reports_Model($this->options);

		# check if we have all required parts installed
		if (!$reports_model->_self_check()) {
			return url::redirect(Router::$controller.'/invalid_setup');
		}

		# reset current_report_params and main_report_params
		# just to be sure they're not left behind
		Session::instance()->set('current_report_params', null);
		Session::instance()->set('main_report_params', null);

		$old_config_names = Saved_reports_Model::get_all_report_names($this->type);
		$old_config_names_js = empty($old_config_names) ? "false" : "new Array('".implode("', '", array_map('addslashes', $old_config_names))."');";
		$type_str = $this->type == 'avail'
			? _('availability')
			: _('SLA');
		$this->template->content = $this->add_view('reports/setup');
		$template = $this->template->content;

		if(isset($_SESSION['report_err_msg'])) {
			$template->error_msg = $_SESSION['report_err_msg'];
			unset($_SESSION['report_err_msg']);
		}

		# we should set the required js-files
		$this->template->js_header = $this->add_view('js_header');
		$this->xtra_js[] = $this->add_path('reports/js/tgraph.js');
		$this->xtra_js[] = 'application/media/js/jquery.datePicker.js';
		$this->xtra_js[] = 'application/media/js/jquery.timePicker.js';
		$this->xtra_js[] = $this->add_path('reports/js/common.js');
		$this->xtra_js[] = $this->add_path('reports/js/reports.js');

		# this makes anything in application/media be imported before
		# application/views before modules/whatever, so op5reports can
		# put random crap here as well.

		# I apologize
		sort($this->xtra_js);
		$this->xtra_js = array_unique($this->xtra_js);

		$this->template->js_header->js = $this->xtra_js;

		$this->template->css_header = $this->add_view('css_header');
		$this->xtra_css[] = $this->add_path('reports/css/tgraph.css');
		$this->xtra_css[] = $this->add_path('reports/css/datePicker.css');
		$this->template->css_header->css = $this->xtra_css;

		# what scheduled reports are there?
		$scheduled_ids = array();
		$scheduled_periods = null;
		$scheduled_res = Scheduled_reports_Model::get_scheduled_reports($this->type);
		if ($scheduled_res && count($scheduled_res)!=0) {
			foreach ($scheduled_res as $sched_row) {
				$scheduled_ids[] = $sched_row->report_id;
				$scheduled_periods[$sched_row->report_id] = $sched_row->periodname;
			}
		}

		$template->report_options = $this->add_view('reports/options');

		$scheduled_info = false;
		if ($this->options['report_id']) {
			$scheduled_info = Scheduled_reports_Model::report_is_scheduled($this->type, $this->options['report_id']);
			$template->is_scheduled = empty($scheduled_info) ? false: true;
		}
		$template->scheduled_info = $scheduled_info;

		if ($this->options['report_id']) {
			$this->js_strings .= "var _report_data = " . $this->options->as_json() . "\n";
		}

		if($this->options['includesoftstates'])
			$this->inline_js .= "toggle_label_weight(true, 'include_softstates');\n";
		if($this->options['assumestatesduringnotrunning'])
			$this->inline_js .= "toggle_label_weight(true, 'assume_progdown');\n";
		$this->inline_js .= "invalid_report_names = ".$old_config_names_js .";\n";

		$label_avail = _('Availability');
		$label_sla = _('SLA');
		$label_switch_to = _('Switch to');
		$label_report = _('report');

		$this->js_strings .= "var _edit_str = '"._('edit')."';\n";
		$this->js_strings .= "var _hide_str = '"._('hide')."';\n";
		$this->js_strings .= "var _label_avail = '".$label_avail."';\n";
		$this->js_strings .= "var _label_sla = '".$label_sla."';\n";
		$this->js_strings .= "var _label_switch_to = '".$label_switch_to."';\n";
		$this->js_strings .= "var _label_report = '".$label_report."';\n";
		$this->js_strings .= "var nr_of_scheduled_instances = ". (!empty($scheduled_info) ? sizeof($scheduled_info) : 0).";\n";
		$this->js_strings .= "var _reports_propagate = '"._('Would you like to propagate this value to all months')."';\n";
		$this->js_strings .= "var _reports_propagate_remove = '"._("Would you like to remove all values from all months")."';\n";
		$this->js_strings .= "var _schedule_change_filename = \""._('Would you like to change the filename based on your selections?')."\";\n";

		$this->js_strings .= reports::js_strings();

		$this->js_strings .= "var _reports_name_empty = '"._("Please give your report a meaningful name.")."';\n";
		$this->js_strings .= "var _reports_error_name_exists = '"._("You have entered a name for your report that already exists. <br />Please select a new name")."';\n";
		$this->js_strings .= "var _reports_missing_objects = \""._("Some items in your saved report do not exist anymore and have been removed")."\";\n";
		$this->js_strings .= "var _reports_missing_objects_pleaseremove = '"._('Please modify the objects to include in your report below and then save it.')."';\n";

		$this->template->inline_js = $this->inline_js;

		$template->new_saved_title = sprintf(_('Create new saved %s report'), $type_str);
		$template->label_create_new = $this->type == 'avail' ? _('Availability report') : _('SLA report');
		$template->report_options->months = date::abbr_month_names();

		$saved_reports = Saved_reports_Model::get_saved_reports($this->type);
		$template->report_options->saved_reports = $saved_reports;
		$template->saved_reports = $saved_reports;
		$template->scheduled_ids = $scheduled_ids;
		$template->scheduled_periods = $scheduled_periods;

		$this->js_strings .= "var _reports_success = '"._('Success')."';\n";
		$this->js_strings .= "var _reports_error = '"._('Error')."';\n";
		$this->js_strings .= "var _reports_schedule_error = '"._('An error occurred when saving scheduled report')."';\n";
		$this->js_strings .= "var _reports_schedule_send_error = '"._('An error occurred when trying to send the scheduled report')."';\n";
		$this->js_strings .= "var _reports_schedule_update_ok = '"._('Your schedule has been successfully updated')."';\n";
		$this->js_strings .= "var _reports_schedule_send_ok = '"._('Your report was successfully sent')."';\n";
		$this->js_strings .= "var _reports_schedule_create_ok = '"._('Your schedule has been successfully created')."';\n";
		$this->js_strings .= "var _reports_fatal_err_str = '"._('It is not possible to schedule this report since some vital information is missing.')."';\n";

		$this->js_strings .= "var _reports_no_sla_str = '"._('Please enter at least one SLA value')."';\n";
		$this->js_strings .= "var _reports_sla_err_str = '"._('Please check SLA values in fields marked red below and try again')."';\n";

		$this->template->js_strings = $this->js_strings;

		$this->template->title = _('Reporting » ').($this->type == 'avail' ? _('Availability Report') : _('SLA Report')).(' » Setup');
	}

	/**
	 * Generate (availability) report from parameters set in index()
	 *
	 * @param $input array = false
	 */
	public function generate($input=false)
	{
		$this->setup_options_obj($input);

		$reports_model = new Status_Reports_Model($this->options);

		# check if we have all required parts installed
		if (!$reports_model->_self_check()) {
			return url::redirect(Router::$controller.'/invalid_setup');
		}

		$this->_stash_params();

		$this->template->js_header = $this->add_view('js_header');
		$this->xtra_js[] = 'application/media/js/jquery.datePicker.js';
		$this->xtra_js[] = 'application/media/js/jquery.timePicker.js';
		$this->xtra_js[] = $this->add_path('reports/js/tgraph.js');
		$this->xtra_js[] = $this->add_path('reports/js/common.js');
		$this->xtra_js[] = $this->add_path('reports/js/reports.js');

		if ($this->options['skin']) {
			if (substr($this->options['skin'], -1, 1) != '/') {
				$this->options['skin'] .= '/';
			}
			$this->template->current_skin = $this->options['skin'];
		}

		$this->xtra_css[] = $this->add_path('reports/css/datePicker.css');
		$this->xtra_css[] = $this->add_path('reports/css/tgraph.css');
		$this->template->css_header = $this->add_view('css_header');

		$old_config_names = Saved_reports_Model::get_all_report_names($this->type);
		$old_config_names_js = empty($old_config_names) ? "false" : "new Array('".implode("', '", array_map("addslashes", $old_config_names))."');";
		$this->inline_js .= "invalid_report_names = ".$old_config_names_js .";\n";

		$this->template->content = $this->add_view('reports/index'); # base template with placeholders for all parts
		$template = $this->template->content;

		$scheduled_info = Scheduled_reports_Model::report_is_scheduled($this->type, $this->options['report_id']);

		$sub_type = false;

		$date_format = $this->type == 'sla' ? cal::get_calendar_format(true) : nagstat::date_format();
		if($this->options['report_period'] && $this->options['report_period'] != 'custom')
			$report_time_formatted  = sprintf(
				_('%s (%s to %s)'),
				$this->options->get_value('report_period'),
				"<strong>".date($date_format, $this->options['start_time'])."</strong>",
				"<strong>".date($date_format, $this->options['end_time'])."</strong>"
			);
		else
			$report_time_formatted  = sprintf(_("%s to %s"),
				date($date_format, $this->options['start_time']),
				date($date_format, $this->options['end_time']));

		if($this->options['rpttimeperiod'] != '')
			$report_time_formatted .= " - {$this->options['rpttimeperiod']}";

		switch ($this->options['report_type']) {
			case 'hostgroups':
				$sub_type = "host";
				$is_group = true;
				break;
			case 'servicegroups':
				$sub_type = "service";
				$is_group = true;
				break;
			case 'hosts':
				$sub_type = "host";
				$is_group = false;
				break;
			case 'services':
				$sub_type = "service";
				$is_group = false;
				break;
			default:
				return url::redirect(Router::$controller.'/index');
		}
		$var = $this->options->get_value('report_type');

		$report_members = $this->options->get_report_members();
		if (empty($report_members)) {
			$_SESSION['report_err_msg'] = "No objects could be found in your selected groups to base the report on";
			return url::redirect(Router::$controller.'/index');
		}

		# fetch data
		if ($this->type == 'avail') {
			$data_arr = $reports_model->get_uptime();
		} else {
			$data_arr = $this->get_sla_data($this->options['months']);
		}

		if ($this->options['output_format'] == 'csv') {
			csv::csv_http_headers($this->type, $this->options);
			$this->template = $this->add_view('reports/'.$this->type.'csv');
			$this->template->data_arr = $data_arr;
			return;
		}

		$template->title = $this->type == 'avail' ? _('Availability Report') : _('SLA Report');

		# ==========================================
		# ========= REPORT STARTS HERE =============
		# ==========================================

		$template->report_options = $this->add_view('reports/options');

		$tpl_options = $template->report_options;
		$saved_reports = Saved_reports_Model::get_saved_reports($this->type);
		$tpl_options->saved_reports = $saved_reports;
		$tpl_options->months = date::abbr_month_names();

		if ($this->type == 'avail') {
			$this->inline_js .= "set_initial_state('scheduleddowntimeasuptime', '".$this->options['scheduleddowntimeasuptime']."');\n";
			$this->inline_js .= "set_initial_state('report_period', '".$this->options['report_period']."');\n";
			$this->inline_js .= "show_calendar('".$this->options['report_period']."');\n";
		}

		$this->js_strings .= "var _reports_success = '"._('Success')."';\n";
		$this->js_strings .= "var _reports_error = '"._('Error')."';\n";
		$this->js_strings .= "var _reports_schedule_send_ok = '"._('Your report was successfully sent')."';\n";
		$this->js_strings .= "var nr_of_scheduled_instances = ". (!empty($scheduled_info) ? sizeof($scheduled_info) : 0).";\n";
		$this->js_strings .= "var _reports_fatal_err_str = '"._('It is not possible to schedule this report since some vital information is missing.')."';\n";
		$this->js_strings .= "var _reports_schedule_interval_error = '"._(' -Please select a schedule interval')."';\n";
		$this->js_strings .= "var _reports_schedule_recipient_error = '"._(' -Please enter at least one recipient')."';\n";
		$this->js_strings .= "var _edit_str = '"._('edit')."';\n";
		$this->js_strings .= "var _hide_str = '"._('hide')."';\n";
		$this->js_strings .= "var _reports_schedule_error = '"._('An error occurred when saving scheduled report')."';\n";
		$this->js_strings .= "var _reports_schedule_update_ok = '"._('Your schedule has been successfully updated')."';\n";
		$this->js_strings .= "var _reports_schedule_create_ok = '"._('Your schedule has been successfully created')."';\n";
		$this->js_strings .= "var _reports_view_schedule = '"._('View schedule')."';\n";
		$this->js_strings .= "var _reports_errors_found = '"._('Found the following error(s)')."';\n";
		$this->js_strings .= "var _reports_please_correct = '"._('Please correct this and try again')."';\n";
		$this->js_strings .= "var _reports_propagate = '"._('Would you like to propagate this value to all months')."';\n";
		$this->js_strings .= "var _reports_propagate_remove = '"._("Would you like to remove all values from all months")."';\n";

		$this->js_strings .= "var _reports_error_name_exists = '"._("You have entered a name for your report that already exists. <br />Please select a new name")."';\n";
		$this->js_strings .= reports::js_strings();
		$this->js_strings .= "var _reports_name_empty = '"._("Please give your report a meaningful name.")."';\n";
		$this->inline_js .= "set_selection('{$this->options['report_type']}');\n";

		$host_graph_items = array('TOTAL_TIME_UP' => _('Up'),
			'TOTAL_TIME_DOWN' => _('Down'),
			'TOTAL_TIME_UNREACHABLE' => _('Unreachable'),
			'TOTAL_TIME_UNDETERMINED' => _('Undetermined'),
			'TOTAL_TIME_EXCLUDED' => 'EXCLUDE',
		);
		$service_graph_items = array('TOTAL_TIME_OK' => _('Ok'),
			'TOTAL_TIME_WARNING' => _('Warning'),
			'TOTAL_TIME_UNKNOWN' => _('Unknown'),
			'TOTAL_TIME_CRITICAL' => _('Critical'),
			'TOTAL_TIME_UNDETERMINED' => _('Undetermined'),
			'TOTAL_TIME_EXCLUDED' => 'EXCLUDE',
		);
		$graph_filter = ${$sub_type.'_graph_items'};

		$template->header = $this->add_view('reports/header');
		$template->header->title = $template->title;
		$template->header->report_time_formatted = $report_time_formatted;

		# avail, more than one object
		$get_vars = $this->options->as_keyval_string(true);
		if ($this->type == 'avail' && ($is_group || count($this->options[$this->options->get_value('report_type')]) > 1)) {
			$template_values = array();
			$template->content = $this->add_view('reports/multiple_'.$sub_type.'_states');
			$template->content->multiple_states = $data_arr;
			$template->content->hide_host = false;

			if($is_group) # actual hostgroup/servicegroup.
				$tmp_title = ucfirst($sub_type)._('group breakdown');
			else
				$tmp_title = ucfirst($sub_type).' '._('state breakdown');
			$template->header->title = $tmp_title;

			// ===== SETUP PIECHART VALUES =====

			if( $this->options['include_pie_charts'] ) {
				$template->pie = $this->add_view('reports/pie_chart');
				$data_str = array();

				foreach($data_arr as $data) { # for every group
					if (!is_array($data) || !isset($data['states']))
						continue;
					$image = array();
					foreach ($graph_filter as $key => $val) {
						if ($data['states'][$key]!=0)
							$image[strtoupper($val)] = $data['states'][$key];
					}
					$data_str[] = array(
						'img' => http_build_query($image),
						'host' => isset($data['groupname'])?implode(', ', $data['groupname']):'',
					);
				}

				$template->pie->data_str = $data_str;
			}
		} else { # single avail host/service, or any sla
			$image_data = array();
			$data_str = '';
			if (!empty($data_arr)) {
				$template->content = $this->add_view('reports/'.$this->type);

				if ($this->type == 'avail') {
					$sources = array();
					foreach ($data_arr as $grouplist) {
						if (!is_array($grouplist) || !isset($grouplist['states']))
							continue;
						foreach ($grouplist as $host) {
							if (!is_array($host) || !isset($host['states']))
								continue;
							$sources[] = $host['source'];
						}
					}
					$avail = $template->content;
					$avail->report_data = $data_arr;

					$avail->header_string = ucfirst($this->options['report_type'])." "._('state breakdown');

					# Will break epically
					if( $this->options['include_pie_charts'] ) {
						$pies = array();
						foreach ($data_arr as $data) {
							if (!is_array($data) || !isset($data['states']))
								continue;
							$pie = $this->add_view('reports/pie_chart');

							// ===== SETUP PIECHART VALUES =====
							if (is_array($data['states'])) {
								foreach ($graph_filter as $key => $val) {
									if ($data['states'][$key]!=0)
										$image_data[strtoupper($val)] = $data['states'][$key];
								}
							}

							if ($image_data) {
								$data_str = http_build_query($image_data);
								$pie->data_str = $data_str;
								$pie->source = $data['source'];
							}
							$pies[] = $pie;
						}
						$avail->pies = $pies;
					}

					if ($sub_type=='host') {
						$service_states = $this->_print_states_for_services($sources);

						if ($service_states !== false) {
							$header_str = _("Service state breakdown");
							$template->svc_content = $this->add_view('reports/multiple_service_states');
							$content = $template->svc_content;
							$content->header_string = $header_str;
							$content->multiple_states = $service_states;
							$content->hide_host = true;
							$content->source = $sources;
						}
					}

					$t1 = $this->options['start_time'];
					$t2 = $this->options['start_time'];

					# assume default values for the following
					$backtrack = 1;

					$links = array();
					$downtime       = $this->options['scheduleddowntimeasuptime'];
					$not_running    = $this->options['assumestatesduringnotrunning'];
					$soft_states    = $this->options['includesoftstates'];

					# links - only for HTML reports
					switch($this->options['report_type']) {
						case 'hosts':
							$host = $this->options['host_name'][0];
							$template->header->title = sprintf(_('Host details for %s'), $host);
							$histogram_params = "host=$host&amp;t1=$t1&amp;t2=$t2";

							$links[$this->histogram_link . "?" . $histogram_params] = _('Alert histogram');

							$links[$this->status_link.$host] = _('Status detail');

							$links[$this->history_link . '?host_name[]=' . $host] = _('Alert history');
							$links[listview::link('notifications', array('host_name' => $host))] = _('Notifications');
							break;

						case 'services':
							list($host, $service) = explode(';',$this->options['service_description'][0]);

							$template->header->title = sprintf(_('Service details for %s on host %s'), $service, $host);
							if (isset($template->content)) {
								$template->content->host = $host;
								$template->content->service = $service;
							}

							$histogram_params = "service[]=".urlencode("$host;$service")."&amp;t1=$t1&amp;t2=$t2";
							$history_params = "service[]=".urlencode("$host;$service");

							$links[$this->histogram_link . "?" . $histogram_params] = _('Alert histogram');
							$links[$this->history_link . "?" . $history_params] = _('Alert history');
							$links[listview::link('notifications', array('host_name' => $host, 'service_description' => $service))] = _('Notifications');

							break;
					}

					$template->links = $links;
					$template->source = $sources;
					$template->header_string = sprintf(_("State breakdown for %s"), implode(', ', $sources));
				} else {
					# SLA report
					$template->header->title = _('SLA breakdown');
					$sla = $template->content;
					$sla->report_data = $data_arr;
				}

			} # end if not empty. Display message to user?
			  # ^ no, that sounds like a terrible idea, don't inform the user about anything, ever!
		}

		if($this->options['include_trends']) {
			$skipped = 0;
			$included = 0;
			$graph_data = array();
			foreach ($data_arr as $data) {
				if (!is_array($data) || !isset($data['states']))
					continue;
				foreach ($data as $obj) {
					if (!is_array($obj) || !isset($obj['log']))
						continue;
					if (($sub_type == 'host' && $obj['states']['PERCENT_TOTAL_TIME_UP'] == 100) || ($sub_type == 'service' && $obj['states']['PERCENT_TOTAL_TIME_OK'] == 100)) {
						$skipped++;
						continue;
					}
					$graph_data[$obj['source']] = $obj['log'];
					$included++;
				}
			}

			$template->trends_graph = $this->add_view('trends/new_report');

			/* New JS trend graph */

			$template->trends_graph->skipped = $skipped;
			$template->trends_graph->included = $included;
			$template->trends_graph->graph_start_date = $this->options['start_time'];
			$template->trends_graph->graph_end_date = $this->options['end_time'];
			$template->trends_graph->use_scaling = $this->options['include_trends_scaling'];
			$template->trends_graph->obj_type = $sub_type;
			$template->trends_graph->graph_pure_data = Trends_graph_Model::format_graph_data($graph_data);
		}

		$this->template->inline_js = $this->inline_js;
		$this->template->js_strings = $this->js_strings;
		$this->template->css_header->css = $this->xtra_css;

		$this->template->title = _('Reporting » ').($this->type == 'avail' ? _('Availability Report') : _('SLA Report')).(' » Report');

		$this->xtra_js[] = $this->add_path('summary/js/summary.js');
		if ($this->options['include_alerts']) {
			$alrt_opts = new Alert_history_options($this->options);
			$alrt_opts['summary_items'] = 0; // we want *every* line in this time range
			$alrt_opts['include_downtime'] = true; // and we want downtime messages

			$alerts = new Alert_history_Controller();
			$alerts->set_options($alrt_opts);
			$alerts->auto_render = false;
			$alerts->generate();
			$this->template->content->log_content = $alerts->template->content->content;
		}

		if(ninja::has_module('synergy') && $this->options['include_synergy_events']) {
			$synergy_report_model = new Synergy_report_Model;
			$synergy_content = $this->add_view('reports/synergy');
			$synergy_content->synergy_events = $synergy_report_model->get_data($this->options);
			$this->template->content->synergy_content = $synergy_content;
		}
		$this->template->js_header->js = $this->xtra_js;
		$scheduled_info = Scheduled_reports_Model::report_is_scheduled($this->type, $this->options['report_id']);
		if($scheduled_info) {
			$schedule_id = $this->input->get('schedule_id', null);
			if($schedule_id) {
				$le_schedule = current(array_filter($scheduled_info, function($item) use ($schedule_id) {
					return $item['id'] == $schedule_id && $item['attach_description'] && $item['description'];
				}));
				if($le_schedule) {
					$template->header->description = $this->options['description'] ? $this->options['description']."\n".$le_schedule['description'] : $le_schedule['description'];
				}
			}
		}

		if ($this->options['output_format'] == 'pdf') {
			return $this->generate_pdf();
		}
	}

	/**
	*	Stash parameters in session from setup form to be used
	*	for re-generating report.
	*/
	public function _stash_params()
	{
		Session::instance()->set('current_report_params', null);

		if (!empty($data)) {
			if (array_key_exists('ew_report_setup', $input)) {
				# directly from setup form - keep data for backlink
				Session::instance()->set('main_report_params', $this->options->as_keyval_string(false));
			}

			Session::instance()->set('current_report_params', $this->options->as_keyval_string(false));
		}
	}

	/**
	*	Print message to user about invalid setup.
	*	This could be because of missing database or
	* 	reports module
	*/
	public function invalid_setup()
	{
		$this->template->content = $this->add_view('reports/reports_module');
		$template = $this->template->content;
		$template->error_msg  = _('Some parts in your setup is apparently missing.');
		$template->info = _("make sure you install the latest version of merlin");
	}

	private function _print_states_for_services($host_name=false)
	{
		$res = Livestatus::instance()->getServices(array('columns' => array('host_name', 'description'), 'filter' => array('host_name' => $host_name)));
		if (!empty($res)) {
			$service_arr = array();

			$classname = get_class($this->options);
			$opts = new $classname($this->options);
			foreach ($res as $row)
				$service_arr[] = $row['host_name'] . ';' . $row['description'];
			$opts['service_description'] = $service_arr;
			$report_class = new Status_Reports_Model($opts);

			$data_arr = $report_class->get_uptime();
			return $data_arr;
		}
		return false;
	}

	/**
	 * Fetch data from report_class
	 * Uses split_month_data() to split start- and end_time
	 * on months.
	 *
	 * @param $months = false
	 * @return array
	 */
	public function get_sla_data($months)
	{
		if (empty($months)) {
			return false;
		}

		$report_data = false;

		// OK, we have start and end but we will have to split
		// this time into parts according to sla_periods (months)
		$time_arr = $this->_split_month_data($this->options['start_time'], $this->options['end_time']);
		// only use month entered by the user regardless of start- or endtime
		$data = array();
		$opts = new Avail_options($this->options);
		$opts['report_period'] = 'custom';
		foreach ($time_arr as $mnr => $dates) {
			$opts['start_time'] = $dates['start'];
			$opts['end_time'] = $dates['end'];
			$report_class = new Status_Reports_Model($opts);
			$data_tmp = $report_class->get_uptime();
			# So, all this does is to remove the summary for all reports?
			# That's anti-clever!
			foreach ($data_tmp as $group => $val) {
				if (!is_array($val) || !isset($val['states']))
					continue;
				$data[$group][$mnr] = $val;
			}
		}
		$report_data = $this->_sla_group_data($data);
		return $report_data;
	}

	/**
	*	Mangle SLA data for host- and servicegroups
	*/
	public function _sla_group_data($sla_data)
	{
		if (empty($sla_data))
			return false;
		$report_data = false;
		foreach ($sla_data as $period_data) {
			$table_data = false;
			$data = false;
			$name = false;
			// loop over whole period for current group
			foreach ($period_data as $period_start => $tmp_data) {
				if (!is_array($tmp_data) || !isset($tmp_data['states']))
					continue;
				$month_idx = date('n', $period_start);
				if (array_key_exists($month_idx, $this->options['months'])) {
					if (arr::search($tmp_data, 'states')) {

						# $tmp_data['states']['PERCENT_TOTAL_TIME_{UP,OK}']
						$real_val = $tmp_data['states'][self::$sla_field_names[$this->options['report_type']]];

						# control colour of bar depending on value
						# true = green, false = red
						$sla_ok = $this->options['months'][$month_idx] > $real_val ? true : false;
					} else {
						// create empty 'real' values
						$sla_ok = false;
						$real_val = 0;
					}

					$data[date('M', $period_start)] = array($real_val, $this->options['months'][$month_idx], $sla_ok);
					if ($this->options['scheduleddowntimeasuptime'] == 2)
						$table_data[$period_start] = array($real_val, $this->options['months'][$month_idx], $tmp_data['states']['PERCENT_TIME_DOWN_COUNTED_AS_UP']);
					else
						$table_data[$period_start] = array($real_val, $this->options['months'][$month_idx]);
				}
				$source = false;
				foreach ($tmp_data as $subobj) {
					if (!is_array($subobj) || !isset($subobj['states']))
						continue;
					$source[] = $subobj['source'];
				}
				$name = isset($tmp_data['groupname']) ? $tmp_data['groupname'] : false;
			}

			if (!$name && count($source) == 1)
				$name = $source;

			$report_data[] = array(
				'name' => $name,
				'table_data' => $table_data,
				'data_str' => http_build_query($data),
				'source' => $source,
				'avail_link' => $this->_generate_avail_member_link(),
			);
		}
		return $report_data;
	}

	/**
	 * @param string $members
	 * @return string A link to an Availability report for all members
	 */
	private function _generate_avail_member_link()
	{
		$objects = '';
		$return = url::site().'avail/generate?';
		$return .= $this->options->as_keyval_string(false);
		return $return;
	}

	/**
	*	@desc  Splits a span of unixtime(start_time, end_time) into slices for every month number in $months.
	*	@param $start_time int start timestamp of the first month
	*	@param $end_time int end timestamp of the last month
	*	@return array of start/end timestamps for every timestamp gives the start and end of the month
	*/
	private function _split_month_data($start_time, $end_time)
	{
		$months = array();
		$date = $start_time;
		while ($date < $end_time) {
			$end = strtotime('+1 month', $date);
			$months[$date] = array('start' => $date, 'end' => $end);
			$date = $end;
		}
		return $months;
	}

	/**
	* Translated helptexts for this controller
	*/
	public static function _helptexts($id)
	{
		# Tag unfinished helptexts with @@@HELPTEXT:<key> to make it
		# easier to find those later
		$helptexts = array(
			'filter' => _("Free text search, matching the objects in the left list below"),
			'scheduled_downtime' => _("Select if downtime that occurred during scheduled downtime should be counted as the actual state, as uptime, or if it should be counted as uptime but also showing the difference that makes."),
			'stated_during_downtime' => _("If the application is not running for some time during a report period we can by this ".
				"option decide to assume states for hosts and services during the downtime."),
			'includesoftstates' => _("A problem is classified as a SOFT problem until the number of checks has reached the ".
				"configured max_check_attempts value. When max_check_attempts is reached the problem is reclassified as HARD."),
			'sla_mode' => _("What calculation method to use for the report summary.<br/>".
				"Depending on how your network is configured, your SLA might be the group availability (worst state at any point in time) or cluster mode availability (best state at any point time). You can also choose to use average values instead for ".
				"the group or object in question.<br/>Note that calculating the summary incorrectly could mislead users."),
			'use_alias' => _("Select if you would like to see host aliases in the generated reports instead of just the host_name"),
			'csv_format' => _("The CSV (comma-separated values) format is a file format that stores tabular data. This format is supported ".
				"by many applications such as MS Excel, OpenOffice and Google Spreadsheets."),
			'save_report' => _("Check this box if you want to save the configured report for later use."),
			'enter-sla' => _("Enter the selected SLA values for each month. Percent values (0.00-100.00) are assumed."),
			'report_settings_sml' => _("Here you can modify the report settings for the report you are currently viewing."),
			'log_entries' => _("Shows the actual log messages that this report was created of."),
			'hostgroup_breakdown' => _("Here you have a list of all hosts that are member of this hostgroup and their states."),
			'servicegroup_breakdown' => _("Here you have a list of all services that are member of this servicegroup and their states."),
			'average_and_sla' => _("Shows the Average and SLA values for all selected services above."), // text ok?
			'availability' => _("This table shows a breakdown of the different states. How much time that was ok, warning, unknown, critical or undetermined in both actual time and percent. Time is also divied between uncheduled and scheduled which helps you to separate unplanned and planned events."),
			'piechart' => _("Pie chart that displays how much time in percent that was ok, warning, unknown, critical or undetermined."),
			'sla_graph' => _("Graphical report of the SLA. Green bars meens that the SLA was fulfilled and red that it was not fulfilled."),
			'sla_breakdown' => _("Breakdown of the SLA report in actual figures."),
			'sla_group_members' => _("Members of the selected group that the report is generated for. All members are links to individual reports."),
			'trends' => _("Shows trends during selected report period, lines above the main line are upscaled statechanges from the blacked out section below"),
			'trends_scaling' => _("Shows trends during selected report period, lines above the main line are upscaled statechanges from the blacked out section below"),
			'saved_reports' => _("A list of all your saved reports. To load them, select the report you wish to generate and click select."),
			'use-sla-values' => _("Load SLA-values from previously saved reports. Just select a report in the list and it will autoload."),
			'include_pie_charts' => _('If you include this, your availability percentages will be graphed in pie charts'),

			// new scheduled report
			'report-type-save' => _("Select what type of report you would like to schedule the creation of"),
			'select-report' => _("Select which report you want to you want to schedule"), // text ok?
			'report' => _("Select the saved report to schedule"),
			'interval' => _("Select how often the report is to be produced and delivered"),
			'recipents' => _("Enter the email addresses of the recipients of the report. To enter multiple addresses, separate them by commas"),
			'filename' => _("This field lets you select a custom filename for the report. If the name ends in <strong>.csv</strong>, a CSV file will be generated - otherwise a PDF will be generated."),
			'start-date' => _("Enter the start date for the report (or use the pop-up calendar)."),
			'end-date' => _("Enter the end date for the report (or use the pop-up calendar)."),
			'local_persistent_filepath' => _("Specify an absolute path on the local disk, where you want the report to be saved in PDF format.").'<br />'._("This should be the location of a folder, for example /tmp"),
			'attach_description' => _("Append this description inside the report's header to the general description given for the report"),
			'include_trends' => _("Check this to include a trends graph in your report.<br>Warning: This can make your reports slow!"),
			'include_trends_scaling' => _("Check this to get upscaled values on your trends graph for small segments of time that would otherwise be hidden."),
			'include_alerts' => _('Include a log of all alerts for all objects in your report.<br>Warning: This can make your reports slow!'),
			'synergy_events' => _('Include a detailed history of what happened to BSM objects'),
			'status_to_display' => _('Check a status to exclude log entries of that kind from the report.')
		);
		if (array_key_exists($id, $helptexts)) {
			echo $helptexts[$id];
		} else
			parent::_helptexts($id);
	}
}