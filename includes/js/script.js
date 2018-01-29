$(function() {
	'use strict';

	// Change destination location depending on use test setting.
    var $target_select = $('#target_select');
	var $use_test = $('#use_test');
	if ($use_test.length > 0) {
		$use_test.change(function() {
			var $importDestination = $('.import_destination');
			var $target_select_row = $('.target_select_row');
			if ($importDestination.length > 0) {
				// Support both checkbox and hidden input types.
				if ($use_test.attr('type') === 'checkbox' && $use_test.is(':checked')
					|| $use_test.attr('type') === 'hidden' && $use_test.val() === 'yes'
				) {
					// Change the link to the test location.
					var destination_link = $importDestination.data('test');
					$target_select_row.hide();
				} else {
					// Change the link to the production location.
					var destination_link = $importDestination.data('production');
					var target_path = $target_select.find("option:selected").text();
					if (target_path !== 'Home') {
						destination_link += target_path;
					}
					$target_select_row.show();
				}
				$importDestination.attr('href', 'https://' + destination_link);
				$importDestination.html(destination_link);
			}
		});
		$use_test.change();
	}

	// Auto-submit import processing form when available.
	var $importProcessing = $('#import-processing');
	if ($importProcessing.length > 0) {
		$importProcessing.submit();
		$importProcessing.find('input[type=submit]').hide();
	}

	// Auto-submit client selection form when a client is selected.
	var $clientSelection = $('#client-selection');
	if ($clientSelection.length > 0) {
		$('#client').change(function() {
			this.form.submit();
		});
	}

	// Sends user back to users list when cancelling a user form.
	var $resetUser = $('#reset-user');
	if ($resetUser.length > 0) {
		$resetUser.click(function() {
			window.location.href = APPLICATION.url + 'management/users/';
		});
	}

	// Clients form. Update extensions based on import type.
	var $importType = $('#import_type_id');
	if ($importType.length > 0) {
		$importType.change(function() {
			var $this = $(this);
			var $extensions = $('#extensions');
			if (import_extensions[$this.val()] !== undefined) {
				// Set the allowed extensions field to that of the import type and disable.
				$extensions.val(import_extensions[$this.val()]);
				$extensions.attr('disabled', 'disabled');
			} else {
				$extensions.removeAttr('disabled');
			}
		});
	}

	// Sends user back to clients list when cancelling a client form.
	var $resetClient = $('#reset-client');
	if ($resetClient.length > 0) {
		$resetClient.click(function() {
			window.location.href = APPLICATION.url + 'management/clients/';
		});
	}

	// Deal with upload import form.
	var $importUpload = $('#import-upload');
	if ($importUpload.length > 0) {
		var $file = $('#import-upload-file');
		var action = $importUpload.attr('action');
		var $progress = $('#import-upload-progress');
		var $bar = $progress.find('.progress-bar');
		$file.fileupload({
			url: action,
			dataType: 'json',
			// These two options prevent multiple uploads at once.
			maxNumberOfFiles: 1,
			singleFileUploads: false,
			formData: function(form) {
				return form.serializeArray();
			},
			beforeSend: function() {
				$progress.removeClass('hidden').fadeIn();
			},
			done: function (e, data) {
				// Show error and reset the bar.
				if (data.result.error) {
					alert(data.result.message);
					$progress.fadeOut(400, function() {
						$bar.css(
							'width',
							'0%'
						);
					});
					if (data.result.refresh) {
						document.location.reload();
					}
					return;
				} else {
					// Submit import form.
					var $importParse = $('#import-parse');

					// Add option to select box and select.
					var $importSelect = $importParse.find('#import');
					$importSelect.append($('<option>', { value: data.result.import }).text(data.result.import));
					$importSelect.val(data.result.import);

					// Submit the parse form.
					$importParse.submit();
				}
			},
			progressall: function (e, data) {
				var progress = parseInt(data.loaded / data.total * 100, 10);
				$bar.css(
					'width',
					progress + '%'
				);
			}
		}).prop('disabled', !$.support.fileInput)
			.parent().addClass($.support.fileInput ? undefined : 'disabled');
	}

	// Prepare popover for queued items on import form upload.
	var $queuedItems = $('.queued-items');
	if ($queuedItems.length > 0) {
		$queuedItems.popover({
			html: true,
			placement: 'bottom'
		});
	}

	// Reload page every half-minute when the queue is visible.
	if ($queuedItems.is(':visible')) {
		setTimeout(function() {
			document.location.reload();
		}, 30000);
	}

	// Prepare popover for zip uploads on import form upload.
	var $popoverZip = $('.popover-zip');
	if ($popoverZip.length > 0) {
		var popoverContent = '';
		var $popoverContent = $('.popover-zip-content');
		if ($popoverContent.length > 0) {
			popoverContent = $popoverContent.html();
		}
		$popoverZip.popover({
			content: popoverContent,
			html: true,
			placement: 'right'
		});
	}

	// Handle deletion confirmation modal.
	var $confirmDelete = $('#confirmDelete');
	if ($confirmDelete.length > 0) {
		$confirmDelete.on('show.bs.modal', function (e) {
			var $this = $(this);
			var $target = $(e.relatedTarget);
			$this.find('.modal-body p').text($target.data('message'));
			$this.find('.modal-title').text($target.data('title'));

			// Pass form reference to modal for submission on yes/ok
			var form = $target.closest('form');
			$this.find('.modal-footer #confirm').data('form', form);
		});

		// Form confirm (yes/ok) handler, submits form.
		$confirmDelete.find('.modal-footer #confirm').on('click', function(){
			$(this).data('form').submit();
		});
	}
});

// Update the destination location depending on user selection.
function target_select_change(target_path) {
	var $importDestination = $('.import_destination');
	var destination_link = $importDestination.data('production');
	if (target_path !== 'Home') {
		destination_link += target_path;
	}
	$importDestination.attr('href', 'https://' + destination_link);
	$importDestination.html(destination_link);
}
