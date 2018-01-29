<?php
class eci extends Client {

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
		$filename = $this->import_dir . '/' . $this->import_file . '_files/' . $path;
		if (file_exists($filename)) {
			$content = file_get_contents($filename);
			$content = str_replace('.picarea', ' class="picarea"', $content);
		}
		return $content;
	}

	/**
	 * Retrieves the required information for a page.
	 * @param array $page Array containing page data.
	 * @return array $page_data Array of data for the page.
	 */
	protected function buildPageData($page, $file) {
		// Build data for the page.
		array_unshift($page['path'], $file);
		$page_data = array(
			'content' => $this->buildPageContentFromPath($page['content_path']),
			'path' => $page['path'],
			'tags' => $this->buildPageTags($page['path'], $page['title']),
			'title' => $page['title'],
			'api_title' => $this->Api->buildPageId($page['title']),
			'content_path' => $page['content_path']
		);
		return $page_data;
	}

	/**
	 * Builds an array of information for all pages of the import.
	 */
	public function buildPagesData($file) {
		$this->pages_data = array();
		$this->pages_empty = array();

		foreach ($this->import as $row) {
			$page_data = $this->buildPageData($row, $file);

			if (empty($page_data['content']) || $row['hidden']) {
				$this->pages_empty[] = $page_data;
				continue;
			} else {
				$this->storePageData($page_data);
			}
			$this->pages_data[] = $page_data;
		}
		// xmp_print($this->pages_empty, 'pages_empty');
		// xmp_print($this->pages_data, $file);
		// exit;
	}

	protected function buildPageTags($path, $title) {
		$tags = array();
		$tags[] = 'ECI';
		$path_count = count($path);
		if ($path_count === 1) {
			$tags[] = $title;
			// $tags[] = 'article:topic-guide';
			$tags[] = 'article:topic';
		} elseif ($path_count === 0) {
			$tags[] = 'article:topic-category';
		} else {
			$tags[] = $path[1];
			$tags[] = 'article:topic';
		}

		return $tags;
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
		return count($this->pages_data) + count($this->pages_empty);
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
		$title = $row['text'];

		$json_array[] = array(
			'title' => $title,
			'content_path' => $row['url'],
			'hidden' => $row['hidden'],
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
	 * Creates array mapping URIs to new MindTouch locations.
	 * @param string $file Name of file to import.
	 * @return array $pages_links Array of URIs.
	 */
	protected function parsePageLinks($file) {
		$pages_links = array();

		foreach ($this->pages_data as $page) {
			// Build the old URI.
			$old_uri = strtolower(trim($page['content_path']));

			// Build the page ID.
			$path = '';
			if (count($page['path']) > 0) {
				foreach ($page['path'] as $key => $page_path) {
					$page['path'][$key] = $this->Api->escapeSlashesPageId($page_path);
				}
				$path .= '/' . join('/', $page['path']);
			}
			$title = rawurlencode($this->Api->escapeSlashesPageId(urldecode(urldecode($page['api_title']))));

			$pages_links[$old_uri] = $path . '/' . $title;
		}

		return $pages_links;
	}

	protected function processImportContentCustom(&$process_import_data) {
		// Extract data.
		extract($process_import_data, EXTR_REFS);

		// Load into PHP Query.
		$content = phpQuery::newDocumentHTML($page['content']);

		// Remove logo.
		foreach ($content->find('div.picarea') as $img) {
			$img = pq($img);
			$img->remove();
		}

		// Remove header navigation.
		foreach ($content->find('table.relatedtopics.aboveheading') as $table) {
			$table = pq($table);
			$table->remove();
		}

		// Remove the footer.
		foreach ($content->find('div[align=left]') as $f) {
			$f = pq($f);
			$f->remove();
		}

		// Deal with see also items.
		foreach ($content->find('h1.seealsoitem') as $see_also) {
			$see_also = pq($see_also);
			$see_also_html = $see_also->html();
			$see_also->replaceWith('<p>' . $see_also_html . '</p>');
		}

		// Remove h1 or h2 when it duplicates the page title.
		foreach ($content->find('h1') as $h1) {
			$h1 = pq($h1);
			if (trim($h1->text()) == $page['title']) {
				$h1->remove();
			}
		}
		foreach ($content->find('h2') as $h2) {
			$h2 = pq($h2);
			if (trim($h2->text()) == $page['title']) {
				$h2->remove();
			}
		}
		foreach ($content->find('h3') as $h3) {
			$h3 = pq($h3);
			if (trim($h3->text()) == $page['title']) {
				$h3->remove();
			}
		}

		// Check for the body.
		$body = $content->find('body');
		if (count($body) > 0) {
			$content = $body;
		}

		if (count($page['path']) === 1) {
			$content->append($this->buildResponsiveGuideContent());
		}

		$page['content'] = $content->html();
	}

	/**
	 * Creates any parent pages for the import.
	 * 
	 * @param array $process_import_data Data for the import.
	 */
	protected function processImportParents(&$process_import_data) {
		// Extract data.
		extract($process_import_data, EXTR_REFS);

		$path = '';
		foreach ($page['path'] as $parent) {
			$path = $this->config['import_path'] . '/' . $path;
			$page_id = $this->Api->buildPageId($parent, $path);
			if (!$this->Api->pageExists($page_id)) {
				$this->Api->pageCreate($page_id, $this->buildResponsiveGuideContent());

				// Add the tabs.
				$property_content = array();
				// Topic hierarchy.
				$property_content[] = array(
					"guid" => generate_guid(),
					"showEditControls" => true,
					"templateKey" => "Topic_hierarchy",
					"templatePath" => "MindTouch/IDF3/Views/Topic_hierarchy",
					"templateTitle" =>'Topic hierarchy',
				);
				$property = array(
					'content' => json_encode($property_content, JSON_UNESCAPED_SLASHES),
					'description' => 'Tabs',
					'property' => 'mindtouch#idf.guideTabs',
				);
				$this->Api->pagePropertiesPost(
					$page_id, 
					$property['property'], 
					$property['description'], 
					$property['content']
				);

				$tags = array(
					'article:topic-guide',
				);
				$this->Api->pageTagsSet($page_id, $tags);
			}
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
		if (empty($page['tags'])) {
			$page['tags'] = array();
		}

		if (count($page['path']) === 1) {
			// Add the tabs.
			$property_content = array();
			// Featured tab
			$property_content[] = array(
				"guid" => generate_guid(),
				"templatePath" => "MindTouch/IDF3/Views/Featured_articles",
				"templateTitle" =>'Featured articles',
			);
			// Article directory.
			$property_content[] = array(
				"guid" => generate_guid(),
				"templatePath" => "MindTouch/IDF3/Views/Article_directory",
				"templateTitle" =>'Article directory',
			);
			$property = array(
				'content' => json_encode($property_content, JSON_UNESCAPED_SLASHES),
				'description' => 'Tabs',
				'property' => 'mindtouch#idf.guideTabs',
			);
			$this->Api->pagePropertiesPost(
				$page_id, 
				$property['property'], 
				$property['description'], 
				$property['content']
			);
		}

		// Add the tags.
		$this->Api->pageTagsSet($page_id, $page['tags']);
	}

}
