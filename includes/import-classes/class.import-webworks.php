<?php
class ImportWebworks extends Client {
	protected $html_options = array();

	public function buildFileNameFromPath($path) {
		// Remove special characters from the path.
		if (empty($path)) {
			return $path;
		}

		$path = str_replace('%20', ' ', $path);
		$hash_pos = strrpos($path, '#');
		if ($hash_pos > 0) {
			$path = substr($path, 0, $hash_pos);
		}
		return $this->import_dir . '/' . $this->import_file . '/' . $path;
	}

	/**
	 * Builds the page's content from the HTML file path.
	 * @param string $path HTML file path.
	 * @return string $content HTML page content.
	 */
	protected function buildPageContentFromPath($path) {
		$content = '';

		// Set any pages without an HTML file to a guide.
		if (empty($path)) {
			return $this->buildDefaultTreeContent();
		}

		// Get the file.
		$filename = $this->buildFileNameFromPath($path);
		if (file_exists($filename)) {
			$content = file_get_contents($filename);
		}
		return $content;
	}

	/**
	 * Retrieves the required information for a page.
	 * @param array $page Array containing page data.
	 * @return array $page_data Array of data for the page.
	 */
	protected function buildPageData($page) {
		// Build data for the page.
		$page_data = array(
			'content' => $this->buildPageContentFromPath($page['content_path']),
			'path' => $page['path'],
			'title' => $page['title'],
			'api_title' => $this->Api->buildPageId($page['title']),
			'content_path' => $page['content_path']
		);
		return $page_data;
	}

	protected function buildPageDataWithOptions($page, $content_path, $path) {
		$page_data = array(
			'anchors' => $page['anchors'],
			'content' => $page['content'],
			'path' => array_merge($path, array_unique($page['path'])),
			'title' => $page['title'],
			'api_title' => $this->Api->buildPageId($page['title']),
			'content_path' => $content_path
		);
		return $page_data;
	}

	/**
	 * Builds an array of information for all pages of the import.
	 */
	public function buildPagesData($file) {
		$this->pages_data = array();
		$this->pages_empty = array();
		foreach ($this->import as $index => $row) {
			// Use the import HTML class when options are set.
			if (count($this->html_options) > 0) {
				if (empty($row['content_path'])) {
					continue;
				}
				$ImportHtml = new ImportHtml($this->Tools);
				$ImportHtml->convert($this->buildPageContentFromPath($row['content_path']), $this->html_options);
				$pages = $ImportHtml->getPages();
				foreach ($pages as $page) {
					$page_data = $this->buildPageDataWithOptions($page, $row['content_path'], $row['path']);

					if (empty($page_data['content'])) {
						$this->pages_empty[] = $page_data;
						continue;
					} else {
						$this->storePageData($page_data);
					}
					$this->pages_data[] = $page_data;
				}
			} else {
				$page_data = $this->buildPageData($row);

				if (empty($page_data['content'])) {
					$this->pages_empty[] = $page_data;
					continue;
				} else {
					$this->storePageData($page_data);
				}
				$this->pages_data[] = $page_data;
			}
		}
		// xmp_print($this->pages_data);
		// exit;
	}

	/**
	 * Builds the path to the content from the href.
	 * @param string $href Content link.
	 * @return string $href Content path.
	 */
	protected function buildContentPath($href) {
		// Strip starting pound sign.
		$hrefPos = strpos($href, '#');
		if ($hrefPos === 0) {
			$href = substr($href, $hrefPos + 1);
		}

		// Strip %3FTocPath.
		$hrefPos = strpos($href, '%3FTocPath');
		if ($hrefPos !== false) {
			$href = substr($href, 0, $hrefPos);
		}

		return $href;
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
	 * Flattens the array created from the JSON hierarchy.
	 * @return array $json_array Flattened hierarchy array.
	 */
	public function flattenJsonHierarchy() {
		$json_array = array();
		foreach ($this->import as $index => $row) {
			$this->flattenJsonHierarchyRow($json_array, $row);
		}
		return $json_array;
	}

	/**
	 * Recursive function that handles each entry of the JSON hierarchy array.
	 * @param array $json_array Flattened hierarchy array.
	 * @param array $row Page from JSON hierarchy array.
	 * @param array $path Array containing previous path, if any.
	 */
	protected function flattenJsonHierarchyRow(&$json_array, $row, $path = array()) {
		$title = $row['link'];
		$content_path = $this->buildContentPath($row['href']);

		$json_array[] = array(
			'title' => $title,
			'content_path' => $content_path,
			'path' => $path
		);

		if (!empty($row['children'])) {
			$path[] = $title;
			foreach ($row['children'] as $childKey => $child) {
				$this->flattenJsonHierarchyRow($json_array, $child, $path);
			}
		}
	}

	/**
	 * Convert the JSON import to a PHP array after the parse function loads it.
	 */
	protected function parseAfterSetFile() {
		$this->importLoadJson();

		// Flatten the Json array.
		$this->import = $this->flattenJsonHierarchy();
	}

	/**
	 * Creates array mapping old URIs to new from the json array.
	 * @param string $file Name of file to import.
	 * @return array $pages_links Array of URIs.
	 */
	protected function parsePageLinks($file) {
		// Build array out of json array.
		$pages_links = array();

		foreach ($this->import as $page) {
			// Build the page ID.
			$path = '';
			if (count($page['path']) > 0) {
				foreach ($page['path'] as $key => $page_path) {
					$page['path'][$key] = $page_path;
				}
				$path .= '/' . join('/', $page['path']);
			}
			$title = rawurlencode($this->Api->escapeSlashesPageId($page['title']));
			$url_parts = pathinfo(parse_url($page['content_path'], PHP_URL_PATH));
			$pages_links[$url_parts['basename']] = $path . '/' . $title;
		}

		// Deal with anchors from breaking apart the HTML files.
		if (count($this->html_options) > 0) {
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

				// Get the source content file name.
				$content_file = pathinfo($page['content_path']);

				// Add each of the anchors.
				foreach ($page['anchors'] as $anchor) {
					$pages_links[$content_file['basename'] . '#' . $anchor] = $path . '/' . $title . '#' . $anchor;
				}
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
	 * Creates and sets any tags for the import.
	 * @param array $process_import_data Data for the import.
	 */
	protected function processImportTags(&$process_import_data) {
		// Extract data.
		extract($process_import_data, EXTR_REFS);

		// Prepare tags.
		$page['tags'] = array();
		if (empty($page['content_path'])) {
			$page['tags'][] = 'article:topic-guide';
		} else {
			$page['tags'][] = 'article:topic';
		}

		// Add the tags.
		$this->Api->pageTagsSet($page_id, $page['tags']);
	}
}
