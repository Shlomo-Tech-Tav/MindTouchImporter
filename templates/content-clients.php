	<h1>Client Management</h1>
	<p>Choose a client to edit.</p>

	<form id="client-selection" action="<?php echo ABSURL; ?>management/clients/select" method="post">
		<?php nonce_input(); ?>

		<input type="submit" value="Edit client &raquo;" class="btn btn-primary">
		<a href="<?php echo ABSURL; ?>management/clients/create" class="btn btn-default">Create new client &raquo;</a>
		<table class="table table-striped table-bordered table-condensed table-hover sortable">
			<thead><tr>
				<th></th>
				<th>Name</th>
				<th>Client Code</th>
				<th>Created</th>
			</tr></thead>
			<tbody>
<?php
foreach ($clients as $client) {
?>
			<tr>
				<td><input type="radio" id="client_id_<?php echo $client['client_id']; ?>" name="client_id" value="<?php echo $client['client_id']; ?>"></td>
				<td><a href="<?php echo ABSURL; ?>management/clients/edit/<?php echo $client['code']; ?>"><?php echo $client['name']; ?></a></td>
				<td><?php echo $client['code']; ?></td>
				<td><?php echo $client['created_on']; ?></td>
			</tr>
<?php
}
?>

			</tbody>
		</table>

		<input type="submit" value="Edit client &raquo;" class="btn btn-primary">
		<a href="<?php echo ABSURL; ?>management/clients/create" class="btn btn-default">Create new client &raquo;</a>
	</form>
