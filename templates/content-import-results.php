<?php
// Store counts.
$success_count = count($successes);
$failure_count = count($failures);
$links_count = count($pages_with_internal_links);
// $tables_count = count($pages_with_tables);
// $assets_count = count($pages_with_assets);
$assets_unadded_count = count($assets_unadded);
?>

	<h2>Import: <?php echo $import; ?></h2>

	<div class="bg-info table-of-contents">
		<h3>Contents</h3>
		<ul>
			<li><a href="#successes">Successes</a></li>
<?php
if ($failure_count > 0) {
?>
			<li><a href="#failures">Failures</a></li>
<?php
}
?>
			<li><a href="#internal_links">Pages with Internal Links</a></li>
		</ul>
	</div>

	<h3 id="successes">Successes</h3>
	<p>The following pages were added to the MindTouch instance for <?php echo $client['name']; ?> underneath the <a href="https://<?php echo $destination_link; ?>"><?php echo $destination_link; ?></a> directory.</p>
	<p>Pages added: <?php echo $success_count; ?>.</p>
	<ul>
<?php
if ($success_count > 0) {
	foreach ($successes as $success) {
?>
		<li><a href="<?php echo $success['uri']; ?>"><?php echo htmlspecialchars($success['title']); ?></a></li>
<?php
	}
}
?>
	</ul>

<?php
if ($failure_count > 0) {
?>
	<h3 id="failures">Failures</h3>
	<p>Errors adding pages: <?php echo $failure_count; ?>.</p>

	<ul>
<?php
	foreach ($failures as $failure) {
?>
		<li><?php echo $failure['title']; ?>: <?php echo $failure['error']; ?></li>
<?php
	}
?>
	</ul>
<?php
}
?>

	<h3 id="internal_links">Pages with Internal Links</h3>
	<p>Pages that have internal links: <?php echo $links_count; ?>.</p>

	<ul>
<?php
if ($links_count > 0) {
	foreach ($pages_with_internal_links as $link) {
?>
		<li><a href="<?php echo $successes[$link]['uri']; ?>"><?php echo htmlspecialchars($successes[$link]['title']); ?></a></li>
<?php
	}
}
?>
	</ul>
