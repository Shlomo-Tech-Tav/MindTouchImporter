<?php
$empty_count = $pages_total - $pages_import;
?>

	<h1><?php echo $title; ?></h1>
	<h2>Analyze Import: <?php echo $import; ?></h2>
	<p>Content for <?php echo $pages_import; ?> pages will be added to the MindTouch instance for <?php echo $client['name']; ?> underneath the <a href="https://<?php echo $destination_link_production; ?>" class="import_destination" data-production="<?php echo $destination_link_production; ?>" data-test="<?php echo $destination_link_test; ?>"><?php echo $destination_link_production; ?></a> directory.</p>
<?php
if ($empty_count > 0) {
?>
	<?php echo $empty_count; ?> pages without content were found and will not be added.
<?php
}
?>
	</p>

	<h2>Pages to Add</h2>
	<form action="<?php echo ABSURL; ?>client/<?php echo $client['code']; ?>/process" method="post">

	<div class="row target_select_row">
		<fieldset class="col-md-5">
			<div class="form-group">
				<label>Choose where you want the pages imported.</label>
				<input type="hidden" name="target_select" id="target_select" value="">

				<div id="tree"></div>
				<script>
				$(function() {
					'use strict';
					var glyph_opts = {
						map: {
							doc: "glyphicon glyphicon-file",
							docOpen: "glyphicon glyphicon-file",
							checkbox: "glyphicon glyphicon-unchecked",
							checkboxSelected: "glyphicon glyphicon-check",
							checkboxUnknown: "glyphicon glyphicon-share",
							dragHelper: "glyphicon glyphicon-play",
							dropMarker: "glyphicon glyphicon-arrow-right",
							error: "glyphicon glyphicon-warning-sign",
							expanderClosed: "glyphicon glyphicon-chevron-right",
							expanderLazy: "glyphicon glyphicon-chevron-right",  // glyphicon-plus-sign
							expanderOpen: "glyphicon glyphicon-chevron-down",  // glyphicon-collapse-down
							folder: "glyphicon glyphicon-folder-close",
							folderOpen: "glyphicon glyphicon-folder-open",
							loading: "glyphicon glyphicon-refresh glyphicon-spin"
						}
					};

					$("#tree").fancytree({
						extensions: ["glyph"],
						glyph: glyph_opts,
						source: <?php echo json_encode($target_select); ?>,
						checkbox: false,

						activate: function(event, data) {
							// Update form.
							$('#target_select').val(data.node.key);
							// Update target path.
							target_select_change(data.node.data.path);
						},

						lazyLoad: function(event, data) {
							// Issue an ajax request to load child nodes
							data.result = {
								cache: false,
								url: "<?php echo ABSURL; ?>client/<?php echo $client['code']; ?>/tree?page_id=" + data.node.key,
							}
						},

					});
				});
				</script>

			</div>
		</fieldset>
	</div>


	<ul class="pages-tree">
		<li><a href="https://<?php echo $destination_link_production; ?>" class="import_destination" data-production="<?php echo $destination_link_production; ?>" data-test="<?php echo $destination_link_test; ?>"><?php echo $destination_link_production; ?></a>
<?php
echo $this->buildList($nested_pages);
?>
		</li>
	</ul>

<?php
if ($empty_count > 0) {
?>
	<h2>Empty Pages</h2>

	<p>The following pages had no content and will be added to MindTouch only if there are pages beneath them.</p>
	<ul>
<?php
	foreach ($pages_empty as $page) {
?>
		<li><?php echo htmlspecialchars($page['title']); ?></li>
<?php
	}
?>
	</ul>
<?php
}

if (is_admin()) {
?>
		<div class="checkbox">
			<label for="use_test">
<?php
	if (!valid_production_credentials($client['api_url'], $client['api_username'], $client['api_password'])) {
?>
				<input type="checkbox" checked disabled> Use test MindTouch instance. (<em>Production API credentials have not been set.</em>)
				<input type="hidden" id="use_test" name="use_test" value="yes">
<?php
	} else {
?>
				<input type="checkbox" id="use_test" name="use_test" value="yes"> Use test MindTouch instance.
<?php
	}
?>
			</label>
		</div>
<?php
}
?>
		<div class="checkbox">
			<label for="delete_import">
				<input type="checkbox" id="delete_import" name="delete_import" value="yes"> Remove the import file once complete.
			</label>
		</div>

		<?php nonce_input(); ?>

		<input type="hidden" name="client" value="<?php echo $client['code']; ?>">
		<input type="hidden" name="import" value="<?php echo $import; ?>">
		<input type="submit" value="Import the content &raquo;" class="btn btn-primary">
	</form>
