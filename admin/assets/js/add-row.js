var rowNum = 0;

function addRow(frm){
	rowNum ++;
	var row = '<input class="regular-text highlight" type="url" name="pbt_remix_settings[pbt_api_endpoint-'+rowNum+']" value="'+rowNum+'" />\n\
	<input onclick="addRow(this.form);" type="button" value="Add URL" />';

}

