<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<div class="extra_toolbar">
	<div class="right lsfilter-edit-bar">
		<input type="hidden" id="server_name"
			value="<?php echo $_SERVER['SERVER_NAME']; ?>" /> <span id="filter_visual_result"></span>
		<button id="show-filter-query-multi-action" title="Send multi action"><span class="icon-16 x16-check-boxes"></span></button>
		<button id="show-filter-query-builder-manual-button" title="Show/Edit Text Filter"><span class="icon-16 x16-edit"></span></button>
		<button id="show-filter-query-builder-graphical-button" title="Show/Edit Graphical Filter"><span class="icon-16 x16-command"></span></button>
		<button id="show-filter-query-saved" title="Show saved filters"><span class="icon-16 x16-status-detail"></span></button>
	</div>
	<div id="filter_result_totals"></div>
</div>

<div class="extra_toolbar_spacer"></div>

<?php echo form::open('command/multi_action', array('id'=>'listview_multi_action_form')); ?>
<div class="clear" id="filter_result"><div style="text-align: center; margin: 32px;"><span class="lsfilter-loader">Loading...</span></div></div>
<input type="hidden" id="listview_multi_action_obj_action" name="multi_action" value="" />
<input type="hidden" id="listview_multi_action_obj_type" name="obj_type" value="" />
<?php echo form::close(); ?>

<div id="filter-query-builder">

	<div style="margin: 8px 0 0 8px">
		<input type="text" id="lsfilter_save_filter_name" placeholder="Filter Name" />
		<button id="lsfilter_save_filter">Save Filter</button>
		<input type="checkbox" id="lsfilter_save_filter_global" /> Make global
	</div>

	<div id="filter-query-saved">

		<h2>Saved filters</h2>

		<input type="checkbox" id="filter-query-saved-hide-static" /> Hide static
		<input type="checkbox" id="filter-query-saved-hide-global" /> Hide global
		<input type="checkbox" id="filter-query-saved-hide-user" /> Hide user<br />
		<ul id="filter-query-saved-filters"></ul>

		<pre id="filter-query-saved-preview"></pre>

	</div>

	<div id="filter-query-builder-manual">

		<h2>Manual input</h2>

		<form action="#" onsubmit="dosubmit();">
			<textarea style="width: 98%; height: 30px" name="filter_query"
				id="filter_query">
				<?php echo htmlentities($query); ?>
			</textarea>
		</form>

	</div>

	<div id="filter-query-builder-graphical">

		<h2>Graphical input</h2>

		<form id="filter_visual_form">
			<div id="filter_visual">Filter</div>
		</form>

	</div>
	
	<div id="filter-query-multi-action">
		<h2>Multi Action</h2>
		<?php echo form::dropdown(array('name' => 'multi_action', 'class' => 'item_select auto', 'id' => 'multi_action_select'), array(''=>'Table doesnt allow multi select')); ?>
		<button id="multi_action_select_send">Submit</button>

	</div>
</div>