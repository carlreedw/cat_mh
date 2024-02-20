$(document).ready(
	function() {
		// custom column sorting for 'Completed' and 'Acknowledged'
		$.fn.dataTable.ext.order['dom-checkbox'] = function  ( settings, col ) {
			// console.log('sorting cboxes');
			return this.api().column( col, {order:'index'} ).nodes().map( function ( td, i ) {
				return $('input', td).prop('checked') ? '1' : '0';
			} );
		}
		CATMH.completion_ordering = [
			'blue',
			'gray',
			'red',
			'yellow',
			'green'
		];
		jQuery.extend(jQuery.fn.dataTableExt.oSort,{
			"completion_status-asc": function(a,b){
				var a_color_index;
				var a_match = a.match(/data-color='([^']+)'/);
				if (a_match) {
					a_color_index = CATMH.completion_ordering.indexOf(a_match[1]);
				}
				
				var b_color_index;
				var b_match = b.match(/data-color='([^']+)'/);
				if (b_match) {
					b_color_index = CATMH.completion_ordering.indexOf(b_match[1]);
				}
				
				if (a_color_index > b_color_index){
					return -1;
				} else if (a_color_index < b_color_index){
					return 1;
				}
				
				return 0;
			},
			"completion_status-desc": function(a,b){
				var a_color_index;
				var a_match = a.match(/data-color='([^']+)'/);
				if (a_match) {
					a_color_index = CATMH.completion_ordering.indexOf(a_match[1]);
				}
				
				var b_color_index;
				var b_match = b.match(/data-color='([^']+)'/);
				if (b_match) {
					b_color_index = CATMH.completion_ordering.indexOf(b_match[1]);
				}
				
				if (a_color_index > b_color_index){
					return 1;
				} else if (a_color_index < b_color_index){
					return -1;
				}
				
				return 0;
			}
		});
		
		CATMH.datatable = $('#records').DataTable({
			ajax: CATMH.dashboard_ajax_url,
			pageLength: 25,
			columnDefs: [
				{className: 'dt-center', targets: '_all'},
				{type: 'completion_status', targets: 2},
				{targets: 9, orderDataType: 'dom-checkbox'}
			],
			order: [
				[9, 'asc'],
				[0, 'asc'],
				[4, 'asc']
			]
		});
		
		// when user clicks "Acknowledged" checkbox in an interview table row
		$('body').on('change', '.ack_cbox', function() {
			var data = {
				rid: $(this).attr('data-rid'),
				seq: $(this).attr('data-seq'),
				date: $(this).attr('data-date'),
				kcat: $(this).attr('data-kcat'),
				acknowledged: $(this).prop('checked')
			}
			var row = $(this).closest('tr')
			
			$.ajax({
				type: "POST",
				url: CATMH.acknowledge_ajax_url,
				data: data,
				complete: function(response) {
					// console.log('reviewInterview ajax returned successfully. responseText:', response.responseText)
					if (response.responseJSON) {
						var data = response.responseJSON
						console.log('response data:', data)
						if (data.error) {
							alert(data.error)
						} else if (data.success) {
							// update status icon
							row.find('img.fstatus').replaceWith(data.icon)
						}
					}
				},
				dataType: 'json'
			})
		})
		
		// when user clicks "show future dates" checkbox
		$('body').on('change', '#show_future_seqs', function() {
			var checked = $(this).prop('checked');
			var url = window.location.href
			// remove existing param value(s)
			url = url.replace(/&show_future_seqs=true/g, '');
			url = url.replace(/&show_future_seqs=false/g, '');
			
			// add param value
			url = url + '&show_future_seqs=' + encodeURIComponent(checked);
			window.location.href = url;
		})
		
		// when user clicks "show all interviews" checkbox
		$('body').on('change', '#show_all_interviews', function() {
			var checked = $(this).prop('checked');
			var url = window.location.href
			// remove existing param value(s)
			url = url.replace(/&show_all_interviews=true/g, '');
			url = url.replace(/&show_all_interviews=false/g, '');
			
			// add param value
			url = url + '&show_all_interviews=' + encodeURIComponent(checked);
			window.location.href = url;
		})
	}
);