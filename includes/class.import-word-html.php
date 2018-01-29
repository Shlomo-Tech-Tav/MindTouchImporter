<?php
class ImportWordHtml extends ImportHtml {
	protected $list_classes = array(
		'MsoListNumber',
		'MsoListNumberCxSpFirst',
		'MsoListNumberCxSpMiddle',
		'MsoListNumberCxSpLast',
		'MsoListNumber2',
		'MsoListNumber3CxSpFirst',
		'MsoListNumber3CxSpMiddle',
		'MsoListNumber3CxSpLast',
		'MsoListNumber3',
		'MsoListNumber4CxSpFirst',
		'MsoListNumber4CxSpMiddle',
		'MsoListNumber4CxSpLast',
		'MsoListNumber4',
		'MsoListNumber5CxSpFirst',
		'MsoListNumber5CxSpMiddle',
		'MsoListNumber5CxSpLast',
		'MsoListNumber5',
		'MsoListParagraphCxSpFirst',
		'MsoListParagraphCxSpMiddle',
		'MsoListParagraphCxSpLast',
		'MsoListParagraph',
		'MsoList2',
		'MsoList',
		'MsoToc1',
		'ProcedureCxSpFirst',
		'ProcedureCxSpMiddle',
		'ProcedureCxSpLast'
	);

	protected $options = array(
		// Add leading zero to numbers less than 10 at the beginning of a page title.
		'add_zeros_to_titles' => true,
		// Which heading to break content on. h1 or h2.
		'break_on' => 'h1',
		// Default title to use when none found.
		'default_title' => 'NO TITLE FOUND',
		// Add leading zeros to page headings without them.
		'pad_headings' => false,
		// Remove any comments in the Word document.
		'remove_comments' => false
	);

	/**
	 * Constructs the Microsoft Word import class.
	 * @param object $Tools Content tools object.
	 */
	public function __construct($Tools) {
		parent::__construct($Tools);
	}

	/**
	 * Cleans Word HTML content.
	 * @param string $content Word HTML to clean up.
	 * @return string $content Cleaned up Word HTML.
	 */
	public function clean($content, $options = array()) {
		// Set variables.
		$this->setOptions($options);

		// Remove empties and other items that don't work well in MT.
		$remove = array(
			'emptySpans',
			'emptyParagraphs',
			'emptyDivs',
			'imageHeights',
			'imageWidths',
			'lang',
			'marginLeft',
			'position',
			'tableHeights',
			'tablePadding',
			'tableWidths',
			'noWrap',
			'textAlign',
			'textIndent',
			'fontSizes',
			'fontFaces',
			'tableBody',
			'tableHead',
			'zindex'
		);
		$content = $this->Tools->remove($content, $remove);

		if ($this->options['pad_headings']) {
			$content = $this->padHeadings($content);
		}

		return $content;
	}

	/**
	 * Converts the Word HTML into HTML pages. Stores it in pages property.
	 * @param string $import Word HTML page.
	 * @param array $options Options to control the import.
	 */
	public function convert($import, $options = array()) {
		// Set variables.
		$this->import = $import;
		$this->setOptions($options);

		// Convert to utf8.
		if (!mb_is_utf8($this->import)) {
			$this->import = convert_cp1252_to_utf8($this->import);
		}

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
			$content = $this->prepareHeadings($page->find('body'), $heading);
		} else {
			$content = $this->prepareHeadings($page, $heading);
		}

		// Deal with comments.
		if ($this->options['remove_comments']) {
			$content = $this->removeComments($content);
		}

		// Deal with any lists.
		$content = $this->convertLists($content);

		// Build data for the page.
		$page_data = array(
			'anchors' => $anchors,
			'content' => $content,
			'title' => $title
		);

