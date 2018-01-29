<?php
class ImportOpenofficeHtml extends ImportHtml {
	protected $options = array(
		// Add leading zero to numbers less than 10 at the beginning of a page title.
		'add_zeros_to_titles' => true,
		// Which heading to break content on. h1 or h2.
		'break_on' => 'h1',
		// Default title to use when none found.
		'default_title' => 'NO TITLE FOUND',
		// Add leading zeros to page headings without them.
		'pad_headings' => false,
		// Remove any comments in the document.
		'remove_comments' => false,
		// Remove the footer HTML from the page.
		'remove_footer' => true,
		// Remove the header HTML from the page.
		'remove_header' => true
	);

	/**
	 * Constructs the Open Office import class.
	 * @param object $Tools Content tools object.
	 */
	public function __construct($Tools) {
		parent::__construct($Tools);
	}

	/**
	 * Converts the Open Office HTML into HTML pages. Stores it in pages property.
	 * @param string $import Open Office HTML page.
	 * @param array $options Options to control the import.
	 */
	public function convert($import, $options = array()) {
		// Set variables.
		$this->import = $import;
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
			$sub_import = array();
			// Expand the array for h2s.
			if ($this->options['break_on'] === 'h2') {
				$first_h2 = stripos($page, '<h2');
				if ($first_h2 !== false) {
					// Get the sub pages from the page.
					$sub_import = preg_split("/<h2/i", $page);

					// The first array item will always be encompassed by the h1 page. Remove it.
					array_shift($sub_import);

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
			if ($this->options['break_on'] === 'h2') {
				$s = 1;
				$sub_page_total = count($sub_page);
				foreach ($sub_import as $sub_page) {
					// Deal with the page order.
					$page_order = $this->determinePageOrder($s, $sub_page_total, 'h2');

					// Add the '<h2' back to all but the first item.
					if ($page_order !== 'first') {
						$sub_page = '<h2' . $sub_page;
					}

					// Get the page data.
					$converted_sub_page = $this->convertPage($sub_page, $page_order, 'h2');
					$converted_sub_page['path'] = $this->preparePath($converted_sub_page['title'], $page_order, $converted_page['title']);
					$this->pages[] = $converted_sub_page;

					$s ++;
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
			$content = $this->prepareHeadings($page->find('body'), $heading);
		} else {
			$content = $this->prepareHeadings($page, $heading);
		}

		// Deal with header.
		if ($this->options['remove_header']) {
			$content = $this->removeHeader($content);
		}

		// Deal with footer.
		if ($this->options['remove_footer']) {
			$content = $this->removeFooter($content);
		}

		// Deal with comments.
		if ($this->options['remove_comments']) {
			$content = $this->removeComments($content);
		}

		// Build data for the page.
		$page_data = array(
			'anchors' => $anchors,
			'content' => $content,
			'title' => $title
		);

		return $page_data;
	}

	/**
	 * Removes Word comments.
	 * @param  string $content HTML string.
	 * @return string $content HTML string.
	 */
	protected function removeComments($content) {
		return $content;
		$content = phpQuery::newDocumentHTML($content);

		// Remove classes associated with comments.
		foreach ($content->find('.MsoCommentText') as $comment) {
			$comment = pq($comment);
			$comment->remove();
		}
		foreach ($content->find('.msocomanchor') as $comment) {
			$comment = pq($comment);
			$comment->remove();
		}

		return $content->html();
	}

	/**
	 * Removes Open Office footer.
	 * @param  string $content HTML string.
	 * @return string $content HTML string.
	 */
	protected function removeFooter($content) {
		$content = phpQuery::newDocumentHTML($content);

		// Remove items associated with the footer.
		foreach ($content->find('div[type=FOOTER]') as $footer) {
			$footer = pq($footer);
			$footer->remove();
		}
		foreach ($content->find('div[type=footer]') as $footer) {
			$footer = pq($footer);
			$footer->remove();
		}

		return $content->html();
	}

	/**
	 * Removes Open Office header.
	 * @param  string $content HTML string.
	 * @return string $content HTML string.
	 */
	protected function removeHeader($content) {
		$content = phpQuery::newDocumentHTML($content);

		// Remove items associated with the header.
		foreach ($content->find('div[type=HEADER]') as $header) {
			$header = pq($header);
			$header->remove();
		}
		foreach ($content->find('div[type=header]') as $header) {
			$header = pq($header);
			$header->remove();
		}

		return $content->html();
	}
}
