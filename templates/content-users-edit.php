	<h1>User Management</h1>
	<h2>Edit User: <?php echo $user['username']; ?></h2>

	<form id="user-edit" action="<?php echo ABSURL; ?>management/users/process/<?php echo $user['username']; ?>" method="post">
		<?php echo $this->getHtml('content-users-form', $data); ?>

		<?php nonce_input(); ?>

		<input type="hidden" id="user_id" name="user_id" value="<?php echo $user['user_id']; ?>">
		<input type="submit" value="Update user &raquo;" class="btn btn-primary">
		<input type="reset" id="reset-user" value="Cancel &raquo;" class="btn">
	</form>
