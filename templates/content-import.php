<?php
// Remove any queued items from imports.
if (isset($queue)) {
	foreach ($imports as $file => $file_plus_ext) {
		foreach ($queue as $queue_item) {
			$queue_file = $queue_item['file'] . '.' . $queue_item['extension'];
			if ($queue_file === $file_plus_ext) {
				// Remove the item from the imports array.
				unset($imports[$file]);
			}
		}
	}

	// Prepare queue popover.
	$queue_count = count($queue);
	$queue_class = '';
	$queue_content = '';
	if ($queue_count < 1) {
		$queue_class = 'hide';
	} else {
		$queue_content = '<ul>';
		foreach ($queue as $queue_item) {
			$queue_file = $queue_item['file'] . '.' . $queue_item['extension'];
			$queue_content .= '<li>' . $queue_file . '</li>';
		}
		$queue_content .= '</ul>';
	}
} else {
	$queue_count = 0;
	$queue_class = 'hide';
	$queue_content = '';
}

// Hide the form when there are no imports.
$import_parse_class = '';
if (count($imports) < 1) {
	$import_parse_class = 'hide';
}

?>

	<div class="row">
		<h1><?php echo $title; ?></h1>

		<div class="queued-items-wrap pull-right">
			<button type="button" class="queued-items btn btn-default <?php echo $queue_class; ?>" tabindex="0" data-toggle="popover" title="Imports to process" data-trigger="focus" data-content="<?php echo $queue_content; ?>">
				<span class="glyphicon glyphicon-file" aria-hidden="true"></span> <?php echo $queue_count; ?> to process
			</button>
		</div>

		<p><?php echo $description; ?></p>

		<?php echo !empty($upload_form) ? $upload_form : ''; ?>

		<div class="<?php echo $import_parse_class; ?>">
			<h3>Select a previously uploaded import file</h3>
			<form id="import-parse" action="<?php echo $form_action; ?>" method="post">
				<?php nonce_input(); ?>

				<div class="row">
					<fieldset class="col-md-5">
						<div class="form-group">
							<label for="import">Choose an import file.</label>
							<select name="import" id="import" class="form-control">
<?php
	natcasesort($imports);
	foreach ($imports as $file => $file_plus_ext) {
?>
								<option value="<?php echo $file; ?>"><?php echo $file; ?></option>
<?php
	}
?>
							</select>
						</div>
					</fieldset>
				</div>

				<input type="hidden" name="client" value="<?php echo $client['code']; ?>">
				<input type="submit" value="<?php echo $submit_button; ?> &raquo;" class="btn btn-primary">
			</form>
		</div>
	</div>
