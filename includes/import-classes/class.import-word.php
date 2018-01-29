<?php
class ImportWord extends Client {
	protected $parent = array();

	/**
	 * Retrieves the required information from a table's row.
	 * @param string $page HTML for the page.
	 * @param string $page_order Tells whether the page is the first or last.
	 * @return array $page_data Array of data for the page.
	 */
	protected function buildPageData($page, $page_order = '', $script = '', $style = '') {
		// Get the data for each page.
		if ($page_order === 'first') {
			// The first item is different.
			$this->setPageParent($page['title'], $script, $style);

			// Get the title and content.
			$title = $this->parent['title'];
		} else {
			// Get title and content.
			$title = $page['title'];
		}

		// Build data for the page.
		$page_data = array(
			'anchors' => $page['anchors'],
			'content' => $page['content'],
			'path' => $this->buildPagePath($title, $page['path']),
			'title' => $title,
			'api_title' => $this->Api->buildPageId($title)
		);

		return $page_data;
	}

	/**
	 * Builds the MindTouch path for the page.
	 * @param array $path Page's current path.
	 * @param string $level_one Level one title.
	 * @param string $level_two Level two title.
	 * @param string $level_three Level three title.
	 * @return array $path Returns page path.
	 */
	protected function buildPagePath($title, $path) {
		// Make sure the current page isn't in the path array.
		if (in_array($title, $path)) {
			$title_key = array_search($title, $path);
			unset($path[$title_key]);
			$path = array_values($path);
		}
		$path = array_unique($path);

		return $path;
	}

	/**
	 * Builds an array of information for all pages of the import.
	 * @param string $file Name of file being imported.
	 */
	public function buildPagesData($file) {
		// Replace non-breaking spaces.
		$this->import = file_get_contents($this->import_dir . '/' . $this->import_files[$this->import_file]);
		$this->import = str_replace('&nbsp;', ' ', $this->import);

		$OpenOffice = new ImportOpenofficeHtml($this->Tools);
		$openoffice_options = array(
			'add_zeros_to_titles' => false,
			'break_on' => 'h2',
			'default_title' => 'Import: ' . $this->import_file,
			'remove_comments' => true,
			'remove_footer' => true,
			'remove_header' => true
		);
		$OpenOffice->convert($this->import, $openoffice_options);
		$this->import = $OpenOffice->getPages();

		// Set initial page data.
		$i = 1;
		$this->pages_data = array();
		$this->pages_empty = array();

		// Iterate through each page and prepare data.
		foreach ($this->import as $page) {
			$page_order = ($i === 1) ? 'first' : '';
			if ($i === 1) {
				$page_order = 'first';
			} elseif ($i === $pages_length) {
				$page_order = 'last';
			}
			$page_data = $this->buildPageData($page, $page_order, $OpenOffice->getScript(), $OpenOffice->getStyle());

			// Skip empty titles and content.
			if ($this->isEmptyHtml($page_data['title'], $page_data['content'])) {
				$this->pages_empty[] = $page_data;
			} else {
				$this->storePageData($page_data);
				$this->pages_data[] = $page_data;
			}
			$i ++;
		}
	}

	/**
	 * Returns the number of pages to be imported.
	 * @return integer $count
	 */
	protected function countImportPages() {
		return count($this->pages_data);
	}

	/**
	 * Returns the total number of pages in the import.
	 * @return integer $count
	 */
	protected function countTotalPages() {
		return count($this->import);
	}

	/**
	 * Checks the title and content to see if the page is empty.
	 * @param string $title Page title.
	 * @param string $content Page content.
	 * @return boolean True when content is empty.
	 */
	protected function isEmptyHtml($title, $content) {
		// Check the title.
		if (empty($title) || preg_match('/^\s+$/', $title)) {
			return true;
		}

		// Check the content.
		if (empty($content)) {
			return true;
		}
		$content = phpQuery::newDocumentHTML($content);

		// Check for images.
		$images = $content->find('img');
		if (count($images) < 1 && preg_match('/^\s+$/', $content->text())) {
			return true;
		}

		return false;
	}

