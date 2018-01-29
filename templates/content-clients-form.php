<?php
// Import type dropdown should be disabled for editing.
$import_type_disabled = '';
if (!empty($client['client_id'])) {
	$import_type_disabled = ' disabled';
}

// Decide if extensions is disabled.
$extensions_disabled = '';
if (!empty($client['import_type_id'])) {
	$extensions_disabled = ' disabled';
}

// Create array of import types and extensions.
$import_extensions = array();
foreach ($import_types as $import_type) {
	$import_extensions[$import_type['import_type_id']] = $import_type['import_type_extensions'];
}
?>
		<script>var import_extensions = <?php echo json_encode($import_extensions); ?></script>
		<div class="row">
			<fieldset class="col-md-6">
				<legend>Client Information</legend>

				<div class="form-group">
					<label for="name">Client Name</label>
					<input type="name" class="form-control" id="name" name="name" value="<?php echo !empty($client['name']) ? $client['name'] : ''; ?>">
				</div>
				<div class="form-group">
					<label for="code">Client Code</label>
					<input type="text" class="form-control" id="code" name="code" value="<?php echo !empty($client['code']) ? $client['code'] : ''; ?>">
				</div>
				<div class="form-group">
					<label for="import_type_id">Import Type</label>
					<select class="form-control" id="import_type_id" name="import_type_id"<?php echo $import_type_disabled; ?>>
						<option value="0">Other</option>
<?php
foreach ($import_types as $import_type) {
	$selected = "";
	if (!empty($client['import_type_id']) && $import_type['import_type_id'] === $client['import_type_id']) {
		$selected = " selected";
	}
?>
						<option value="<?php echo $import_type['import_type_id']; ?>"<?php echo $selected; ?>><?php echo $import_type['import_type_name']; ?></option>
<?php
}
?>

					</select>
				</div>
				<div class="form-group">
					<label>Import pages as drafts</label>
					<select class="form-control" id="import_drafts" name="import_drafts">
						<option value="0" <?php echo $client['import_drafts'] == 0 ? 'selected' : ''; ?>>No</option>
						<option value="1" <?php echo $client['import_drafts'] == 1 ? 'selected' : ''; ?>>Yes</option>
					</select>
				</div>
			</fieldset>

			<fieldset class="col-md-6">
				<legend>Meta Information</legend>

				<div class="form-group">
					<label for="pages_per_import">Pages Per Import</label>
					<input type="text" class="form-control" id="pages_per_import" name="pages_per_import" value="<?php echo !empty($client['pages_per_import']) ? $client['pages_per_import'] : ''; ?>">
				</div>
				<div class="form-group">
					<label for="extensions">Allowed Extensions (Format: htm,html)</label>
					<input type="text" class="form-control" id="extensions" name="extensions" value="<?php echo !empty($client['extensions']) ? $client['extensions'] : ''; ?>"<?php echo $extensions_disabled; ?>>
				</div>
				<div class="form-group created-on">
					<label>Account creation date</label>
					<input type="text" class="form-control" id="created_on" value="<?php echo !empty($client['created_on']) ? $client['created_on'] : ''; ?>" disabled>
				</div>
				<div class="form-group">
					<label>Archived</label>
					<select class="form-control" id="archived" name="archived">
						<option value="0" <?php echo $client['archived'] == 0 ? 'selected' : ''; ?>>No</option>
						<option value="1" <?php echo $client['archived'] == 1 ? 'selected' : ''; ?>>Yes</option>
					</select>
				</div>
			</fieldset>
		</div>

		<div class="row">
			<fieldset class="col-md-6">
				<legend>Test Import Information</legend>

				<div class="form-group">
					<label for="import_domain">Test Domain</label>
					<input type="name" class="form-control" id="import_domain" name="import_domain" value="<?php echo !empty($client['import_domain']) ? $client['import_domain'] : 'across.mindtouch.us'; ?>">
				</div>
				<div class="form-group">
					<label for="import_path">Test Import Path</label>
					<input type="text" class="form-control" id="import_path" name="import_path" value="<?php echo !empty($client['import_path']) ? $client['import_path'] : 'Clients/'; ?>">
				</div>
			</fieldset>

			<fieldset class="col-md-6">
				<legend>Production Import Information</legend>

				<div class="form-group">
					<label for="api_url">Production Domain</label>
					<input type="name" class="form-control" id="api_url" name="api_url" value="<?php echo !empty($client['api_url']) ? $client['api_url'] : ''; ?>">
				</div>
				<div class="form-group">
					<label for="api_username">API Username</label>
					<input type="name" class="form-control" id="api_username" name="api_username" value="<?php echo !empty($client['api_username']) ? $client['api_username'] : ''; ?>">
				</div>
				<div class="form-group">
					<label for="api_password">API Password</label>
					<input type="name" class="form-control" id="api_password" name="api_password" value="<?php echo !empty($client['api_password']) ? $client['api_password'] : ''; ?>">
				</div>
				<div class="form-group">
					<label for="prod_import_path">Production Import Path</label>
					<input type="name" class="form-control" id="prod_import_path" name="prod_import_path" value="<?php echo !empty($client['prod_import_path']) ? $client['prod_import_path'] : ''; ?>">
				</div>
			</fieldset>
		</div>
