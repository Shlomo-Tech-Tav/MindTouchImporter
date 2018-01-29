	<h1>Client Selection</h1>
	<p>Choose a client to begin the content import process.</p>

	<form id="client-selection" action="<?php echo ABSURL; ?>client-process" method="post">
		<?php nonce_input(); ?>

		<div class="row">
			<fieldset class="col-md-5">
				<div class="form-group">
					<label for="client">Choose a client to import.</label>
					<select name="client" id="client" class="form-control">
<?php
	foreach ($clients as $client) {
?>
						<option value="<?php echo $client['code']; ?>"><?php echo $client['name']; ?></option>
<?php
	}
?>
					</select>
				</div>
			</fieldset>
		</div>

		<input type="submit" value="Choose client &raquo;" class="btn btn-primary">
	</form>
