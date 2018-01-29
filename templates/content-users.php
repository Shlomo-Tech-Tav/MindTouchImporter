	<h1>User Management</h1>
	<p>Choose a user to edit.</p>

	<form id="user-selection" action="<?php echo ABSURL; ?>management/users/select" method="post">
		<?php nonce_input(); ?>

		<input type="submit" value="Edit user &raquo;" class="btn btn-primary">
		<a href="<?php echo ABSURL; ?>management/users/create" class="btn btn-default">Create new user &raquo;</a>
		<table class="table table-striped table-bordered table-condensed table-hover sortable">
			<thead><tr>
				<th></th>
				<th>Username</th>
				<th>Name</th>
				<th>Clients</th>
				<th>Created</th>
				<th>Last Accessed</th>
				<th>Expires</th>
			</tr></thead>
			<tbody>
<?php
foreach ($users as $user) {
	$last_accessed = ($user['last_accessed'] === '0000-00-00 00:00:00') ? 'Never' : $user['last_accessed'];
	$expires_on = ($user['expires_on'] === '0000-00-00') ? 'Never' : $user['expires_on'];
?>
			<tr>
				<td><input type="radio" id="user_id_<?php echo $user['user_id']; ?>" name="user_id" value="<?php echo $user['user_id']; ?>"></td>
				<td><a href="<?php echo ABSURL; ?>management/users/edit/<?php echo $user['username']; ?>"><?php echo $user['username']; ?></a></td>
				<td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
				<td><a href="<?php echo ABSURL; ?>management/users/clients/<?php echo $user['username']; ?>" title="User's Clients"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></a></td>
				<td><?php echo $user['created_on']; ?></td>
				<td><?php echo $last_accessed; ?></td>
				<td><?php echo $expires_on; ?></td>
			</tr>
<?php
}
?>

			</tbody>
		</table>

		<input type="submit" value="Edit user &raquo;" class="btn btn-primary">
		<a href="<?php echo ABSURL; ?>management/users/create" class="btn btn-default">Create new user &raquo;</a>
	</form>
