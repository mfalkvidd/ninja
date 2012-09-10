<?php defined('SYSPATH') OR die("No direct access allowed");
if (isset($schedules)) {
	echo $schedules;
}
# workaround for _print_alert_totals_table() below
$this->create_pdf = $create_pdf;
?>

<div class="left w98">
	<h1><?php echo _('Overall Totals') ?></h1>
	<p style="margin-top:-10px"><?php $this->_print_duration($options['start_time'], $options['end_time']); ?></p>
		<?php
		foreach ($result as $service_name => $ary) {
			$foo = explode(';', $service_name);
			$host_name = $foo[0];
			$service = $foo[1];
			//echo _('Service') . "'" . $service . "' on " ._('Host') . "'" . $host_name . "'<br />\n";
			$name = $service .' on '._('Host').': '.$host_name;
			$this->_print_alert_totals_table(_('Service Alerts'), $ary['service'], $service_state_names, $ary['service_totals'], $name);
		}
		//printf("Report completed in %.3f seconds<br />\n", $completion_time);
		?>
</div>