		return $page_data;
	}

	/**
	 * Replaces common Word classes for bullets with actual HTML bullets.
	 * @param string $content HTML string.
	 * @return string $content HTML string.
	 */
	protected function convertLists($content) {
		// Replace all of the list classes with Procedure.
		$content = $this->replaceListClasses($content);

		// Level three list items.
		$content = str_replace(array('>–<', '>-<'), '>···<', $content);
		// Level two list items.
		$content = str_replace(array('>o<', '>§<'), '>··<', $content);

		$content = phpQuery::newDocumentHTML($content);

		// Deal with third level items.
		foreach ($content->find('p:contains("···")') as $bullet) {
			$bullet = pq($bullet);
			$class = $bullet->attr('class');
			$html = '<ul class="' . $class . '" style="list-style: none;"><li><ul style="list-style: none;"><li><ul><li>' . $bullet->html() . '</li></ul></li></ul></li></ul>';
			$bullet->replaceWith($html);
		}

		// Deal with second level items.
		foreach ($content->find('p:contains("··")') as $bullet) {
			$bullet = pq($bullet);
			$class = $bullet->attr('class');
			$html = '<ul class="' . $class . '" style="list-style: none;"><li><ul><li>' . $bullet->html() . '</li></ul></li></ul>';
			$bullet->replaceWith($html);
		}

		// Replace paragraph tags with Word bullet (·) with unordered list.
		foreach ($content->find('p:contains("·")') as $bullet) {
			$bullet = pq($bullet);
			$class = $bullet->attr('class');
			$html = '<ul class="' . $class . '"><li>' . $bullet->html() . '</li></ul>';
			$bullet->replaceWith($html);
		}

		// Build a list of the last ordered items.
		$last_bullet = '';
		$last = array();
		foreach ($content->find('.Procedure') as $index => $bullet) {
			$bullet = pq($bullet);

			// Get the number from the item.
			preg_match("/[0-9]?[0-9]?\.?/", $bullet->html(), $matches, PREG_OFFSET_CAPTURE);
			$number = (int) rtrim($matches[0][0], '.');

			// Skip any empty items.
			if (empty($number)) {
				continue;
			}

			// Record the end of the last list.
			if ($number === 1 && $index > 0) {
				$last[] = $last_bullet;
			}

			$last_bullet = $index;
		}

		// Replace the paragraph tags for ordered list items.
		foreach ($content->find('.Procedure') as $index => $bullet) {
			$bullet = pq($bullet);

			// Get the number from the item.
			preg_match("/[0-9]?[0-9]?\.?/", $bullet->html(), $matches, PREG_OFFSET_CAPTURE);
			$number = (int) rtrim($matches[0][0], '.');

			// Skip any empty items.
			if (empty($number)) {
				continue;
			}

			// Deal with the first item.
			$class = '';
			if ($number === 1) {
				$class = "numbered-first ";
			}

			// Deal with the last item.
			$append = '';
			if (in_array($index, $last)) {
				$class = 'numbered-last ';
				$append = '{numbered-last}';
			} else {
				$append = '{numbered-other}';
			}

			// Remove the number.
			if ($matches[0][1] === 0) {
				$html = substr($bullet->html(), strlen($matches[0][0]));
				$bullet->html($html);
			}

			$html = '<li class="' . $class . 'numbered">' . $bullet->html() . $append . '</li>';
			$bullet->replaceWith($html);
		}

		$content = $content->html();

		// Open ordered lists.
		$content = str_replace('<li class="numbered-first numbered"', '<ol><li class="numbered-first numbered"', $content);

		// Correct ordered list items.
		$content = str_replace('{numbered-other}</li>', '', $content);
		$content = str_replace('<li class="numbered">', '</li><li class="numbered">', $content);
		$content = str_replace('<li class="numbered-last', '</li><li class="numbered-last">', $content);

		// Close all ordered lists.
		$content = str_replace('{numbered-last}</li>', '</li></ol>', $content);

		// Remove Word bullet.
		$content = str_replace('·', '', $content);

		$content = str_replace(' style=""', '', $content);
		$content = str_replace('&nbsp;', ' ', $content);

		// Combine adjacent unordered lists.
		$content = preg_replace("/<\/ul>\s*<ul>/", '', $content);

		return $content;
	}

	/**
	 * Adds leading zeros to HTML headers without them.
	 * @param string $content Page content.
	 * @return string $content Page content.
	 */
	protected function padHeadings($content) {
		$content = phpQuery::newDocumentHTML($content);
		$headers = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');
		foreach ($headers as $header) {
			foreach ($content->find($header) as $h) {
				$h = pq($h);
				$h_text = $h->text();
				if (preg_match('/[0-9][0-9.]*/', $h_text, $matches) > 0) {
					$h_original = $matches[0];
					$h_split = explode('.', $h_original);
					foreach ($h_split as $key => $item) {
						if ((int) $item < 10 && (int) $item[0] != 0) {
							$h_split[$key] = '0' . $item;
						}
					}
					$h_new = implode('.', $h_split);
					$h_text = str_replace($h_original, $h_new, $h_text);
					$h->text($h_text);
				}
			}
		}
		return $content->html();
	}

	/**
	 * Prepares headings in the Word page content.
	 * @param object $content phpQuery object.
	 * @param string $heading What type of heading will start the page.
	 * @return string $content Word HTML string.
	 */
	protected function prepareHeadings($content, $heading = 'h1') {
		// Remove the heading so that it's not duplicating the MindTouch title.
		foreach ($content->find($heading) as $h) {
			$h = pq($h);
			$h->remove();
		}

		// Make sure the numbers don't butt up against the headlines.
		foreach ($content->find('h2 span') as $span) {
			$span = pq($span);
			$text = trim($span->text());
			if (!preg_match('/^\d+/', $text) && !empty($text) && !preg_match('/^\s+$/', $text)) {
				$span->html('&nbsp;' . $span->html());
			}
		}
		foreach ($content->find('h3 span') as $span) {
			$span = pq($span);
			$text = trim($span->text());
			if (!preg_match('/^\d+/', $text) && !empty($text) && !preg_match('/^\s+$/', $text)) {
				$span->html('&nbsp;' . $span->html());
			}
		}

		// Deal with h4 headlines.
		foreach ($content->find('.StyleHeading4TrebuchetMS10pt') as $h4) {
			$h4 = pq($h4);
			$h4_html = $h4->html();
			$h4->replaceWith('<h4>' . $h4_html . '</h4>');
		}

		$content = trim($content->html());
		$content = str_replace('       ', ' ', $content);
		$content = str_replace('      ', ' ', $content);
		$content = str_replace(' ', ' ', $content);
		$content = str_replace('&nbsp;&nbsp;', '&nbsp;', $content);
		return $content;
	}

	/**
	 * Removes Word comments.
	 * @param  string $content HTML string.
	 * @return string $content HTML string.
	 */
	protected function removeComments($content) {
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
	 * Replaces known Word bullet classes with one common one.
	 * @param string $content HTML string.
	 * @return string $content HTML string.
	 */
	protected function replaceListClasses($content) {
		foreach ($this->list_classes as $class) {
			$content = str_replace('=' . $class, '=Procedure', $content);
			$content = str_replace('="' . $class, '="Procedure', $content);
		}
		return $content;
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
		$title = str_replace('          ', ' ', $title);
		$title = str_replace(' ', ' ', $title);
		$title = trim(preg_replace('/\s+/', ' ', $title));

		// Add leading zero to numbers less than 10 at the title's start.
		if ($this->options['add_zeros_to_titles']) {
			if ($this->options['pad_headings']) {
				preg_match('/[0-9][0-9.]*/', $title, $matches);
				$title_original = $matches[0];
				$title_split = explode('.', $title_original);
				if (count($matches) > 0) {
					$number = build_version_string($title_split);
					$title = preg_replace('/[0-9][0-9.]*/', $number, $title);
				}
			} else {
				preg_match('/^\d+/', $title, $matches);
				if (count($matches) > 0) {
					$number = build_version_string($matches[0]);
					$title = preg_replace('/^\d+/', $number, $title);
				}
			}
		}

		return $title;
	}
}
