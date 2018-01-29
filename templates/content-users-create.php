	<h1>User Management</h1>
	<h2>Create User</h2>

	<form id="user-create" action="<?php echo ABSURL; ?>management/users/process/" method="post">
		<?php echo $this->getHtml('content-users-form', $data); ?>

		<?php nonce_input(); ?>

		<input type="submit" value="Create user &raquo;" class="btn btn-primary">
		<input type="reset" id="reset-user" value="Cancel &raquo;" class="btn">
	</form>
