	<h1>Forgot Password</h1>
	<p>Enter your email address to receive information on how to reset your password.</p>

	<form action="<?php echo ABSURL; ?>password/forgot-process" method="post">
		<?php nonce_input(); ?>

		<div class="row">
			<fieldset class="col-md-4">
				<div class="form-group">
					<label for="email">Email Address</label>
					<input id="email" class="form-control" name="email">
				</div>
			</fieldset>
		</div>

		<input type="submit" value="Email me &raquo;" class="btn btn-primary">
	</form>
