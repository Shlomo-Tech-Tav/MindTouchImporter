				<ul class="nav navbar-nav">
<?php
$items_right = array();
foreach ($items as $name => $item) {
	// Move right-aligned items to own array.
	if (!empty($item['navbar']) && $item['navbar'] === 'right') {
		$items_right[$name] = $item;
		continue;
	}

	// Deal with the active class.
	$active_class = '';
	if ($active === $name) {
		$active_class = ' active';
	}

	// These items have a second level of links. Create the dropdown.
	if (!empty($item['_url'])) {

?>
					<li class="dropdown<?php echo $active_class; ?>"><a href="<?php echo $item['_url']; ?>" data-toggle="dropdown"><?php echo $name; ?> <b class="caret"></b></a>
						<ul class="dropdown-menu <?php echo slugify($name); ?>">
<?php
		foreach ($item as $sub_name => $sub_url) {
			// Skip parent properties.
			if (strpos($sub_name, '_') === 0) {
				continue;
			}

?>
							<li><a href="<?php echo $sub_url; ?>"><?php echo $sub_name; ?><?php echo strpos($sub_name, '|'); ?></a></li>
<?php
		}
?>
						</ul>
					</li>
<?php
	} else {
		$data = array(
			'active' => $active_class,
			'item' => $item,
			'name' => $name
		);
		echo $this->getHtml('menu-item', $data);
	}
}
?>
				</ul>

<?php
if (count($items_right) > 0) {
?>
				<ul class="nav navbar-nav navbar-right">
<?php
	foreach ($items_right as $name => $item) {
		// Deal with the active class.
		$active_class = '';
	if ($active === $name) {
		$active_class = ' active';
	}

		// These items have a second level of links. Create the dropdown.
		if (!empty($item['_url'])) {
			if (!empty($item['glyphicon'])) {
				$icon_before = '<span class="glyphicon ' . $item['glyphicon'] . '"></span> ';
			}
?>
					<li class="dropdown<?php echo $active_class; ?>"><a href="<?php echo $item['_url']; ?>" data-toggle="dropdown"><?php echo $icon_before . $name; ?> <b class="caret"></b></a>
						<ul class="dropdown-menu">
<?php
			foreach ($item as $sub_name => $sub_url) {
				// Skip parent properties.
				if (strpos($sub_name, '_') === 0) {
					continue;
				}
				if ($sub_name === 'navbar' || $sub_name === 'glyphicon') {
					continue;
				}

?>
							<li><a href="<?php echo $sub_url; ?>"><?php echo $sub_name; ?><?php echo strpos($sub_name, '|'); ?></a></li>
<?php
			}
?>
						</ul>
					</li>
<?php
		} else {
			$data = array(
				'active' => $active_class,
				'item' => $item,
				'name' => $name
			);
			echo $this->getHtml('menu-item', $data);
		}
	}	
?>
				</ul>
<?php
}
?>
