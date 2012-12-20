var lsfilter_main = {
	update : function(query, source) {
		console.log('update <' + query + '> from <' + source + '>');
		try {
			var parser = new LSFilter(new LSFilterPreprocessor(),
					new LSFilterMetadataVisitor());
			var metadata = parser.parse(query);

			lsfilter_history.update(query,source,metadata);
			lsfilter_list.update(query,source,metadata);
			lsfilter_multiselect.update(query,source,metadata);
			lsfilter_saved.update(query,source,metadata);
			lsfilter_textarea.update(query,source,metadata);
			lsfilter_visual.update(query,source,metadata);
			
		} catch (ex) {
			this.handle_parse_exception(ex);
		}
	},
	init : function() {
		lsfilter_history.init();
		lsfilter_list.init();
		lsfilter_multiselect.init();
		lsfilter_saved.init();
		lsfilter_textarea.init();
		lsfilter_visual.init();

		// when frist loaded, the textarea contains the query from the
		// controller
		lsfilter_textarea.load();
	},
	handle_parse_exception : function(ex) {
	}
};

$().ready(function() {
	lsfilter_main.init();
});