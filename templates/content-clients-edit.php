	<h1>Client Management</h1>
	<h2>Edit Client: <?php echo $client['name']; ?></h2>

	<form id="client-edit" action="<?php echo ABSURL; ?>management/clients/process/<?php echo $client['code']; ?>" method="post">
		<?php echo $this->getHtml('content-clients-form', $data); ?>

		<?php nonce_input(); ?>

		<input type="hidden" id="client_id" name="client_id" value="<?php echo $client['client_id']; ?>">
		<input type="submit" value="Update client &raquo;" class="btn btn-primary">
		<input type="reset" id="reset-client" value="Cancel &raquo;" class="btn">
	</form>
