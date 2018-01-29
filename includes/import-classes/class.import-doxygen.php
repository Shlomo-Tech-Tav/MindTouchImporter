<?php
class ImportDoxygen extends Client {
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
		$filename = $this->import_dir . '/' . $this->getDirectoryAssets($this->import_file) . '/' . $path;
		if (file_exists($filename)) {
			$content = file_get_contents($filename);
			$content = phpQuery::newDocumentHTML($content);
			$content = $content->find('body .contents')->html();
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

	/**
	 * Builds an array of information for all pages of the import.
	 */
	public function buildPagesData($file) {
		$this->pages_data = array();
		$this->pages_empty = array();
		foreach ($this->import as $page) {
			$page_data = $this->buildPageData($page);
			if (empty($page_data['content'])) {
				$this->pages_empty[] = $page_data;
				continue;
			} else {
				$this->storePageData($page_data);
			}
			$this->pages_data[] = $page_data;
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
		return count($this->pages_data) + count($this->pages_empty);
	}

	/**
	 * Flattens the object created from the XML hierarchy.
	 */
	protected function flattenXmlHierarchy() {
		$xml_array = array();
		$files = array();
		foreach ($this->import->TOC->Node->Subnodes->Node as $row) {
			$this->flattenXmlHierarchyRow($xml_array, $files, $row, array($this->import_file));
		}
		return $xml_array;
	}

	/**
	 * Recursive function that handles each entry of the XML hierarchy object.
	 * @param array $row Page from XML hierarchy object.
	 * @param array $path Array containing previous path, if any.
	 */
	protected function flattenXmlHierarchyRow(&$xml_array, &$files, $row, $path = array()) {
		// Get the title.
		$title = trim(strip_tags($row->Name[0]));

		// Get the path to the content.
		$content_path = (string) $row->Path[0];

		// Add the row to the array when the file hasn't been included before.
		if (!in_array($content_path, $files)) {
			if (!empty($content_path)) {
				$files[] = $content_path;
			}
			$xml_array[] = array(
				'title' => $title,
				'content_path' => $content_path,
				'path' => $path
			);
		}

		// Check for sub-nodes and recursively call function again.
		if (!empty($row->Subnodes)) {
			$path[] = $title;
			foreach ($row->Subnodes->Node as $child) {
				$this->flattenXmlHierarchyRow($xml_array, $files, $child, $path);
			}
		}
	}

	/**
	 * Convert the import to an XML object after the parse function loads it.
	 */
	protected function parseAfterSetFile() {
		$this->importLoadXml();

		// Flatten the XML object.
		$this->import = $this->flattenXmlHierarchy();
	}

	/**
	 * Creates array mapping old URIs to new from the json array.
	 * @param string $file Name of file to import.
	 * @return array $pages_links Array of URIs.
	 */
	protected function parsePageLinks($file) {
		// Build array out of import array.
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

		return $pages_links;
	}
}
