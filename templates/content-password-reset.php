	<h1>Reset Password</h1>
	<p>Enter your new password below.</p>

	<form action="<?php echo ABSURL; ?>password/reset-process" method="post">
		<?php nonce_input(); ?>

		<div class="row">
			<fieldset class="col-md-3">
				<div class="form-group">
					<label for="password">Password</label>
					<input type="password" class="form-control" id="password" name="password">
				</div>
				<div class="form-group">
					<label for="password_confirm">Confirm password</label>
					<input type="password" class="form-control" id="password_confirm" name="password_confirm">
				</div>
			</fieldset>
		</div>

		<input type="hidden" id="user_id" name="user_id" value="<?php echo $user_id; ?>">
		<input type="hidden" id="email" name="email" value="<?php echo $email; ?>">
		<input type="hidden" id="token" name="token" value="<?php echo $token; ?>">
		<input type="submit" value="Reset password &raquo;" class="btn btn-primary">
	</form>
