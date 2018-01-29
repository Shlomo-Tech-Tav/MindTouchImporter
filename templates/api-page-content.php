<h2>Overview</h2>
<p><?php echo $description; ?></p>
<ul>
	<li><strong>REST Method:</strong> <?php echo $rest_method; ?></li>
	<li><strong>Method Access:</strong> <?php echo $access; ?></li>
</ul>

<?php
if (count($params['uri']) > 0) {
?>
<h2>Uri Parameters</h2>
<table style="width: 100%;" border="1" cellpadding="1" cellspacing="0">
	<tbody>
		<tr style="text-align: left; vertical-align: top; background-image: none; background-color: #e1e1e1;">
			<td><strong>Name</strong></td>
			<td><strong>Type</strong></td>
			<td><strong>Description</strong></td>
		</tr>
<?php
	foreach ($params['uri'] as $param) {
?>
		<tr style="text-align: left; vertical-align: top; background-image: none;">
			<td><?php echo $param['name']; ?></td>
			<td><?php echo $param['valuetype']; ?></td>
			<td><?php echo $param['description']; ?></td>
		</tr>
<?php	
	}
?>
	</tbody>
</table>
<?php
}

if (count($params['query']) > 0) {
?>

<h2>Query Parameters</h2>
<table style="width: 100%;" border="1" cellpadding="1" cellspacing="0">
	<tbody>
		<tr style="text-align: left; vertical-align: top; background-image: none; background-color: #e1e1e1;">
			<td><strong>Name</strong></td>
			<td><strong>Type</strong></td>
			<td><strong>Description</strong></td>
		</tr>
<?php
	foreach ($params['query'] as $param) {
?>
		<tr style="text-align: left; vertical-align: top; background-image: none;">
			<td><?php echo $param['name']; ?></td>
			<td><?php echo $param['valuetype']; ?></td>
			<td><?php echo $param['description']; ?></td>
		</tr>
<?php	
	}
?>
	</tbody>
</table>
<?php
}

if (count($statuses) > 0) {
?>

<h2>Return Codes</h2>
<table style="width: 100%;" border="1" cellpadding="1" cellspacing="0">
	<tbody>
		<tr style="text-align: left; vertical-align: top; background-image: none; background-color: #e1e1e1;">
			<td><strong>Name</strong></td>
			<td><strong>Value</strong></td>
			<td><strong>Description</strong></td>
		</tr>
<?php
	foreach ($statuses as $status) {
?>
		<tr style="text-align: left; vertical-align: top; background-image: none;">
			<td><?php echo $status['name']; ?></td>
			<td><?php echo $status['code']; ?></td>
			<td><?php echo $status['description']; ?></td>
		</tr>
<?php	
	}
?>
	</tbody>
</table>
<?php
}