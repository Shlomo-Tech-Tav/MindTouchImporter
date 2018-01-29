	<h3>Upload a file to import</h3>
	<div class="row">
		<div class="col-md-6">
			<p>The following file types are what can be imported.</p>
			<ul>
<?php
foreach ($client['extensions'] as $extension) {
	if ($extension === 'zip') {
?>
				<li><a class="popover-zip" tabindex="0" data-toggle="popover" data-trigger="focus" title="Zipped Packages"><?php echo $extension; ?> <span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span></a></li>
<?php
	} else {
?>
				<li><?php echo $extension; ?></li>
<?php
	}
}
?>
			</ul>

			<form id="import-upload" action="<?php echo ABSURL; ?>client/<?php echo $client['code']; ?>/upload" method="POST">
				<?php nonce_input(); ?>
				<span class="btn btn-primary fileinput-button">
					<i class="glyphicon glyphicon-plus"></i>
					<span>Select a file to upload and import &raquo;</span>
					<input type="file" id="import-upload-file" name="importUpload">
				</span>
				<input type="hidden" name="client" value="<?php echo $client['code']; ?>">
			</form>

			<div id="import-upload-progress" class="progress hidden">
				<div class="progress-bar progress-bar-success progress-bar-striped active"></div>
			</div>
		</div>
		<div class="popover-zip-content hide">
			<p>Zipped packages containing assets can be uploaded, as long as they are in the proper format. The content to be imported should be at the base level, with a folder containing all the images to be added to MindTouch.</p>
			<p>For example, the image below shows a zipped folder named "User Guide.zip". Inside the archive are a file named "User Guide.htm" and a folder named "User Guide_files", which contains all the assets.<br>
				<img src="<?php echo ABSURL; ?>includes/images/example-import-upload-zip.png" alt="Image of compressed folder" width="245" height="84">
			</p>
		</div>
	</div>
