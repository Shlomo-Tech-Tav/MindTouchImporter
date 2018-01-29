<?php
class View {
	protected $path = array();

	/**
	 * Instantiates the object and sets the default path.
	 */
	public function __construct() {
		$this->addToPath(ABSPATH . 'templates');
	}

	/**
	 * Adds directories where templates can be stored.
	 * @param string $path Directory where templates could be.
	 */
	public function addToPath($path) {
		$this->path[] = $path;
	}

	/**
	 * Builds a nested list from a tree array.
	 * @param array $tree Array of items for the nested list.
	 * @param array $options Options for displaying the list.
	 * @param string $prefix Current path.
	 * @param integer $level Number of levels the function is currently on.
	 * @return string Nested HTML list.
	 */
	public function buildList($tree, $options = array(), $prefix = '', $level = 1) {
		$ul = '';
		foreach ($tree as $key => $value) {
			$li = '';
			if (is_array($value)) {
				if (array_key_exists('title', $value)) {
					$li .= htmlspecialchars($value['title']);
				} else {
					$li .= $key;
				}
				$li .= $this->buildList($value, $options, "$prefix$key/", $level + 1);
				$ul .= strlen($li) ? $this->buildTabs($level + 1) . "<li>$li</li>\n" : '';
			}
		}

		if ($level === 1) {
			$class = !empty($options['class']) ? $options['class'] : '';
		} else {
			$class = '';
		}
		return strlen($ul) ? "\n" . $this->buildTabs($level) . '<ul class="' . $class . '"' . ">\n$ul\n" . $this->buildTabs($level) . "</ul>\n" : '';
	}

	/**
	 * Returns a string with the number of tabs given.
	 * @param integer $number Number of tabs to return.
	 * @return string String with number of tabs requested.
	 */
	protected function buildTabs($number) {
		ob_start();
		for ($i = 0; $i < $number; $i ++) {
			echo "\t";
		}
		return ob_get_clean();
	}

	/**
	 * Returns the HTML of the given template.
	 * @param string $template Name of template to use.
	 * @param array  $data Data for the template.
	 * @param string $title Main heading for the page.
	 * @return string $content HTML for the template.
	 */
	public function getHtml($template, $data = array(), $title = '') {
		// Find the template file.
		$template = $this->findTemplate($template);

		// Prepare data for the template.
		extract($data);

		// Load the template and return the data.
		ob_start();
		include($template);
		return ob_get_clean();
	}

	/**
	 * Encodes the data as a JSON string.
	 * @param array $data Data to return in JSON string.
	 * @return string $json
	 */
	public function getJson($data) {
		return json_encode($data);
	}

	/**
	 * Searches the paths for the template.
	 * @param string $template Name of template file to find.
	 * @return string $template Full path and file name of the template.
	 */
	protected function findTemplate($template) {
		// Reverse the array to prioritize later entries.
		$path = array_reverse($this->path);

		// Iterate through the path array.
		foreach ($path as $template_path) {
			if (file_exists($template_path . '/' . $template . '.php')) {
				$template = $template_path . '/' . $template . '.php';
				break;
			}
		}

		return $template;
	}

}
