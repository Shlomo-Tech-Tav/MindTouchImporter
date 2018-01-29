<?php
$last_accessed = 'Never';
if (!empty($user['last_accessed']) && $user['last_accessed'] !== '0000-00-00 00:00:00') {
	$last_accessed = $user['last_accessed'];
}

$expires_on = '';
if (!empty($user['expires_on']) && $user['expires_on'] !== '0000-00-00') {
	$expires_on = $user['expires_on'];
}
?>

		<div class="row">
			<fieldset class="col-md-6">
				<legend>Credentials</legend>

				<div class="form-group required">
					<label for="username">Username</label>
					<input type="text" class="form-control" id="username" name="username" value="<?php echo !empty($user['username']) ? $user['username'] : ''; ?>">
				</div>
				<div class="form-group">
					<label for="password">Password</label>
					<input type="password" class="form-control" id="password" name="password">
				</div>
				<div class="form-group">
					<label for="password_confirm">Confirm password</label>
					<input type="password" class="form-control" id="password_confirm" name="password_confirm">
				</div>
			</fieldset>

			<fieldset class="col-md-6">
				<legend>User Information</legend>

				<div class="form-group required">
					<label for="email">Email address</label>
					<input type="email" class="form-control" id="email" name="email" value="<?php echo !empty($user['email']) ? $user['email'] : ''; ?>">
				</div>
				<div class="form-group">
					<label for="first_name">First Name</label>
					<input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo !empty($user['first_name']) ? $user['first_name'] : ''; ?>">
				</div>
				<div class="form-group">
					<label for="last_name">Last Name</label>
					<input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo !empty($user['last_name']) ? $user['last_name'] : ''; ?>">
				</div>
			</fieldset>
		</div>

		<div class="row">
			<fieldset class="col-md-6">
				<legend>Meta Information</legend>

				<div class="form-group created-on">
					<label>Account creation date</label>
					<input type="text" class="form-control" id="created_on" value="<?php echo !empty($user['created_on']) ? $user['created_on'] : ''; ?>" disabled>
				</div>
				<div class="form-group last-accessed">
					<label for="last_accessed">User last logged in</label>
					<input type="text" class="form-control" id="last_accessed" value="<?php echo $last_accessed; ?>" disabled>
				</div>
				<div class="form-group">
					<label for="expires_on">Account expires on</label>
					<input type="text" class="form-control" id="expires_on" name="expires_on" value="<?php echo !empty($expires_on) ? $expires_on : ''; ?>" data-provide="datepicker" data-date-format="yyyy-mm-dd">
				</div>
			</fieldset>
		</div>
