	<h1>Log In</h1>
	<p>This tool allows content in XML and HTML files to be imported into an instance of MindTouch.</p>

	<form action="<?php echo ABSURL; ?>logging-in" method="post">
		<?php nonce_input(); ?>

		<div class="row">
			<fieldset class="col-md-3">
				<div class="form-group">
					<label for="username">Username</label>
					<input id="username" name="username" class="form-control" >
				</div>
				<div class="form-group">
					<label for="password">Password</label>
					<input id="password" name="password" type="password" class="form-control" >
					<p class="forgotten-password"><a href="<?php echo ABSURL; ?>password/forgot">Forgotten password?</a></p>
				</div>
			</fieldset>
		</div>

		<input type="hidden" name="redirect" value="<?php echo $redirect; ?>">
		<input type="submit" value="Log in &raquo;" class="btn btn-primary">
	</form>
