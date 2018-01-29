<h1><?php echo $title; ?></h1>
	<p>Choose an import report to view.</p>

	<table class="table table-striped table-bordered table-condensed table-hover sortable">
		<thead><tr>
			<th data-defaultsort="asc">#</th>
			<th>Imported File</th>
			<th>User</th>
			<th>Date</th>
<?php
if (is_admin()) {
?>
			<th>Production</th>
			<th>Delete</th>
<?php
}
?>
		</tr></thead>
		<tbody>
<?php
$i = 1;
foreach ($reports as $report) {
	$production = (!empty($report['production'])) ? 'Yes' : 'No';
	$last_accessed = ($user['last_accessed'] === '0000-00-00 00:00:00') ? 'Never' : $user['last_accessed'];
	$expires_on = ($user['expires_on'] === '0000-00-00') ? 'Never' : $user['expires_on'];
?>
		<tr>
			<td><?php echo $i; ?></td>
			<td><a href="<?php echo ABSURL; ?>client/<?php echo $client['code']; ?>/report/<?php echo $report['report_id']; ?>"><?php echo $report['import_title']; ?></a></td>
			<td><?php echo $report['username']; ?> (<?php echo $report['first_name'] . ' ' . $report['last_name']; ?>)</td>
			<td><?php echo $report['created_on']; ?></td>
<?php
	if (is_admin()) {
?>
			<td><?php echo $production; ?></td>
			<td>
				<form action="<?php echo ABSURL; ?>client/<?php echo $client['code']; ?>/report/<?php echo $report['report_id']; ?>/delete" method="post">
					<?php nonce_input(); ?>

					<button class="btn btn-danger" type="button" data-toggle="modal" data-target="#confirmDelete" data-title="Delete Report" data-message="Are you sure you want to delete the report for <?php echo htmlentities($report['import_title']); ?>?">
						<i class="glyphicon glyphicon-trash"></i> Delete
					</button>
				</form>
			</td>
<?php
	}
?>
		</tr>
<?php
	$i ++;
}
?>

		</tbody>
	</table>

<?php
echo $this->getHtml('content-delete-confirm', $data);
?>
