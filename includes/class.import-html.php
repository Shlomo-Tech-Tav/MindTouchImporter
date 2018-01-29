<?php
class ImportHtml {
	public $import;
	protected $options = array(
		// Add leading zero to numbers less than 10 at the beginning of a page title.
		'add_zeros_to_titles' => true,
		// Which heading to break content on. h1 or h2.
		'break_on' => 'h1',
		// Default title to use when none found.
		'default_title' => 'NO TITLE FOUND',
		// Add leading zeros to page headings without them.
		'pad_headings' => false
	);
	protected $pages = array();
	protected $parent = '';
	protected $script = '';
	protected $style = '';
	protected $Tools;

	/**
	 * Constructs the Microsoft Word import class.
	 * @param object $Tools Content tools object.
	 */
	public function __construct($Tools) {
		$this->Tools = $Tools;
	}

	/**
	 * Converts the HTML into HTML pages. Stores it in pages property.
	 * @param string $import HTML page.
	 * @param array $options Options to control the import.
	 */
	public function convert($import, $options = array()) {
		// Load the HTML into phpQuery to standardise the HTML tags.
		$import = phpQuery::newDocumentHTML($import);
		$this->import = $import->html();
		unset($import);

		// Set options.
		$this->setOptions($options);

		// Sometimes, no h1 is in the page. Just include the whole page.
		$first_h1 = stripos($this->import, '<h1');
		if ($first_h1 === false) {
			$this->import = array(
				$this->import
			);
		} else {
			// Break apart based on the h1.
			$this->import = preg_split("/<h1/i", $this->import);
		}

		// Set initial page data.
		$i = 1;
		$page_total = count($this->import);
		$this->pages = array();

		// Iterate through each page and prepare data.
		foreach ($this->import as $page) {
			$h2_pages = array();
			// Expand the array for h2s.
			if ($this->options['break_on'] === 'h2' || $this->options['break_on'] === 'h3') {
				$first_h2 = stripos($page, '<h2');
				if ($first_h2 !== false) {
					// Get the sub pages from the page.
					$h2_pages = preg_split("/<h2/i", $page);

					// The first array item will always be encompassed by the h1 page. Remove it.
					array_shift($h2_pages);

					// Remove the h2 content from the h1 page.
					$page = substr($page, 0, $first_h2);
				}
			}

			// Deal with the page order.
			$page_order = $this->determinePageOrder($i, $page_total, 'h1');

			// Add the '<h1' back to all but the first item.
			if ($page_order !== 'first') {
				$page = '<h1' . $page;
			}

			// Get the page data.
			$converted_page = $this->convertPage($page, $page_order, 'h1');
			$converted_page['path'] = $this->preparePath($converted_page['title'], $page_order);
			$this->pages[] = $converted_page;

			// Deal with h2 sub pages.
			if ($this->options['break_on'] === 'h2' || $this->options['break_on'] === 'h3') {
				// xmp_print($h2_pages, 'h2_pages');
				$h2_i = 1;
				$h2_page_total = count($h2_pages);
				foreach ($h2_pages as $h2_page) {
					$h3_pages = array();
					// Expand the array for h3s.
					if ($this->options['break_on'] === 'h3') {
						$first_h3 = stripos($h2_page, '<h3');
						if ($first_h3 !== false) {
							// Get the sub pages from the page.
							$h3_pages = preg_split("/<h3/i", $h2_page);

							// The first array item will always be encompassed by the h2 page. Remove it.
							array_shift($h3_pages);

							// Remove the h3 content from the h2 page.
							$h2_page = substr($h2_page, 0, $first_h3);
						}
					}

					// Deal with the page order.
					$page_order = $this->determinePageOrder($h2_i, $h2_page_total, 'h2');

					// Add the '<h2' back to all but the first item.
					if ($page_order !== 'first') {
						$h2_page = '<h2' . $h2_page;
					}

					// Get the page data.
					$converted_h2_page = $this->convertPage($h2_page, $page_order, 'h2');
					$converted_h2_page['path'] = $this->preparePath($converted_h2_page['title'], $page_order, $converted_page['title']);
					$this->pages[] = $converted_h2_page;

					// Deal with h3 sub pages.
					if ($this->options['break_on'] === 'h3') {
						$h3_i = 1;
						$h3_page_total = count($h3_page);
						foreach ($h3_pages as $h3_page) {
							// Deal with the page order.
							$page_order = $this->determinePageOrder($h3_i, $h3_page_total, 'h3');

							// Add the '<h3' back to all but the first item.
							if ($page_order !== 'first') {
								$h3_page = '<h3' . $h3_page;
							}

							// Get the page data.
							$converted_h3_page = $this->convertPage($h3_page, $page_order, 'h3');
							$converted_h3_page['path'] = $this->preparePath($converted_h3_page['title'], $page_order, array($converted_page['title'], $converted_h2_page['title']));
							$this->pages[] = $converted_h3_page;

							$h3_i ++;
						}
					}

					$h2_i ++;
				}
			}

			$i ++;
		}
	}

