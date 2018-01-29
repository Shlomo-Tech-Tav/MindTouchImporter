	<h1>Client Management</h1>
	<h2>Create client</h2>

	<form id="client-create" action="<?php echo ABSURL; ?>management/clients/process/" method="post">
		<?php echo $this->getHtml('content-clients-form', $data); ?>

		<?php nonce_input(); ?>

		<input type="submit" value="Create Client &raquo;" class="btn btn-primary">
		<input type="reset" id="reset-client" value="Cancel &raquo;" class="btn">
	</form>
