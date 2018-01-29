	<h1><?php echo $title; ?></h1>

<?php
echo $this->getHtml('content-import-results', $data);
?>

	<ul class="inline-links">
		<li><a href="<?php echo ABSURL; ?>client/<?php echo $client['code']; ?>">Choose another <?php echo $client['name']; ?> import &raquo;</a></li></li>
		<li><a href="<?php echo ABSURL; ?>">Change clients &raquo;</a></li>
	</ul>