	/**
	 * Converts any hidden content into MindTouch conditional content.
	 * @param string $content Page content.
	 * @return string $content Page content.
	 */
	protected function makeHiddenContentConditional($content) {
		$content = phpQuery::newDocumentHTML($content);

		// Deal with the hidden spans first.
		foreach ($content->find("span[style]") as $style) {
			$style = pq($style);

			// Skip when the item is not hidden.
			if (stripos($style->attr('style'), 'display:none') === false) {
				continue;
			}

			// Get the parent paragraph.
			$parent = $style->parents('p');
			$parent->attr('style', 'display:none');

			// Remove the style.
			$style->removeAttr('style');
		}

		// Add the conditional div.
		foreach ($content->find("p[style]") as $p) {
			$p = pq($p);

			// Skip when the item is not hidden.
			if (stripos($p->attr('style'), 'display:none') === false) {
				continue;
			}

			// Remove the style.
			$p->removeAttr('style');

			// Surround the item with the MindTouch conditional div.
			$html = '<div class="mt-style-conditional style-wrap" if="user.seated" title="Conditional Text (Pro-Member only)">' . $p->htmlOuter() . '</div>';
			$p->replaceWith($html);
		}

		return $content->html();
	}

	/**
	 * Sets the page parent array.
	 * @param string $title Title of the parent page.
	 * @param string $script Any script tags in the parent.
	 * @param string $style Any style information in the parent.
	 */
	protected function setPageParent($title, $script = '', $style = '') {
		$this->parent = array(
			'title' => $title,
			'script' => $script,
			'style' => $style
		);
	}

	/**
	 * Creates array mapping old anchor names to new MindTouch locations.
	 * @param string $file Name of file to import.
	 * @return array $pages_links Array of URIs.
	 */
	protected function parsePageLinks($file) {
		$pages_links = array();

		foreach ($this->pages_data as $page) {
			// Don't bother with the array when there are no anchor names.
			if (count($page['anchors']) < 1) {
				continue;
			}

			// Build the page ID.
			$path = '';
			if (count($page['path']) > 0) {
				foreach ($page['path'] as $key => $page_path) {
					$page['path'][$key] = $this->Api->escapeSlashesPageId($page_path);
				}
				$path .= '/' . join('/', $page['path']);
			}
			$title = rawurlencode($this->Api->escapeSlashesPageId($page['title']));

			// Add each of the anchors.
			foreach ($page['anchors'] as $anchor) {
				$pages_links['#' . $anchor] = $path . '/' . $title . '#' . $anchor;
			}
		}

		return $pages_links;
	}

	/**
	 * Sets the order of the page.
	 * @param array $process_import_data Data for the import.
	 */
	protected function processImportOrder(&$process_import_data) {
		// Extract data.
		extract($process_import_data, EXTR_REFS);

		// Get the previous page's numerical ID.
		if (count($successes) > 0) {
			// The last element of the successes array is this page.
			// Get the one before that.
			end($successes);
			$last = prev($successes);
			$output = $this->Api->pageOrderPut($page_id, $last['id']);
		}
	}

	/**
	 * Creates any parent pages for the import.
	 * @param array $process_import_data Data for the import.
	 */
	protected function processImportParents(&$process_import_data) {
		// Extract data.
		extract($process_import_data, EXTR_REFS);

		// Check the parents.
		$path = $this->config['import_path'];
		foreach ($page['path'] as $parent_path) {
			if (empty($parent_path)) {
				continue;
			}

			$page_id = $this->Api->buildPageId($parent_path, $path);
			if (!$this->Api->pageExists($page_id)) {
				$this->Api->pageCreate($page_id, $this->buildDefaultGuideContent());
				$tags = array(
					'article:topic'
				);
				$this->Api->pageTagsSet($page_id, $tags);
			}

			// Update the path for the next item.
			$path .= '/' . $this->Api->escapeSlashesPageId($parent_path);
		}
	}

	/**
	 * Creates and sets any tags for the import.
	 * @param array $process_import_data Data for the import.
	 */
	protected function processImportTags(&$process_import_data) {
		// Extract data.
		extract($process_import_data, EXTR_REFS);

		// Prepare tags.
		$page['tags'] = array();
		if (count($page['path']) > 0) {
			$page['tags'][] = 'article:topic';
		} else {
			$page['tags'][] = 'article:topic-guide';
		}

		// Add the tags.
		$this->Api->pageTagsSet($page_id, $page['tags']);
	}

	/**
	 * Creates any extra data for use during the import process.
	 * @param string $file File being imported.
	 * @return array $extra Array of extra data.
	 */
	protected function processLoadExtra($file) {
		$extra = array(
			'remove_dimensions' => true
		);
		return $extra;
	}
}