	/**
	 * Retrieves the required information from a table's row.
	 * @param string $page HTML for the page.
	 * @param string $page_order Tells whether the page is the first or last.
	 * @param string $heading What type of heading will start the page.
	 * @return array $page_data Array of data for the page.
	 */
	protected function convertPage($page, $page_order, $heading = 'h1') {
		if ($page_order !== 'first' && $page_order === 'last') {
			$page = str_replace(array('</body>', '</html>'), '', $page);
		}

		// Load into PHP Query.
		$page = phpQuery::newDocumentHTML($page);

		// Store any script and styles.
		if ($page_order === 'first') {
			$this->setScriptAndStyle($page);
		}

		// Get the data for each page.
		$title = $this->titleParse($page);

		// Get any anchors before the content is altered.
		$anchors = $this->parseAnchors($page);

		// The first item is different.
		if ($page_order === 'first') {
			// Set parent.
			$this->parent = $title;
			$content = $this->prepareHeadings($page->find('body'), $title, $heading);
		} else {
			$content = $this->prepareHeadings($page, $title, $heading);
		}
		unset($page);

		// Build data for the page.
		$page_data = array(
			'anchors' => $anchors,
			'content' => $content,
			'title' => $title
		);

		return $page_data;
	}

	/**
	 * Returns the page order for the page.
	 * @param integer $number Number of the page.
	 * @param integer $total Total number of pages.
	 * @param string $heading What type of heading will start the page.
	 * @return string $page_order Textual description of the page's order.
	 */
	protected function determinePageOrder($number, $total, $heading = 'h1') {
		$page_order = '';
		if ($number === 1 && $heading === 'h1') {
			$page_order = 'first';
		} elseif ($number !== 1 && $number === $total) {
			$page_order = 'last';
		}

		return $page_order;
	}

	/**
	 * Returns the pages array.
	 * @return array $pages Pages of Word HTML.
	 */
	public function getPages() {
		return $this->pages;
	}

	/**
	 * Returns script in head.
	 * @return string $script Script tags found in head of Word HTML page.
	 */
	public function getScript() {
		return $this->script;
	}

	/**
	 * Returns style in head.
	 * @return string $style Styles found in head of Word HTML page.
	 */
	public function getStyle() {
		return $this->style;
	}

	/**
	 * Returns any anchor names in the page.
	 * @param object $page phpQuery object.
	 * @return array $anchors Array of unique anchor names.
	 */
	protected function parseAnchors($page) {
		$anchors = array();
		foreach ($page->find('a[name]') as $a) {
			$a = pq($a);
			$anchors[] = $a->attr('name');
		}
		foreach ($page->find('[id]') as $id) {
			$id = pq($id);
			$anchors[] = $id->attr('id');
		}

		$anchors = array_unique($anchors);
		sort($anchors);
		return $anchors;
	}

