	<h1>User Management</h1>
	<h2>Edit User Clients: <?php echo $user['username']; ?></h2>
	<p>The following are the clients that this user can access via the MindTouch Importer.</p>

	<form id="user-clients-edit" action="<?php echo ABSURL; ?>management/users/clients-process/<?php echo $user['username']; ?>" method="post">
		<div class="row">
			<fieldset class="col-md-6">
				<legend>Clients</legend>

<?php
foreach ($clients as $client) {
	$checked = in_array($client['client_id'], $user_clients) ? ' checked' : '';
?>
				<div class="checkbox">
					<label for="<?php echo $client['code']; ?>">
						<input type="checkbox" id="<?php echo $client['code']; ?>" name="clients[]" value="<?php echo $client['client_id']; ?>"<?php echo $checked; ?>>
						<?php echo $client['name']; ?>

					</label>
				</div>
<?php
}
?>
			</fieldset>

		</div>

		<?php nonce_input(); ?>

		<input type="hidden" id="user_id" name="user_id" value="<?php echo $user['user_id']; ?>">
		<input type="submit" value="Update user clients &raquo;" class="btn btn-primary">
		<input type="reset" id="reset-user" value="Cancel &raquo;" class="btn">
	</form>
