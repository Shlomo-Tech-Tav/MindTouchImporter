<?php
$pages_imported = $pages_import - count($pages_to_import);
$progress_percent = number_format($pages_imported / $pages_import * 100);
?>

	<h1><?php echo $title; ?></h1>
	<h2>Processing Import: <?php echo $import; ?></h2>

	<p class="bg-danger"><strong>Warning: Leaving this page before the import is finished will result in a broken import.</strong></p>

	<p>Content for <?php echo $pages_import; ?> pages will be added to the MindTouch instance for <?php echo $client['name']; ?> underneath the <a href="https://<?php echo $destination_link_production; ?>" class="import_destination" data-production="<?php echo $destination_link_production; ?>" data-test="<?php echo $destination_link_test; ?>"><?php echo $destination_link_production; ?></a> directory.</p>

	<div class="progress">
		<div class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" aria-valuenow="<?php echo $progress_percent; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $progress_percent; ?>%;">
			<?php echo $progress_percent; ?>%
		</div>
	</div>

	<p>Imported <?php echo $pages_imported; ?> of <?php echo $pages_import; ?> pages.</p>

	<form id="import-processing" action="<?php echo ABSURL; ?>client/<?php echo $client['code']; ?>/process" method="post">
		<?php nonce_input(); ?>

		<input type="hidden" name="client" value="<?php echo $client['code']; ?>">
		<input type="hidden" name="import" value="<?php echo $import; ?>">
		<input type="hidden" name="use_test" id="use_test" value="<?php echo $use_test; ?>">
		<input type="hidden" name="target_select" id="target_select" value="<?php echo $target_select; ?>">
		<input type="hidden" name="delete_import" value="<?php echo $delete_import; ?>">
		<input type="submit" value="Import the content &raquo;" class="btn btn-primary">
	</form>
