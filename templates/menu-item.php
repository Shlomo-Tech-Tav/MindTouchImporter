<?php
// Deal with child properties.
$icon_after = '';
$icon_before = '';
$target = '';
if (!empty($item['target'])) {
	$target = ' target="' . $item['target'] . '"';
	$icon_after = ' <span class="glyphicon glyphicon-new-window"></span>';
}
if (!empty($item['glyphicon'])) {
	$icon_before = '<span class="glyphicon ' . $item['glyphicon'] . '"></span> ';
}
?>
					<li class="<?php echo $active; ?>"><a href="<?php echo $item['url']; ?>"<?php echo $target; ?>><?php echo  $icon_before . $name . $icon_after; ?></a></li>