	/**
	 * Prepares headings in the Word page content.
	 * @param object $content phpQuery object.
	 * @param string $heading What type of heading will start the page.
	 * @return string $content Word HTML string.
	 */
	protected function prepareHeadings($content, $title, $heading = 'h1') {
		// Remove the heading so that it's not duplicating the MindTouch title.
		foreach ($content->find($heading) as $h) {
			$h = pq($h);
			if (trim($h->text()) == $title) {
				$h->remove();
			}
		}
		unset($h);

		$content = trim($content->html());
		return $content;
	}

	/**
	 * Builds the path array for the page.
	 * @param string $title Title of the current page.
	 * @param string $page_order Whether the page is the first or last.
	 * @param string $parent If the page has a parent in addition to the stored parent.
	 * @return array $path Array of pages the page is a child of.
	 */
	protected function preparePath($title, $page_order, $parent = '') {
		$path = array();
		if ($title !== $this->parent) {
			$path[] = $this->parent;
		}
		if (!empty($parent)) {
			if (is_array($parent)) {
				foreach ($parent as $parent_path) {
					$path[] = $parent_path;
				}
			} else {
				$path[] = $parent;
			}
		}
		return $path;
	}

	/**
	 * Replaces the surrounding HTML element for the given CSS class with the given HTML element.
	 * Example: replaceClasses($content, array('h1-head', 'h1'))
	 * 	This call would search the $content for any items with a class of h1-head and replace the
	 * 	HTML element with an h1.
	 * @param string $content HTML string.
	 * @param array $search_replace Array with the CSS class to search for as a key and the HTML element to replace with as a value.
	 * @return string $content HTML string.
	 */
	public function replaceClasses($content, $search_replace) {
		$content = phpQuery::newDocumentHTML($content);

		foreach ($search_replace as $search => $replace) {
			foreach ($content->find('.' . $search) as $h) {
				$h = pq($h);
				$html = '<' . $replace . ' class="' . $search .'">' . $h->html() . '</h1>';
				$h->replaceWith($html);
			}
		}

		return $content->html();
	}

	/**
	 * Sets the Word HTML import options.
	 * @param array $options Options to set.
	 * @return boolean True on success.
	 */
	protected function setOptions($options) {
		foreach ($options as $option => $value) {
			if (!array_key_exists($option, $this->options)) {
				return false;
			}
			$this->options[$option] = $value;
		}
		return true;
	}

	/**
	 * Sets script and style from head of Word HTML page.
	 * @param object &$import phpQuery object.
	 */
	protected function setScriptAndStyle(&$import) {
		$this->script = $import->find('script')->text();
		$this->style = $import->find('style')->text();
	}

	/**
	 * Builds the title for the page.
	 * @param object $page phpQuery object for the page.
	 * @return string $title Title of the page.
	 */
	protected function titleParse(&$page) {
		// Check the title tag first.
		$title = $page->find('title')->text();

		// Check the h1 next.
		if (empty($title)) {
			$title = $page->find('h1')->text();
		}

		// Check the h2 next.
		if (empty($title)) {
			$title = $page->find('h2')->text();
		}

		// Check the h3 next.
		if (empty($title)) {
			$title = $page->find('h3')->text();
		}

		// Add warning about title when none found.
		if (empty($title)) {
			$title = $this->options['default_title'];
		}

		// Deal with white space.
		$title = trim(preg_replace('/\s+/', ' ', $title));

		// Add leading zero to numbers less than 10 at the title's start.
		if ($this->options['add_zeros_to_titles']) {
			preg_match('/^\d+/', $title, $matches);
			if (count($matches) > 0) {
				$number = build_version_string($matches[0]);
				$title = preg_replace('/^\d+/', $number, $title);
			}
		}

		return $title;
	}
}
