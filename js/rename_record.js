CATMH = {}

// Reassign interviews
CATMH.reassignInterviews = function() {
	var to_record = getParameterByName('id');
	function callback() {
		var ajax_url = app_path_webroot + "/ExternalModules/?prefix=cat_mh_cha&page=ajax/reassign_interviews&pid=" + pid;
		var from_record = $("#from_record").val();
		$.post(
			ajax_url,
			{from_record: from_record, to_record: to_record},
			function(data) {
				var obj = JSON.parse(data);
				if (obj) {
					if (obj.message)
						alert(obj.message);
				}
			}
		);
	}
	var dialogContent = "<p>You may re-assign CAT-MH interview results from another record to this one (record '" + to_record + "').</p>";
	var dialogContent = dialogContent + "<p>Enter the record ID to re-assign interviews from and click 'Reassign'</p>";
	var dialogContent = dialogContent + "<label for='from_record'>Record ID</label>&emsp;";
	var dialogContent = dialogContent + "<input type='text' id='from_record' name='from_record'>";
	simpleDialog(dialogContent, "Reassign Interviews (CAT-MH)", null, 450, null, 'Cancel', callback, 'Reassign');
}

// initialize schedule (datatable)
$(function() {
	// copy rename record item in ul#recordActionDropdown and change it's onclick and label
	var ul = $("#recordActionDropdown");
	CATMH.ul = ul;
	ul.children().each(function(index, element) {
		if (element.innerHTML.includes("renameRecord();")) {
			var renameButton = $(element);
			var catmhRenameButton = renameButton.clone(false, false);
			$(catmhRenameButton).find('a').removeAttr("onclick");
			$(catmhRenameButton).find('a').attr("onclick", "CATMH.reassignInterviews();");
			renameButton.after(catmhRenameButton);
			
			// update new catmh rename button
			catmhRenameButton.find('span').html("<i class='fas fa-exchange-alt'></i> Reassign interviews (CAT-MH)");
		}
		
	});
})