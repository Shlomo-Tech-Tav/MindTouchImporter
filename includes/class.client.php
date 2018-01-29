<?php
abstract class Client {
	public $Api;
	protected $assets_per_import = 2;
	protected $client_dir;
	public $config = array();
	/**
	 * Instance of database class.
	 * @var object
	 */
	public $Database;
	public $import;
	protected $import_assets = array();
	protected $import_dir;
	protected $import_file;
	/**
	 * Array of client's import files.
	 * @var array
	 */
	public $import_files = array();
	/**
	 * Array of client's import files in queue.
	 * @var array
	 */
	public $import_queue = array();
	/**
	 * Instance of queue class.
	 * @var object
	 */
	private $Queue;
	protected $pages_per_import;
	protected $pages_data = array();
	protected $pages_empty = array();
	protected $temp_dir;
	protected $template_dir;
	protected $Tools;

	/**
	 * Sets up the client class.
	 * @param array $config Configuration for the client.
	 */
	public function __construct(&$Database, $config) {
		$this->Database = $Database;
		$this->config = $config;
		$this->Queue = new ImportQueue($this->Database);
		$this->setPagesPerImport();
		$this->setDirectories();
		$this->importFilesLoad();
		$this->ImportQueueLoad();
		$api_url = parse_url(API_URL);
		$credentials = array(
			'api_domain' => $api_url['host'],
			'api_username' => API_USERNAME,
			'api_password' => API_PASSWORD
		);
		$this->Api = new \MindTouchApi\MindTouchApi($credentials);
		$this->Tools = new ContentTools();
	}

	/**
	 * Returns MindTouch content for a Responsive category page.
	 * 
	 * @return string $content Content for a Responsive category page.
	 */
	protected function buildResponsiveCategoryContent() {
		ob_start();
		?>
		<pre class="script">
		template('MindTouch/IDF3/Views/Category');
		</pre>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns MindTouch content for a Responsive guide page.
	 * 
	 * @return string $content Content for a Responsive guide page.
	 */
	protected function buildResponsiveGuideContent() {
		ob_start();
		?>
		<pre class="script">
		template('MindTouch/IDF3/Views/Guide');
		</pre>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns MindTouch content for a Responsive portfolio page.
	 * 
	 * @return string $content Content for a Responsive portfolio page.
	 */
	protected function buildResponsivePortfolioContent() {
		ob_start();
		?>
		<pre class="script">
		template('MindTouch/IDF3/Views/Portfolio');
		</pre>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns default content a MindTouch page with a guide.
	 * @return string $content Default content for guide.
	 */
	protected function buildDefaultGuideContent() {
		ob_start();
		?>
		<pre class="script">
		template('MindTouch/IDF2/Views/Guide', { });
		</pre>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns default content a MindTouch page with a wiki tree.
	 * @return string $content Default content for tree page.
	 */
	protected function buildDefaultTreeContent() {
		ob_start();
		?>
		<pre class="script">
		wiki.tree();
		</pre>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns content for a media repository page, which is a script
	 * that will display all attachments in a table.
	 * 
	 * @return string Default content for a media repository page.
	 */
	protected function buildDefaultMediaRepoContent() {
		ob_start();
		?>
		<pre class="script">
		&lt;table&gt;
		    &lt;thead&gt;&lt;tr&gt;
		        &lt;th&gt;"File Name"&lt;/th&gt;
		        &lt;th&gt;"Last Modified"&lt;/th&gt;
		        &lt;th&gt;"Image"&lt;/th&gt;
		    &lt;/tr&gt;&lt;/thead&gt;
		    &lt;tbody&gt;&lt;/tbody&gt;
		foreach (var file in list.orderby(map.values(page.files), "name")) {
		    &lt;tr&gt;
		        &lt;td class="file-name sorting_1"&gt;&lt;a href=(file.uri)&gt;file.name&lt;/a&gt;&lt;/td&gt;
		        &lt;td&gt;date.format(file.date,"MMMM dd, yyyy");&lt;/td&gt;
		        &lt;td&gt;&lt;a href=(file.uri)&gt;&lt;img src=(file.thumburi) /&gt;&lt;/a&gt;&lt;/td&gt;
		    &lt;/tr&gt;
		}
		&lt;/table&gt;
		</pre>

		<pre class="script-css">
		.mt-content-container table img {
		    max-width: 250px;
		}
		</pre>
		<?php
		return ob_get_clean();
	}

	protected function buildDefaultAttachmentPageContent() {
		ob_start();
		?>
		<pre class="script">
		&lt;table&gt;
		    &lt;thead&gt;&lt;tr&gt;
		        &lt;th&gt;"File Name"&lt;/th&gt;
		        &lt;th&gt;"Description"&lt;/th&gt;
		    &lt;/tr&gt;&lt;/thead&gt;
		    &lt;tbody&gt;&lt;/tbody&gt;
		foreach (var file in list.orderby(map.values(page.files), "name")) {
		    &lt;tr&gt;
		        &lt;td class="file-name sorting_1"&gt;&lt;a href=(file.uri)&gt;string.replace(file.name, '_', ' ')&lt;/a&gt;&lt;/td&gt;
		        &lt;td&gt;file.description;&lt;/td&gt;
		    &lt;/tr&gt;
		}
		&lt;/table&gt;		
		</pre>
		<?php
		return ob_get_clean();
	}

	public function buildMindTouchProductionTree($page_id, $format = 'flatten', $depth = "all") {
		$credentials = array(
			'api_domain' => $this->config['api_url'],
			'api_username' => $this->config['api_username'],
			'api_password' => $this->config['api_password'],
		);
		$this->Api->setApiCredentials($credentials);
		$this->config['import_path'] = $this->config['prod_import_path'];
		$this->config['mindtouch_url'] = 'https://' . $this->config['api_url'] . '/';

		$tree = $this->Api->pageTreeGet($page_id, array('include' => 'lastmodified'));
		if ($format === 'flatten') {
			return $this->treeFlatten($tree);
		} elseif ($format === 'fancytree') {
			// Build array for fancytree, which preserves the child relationships.
			return $this->treeFancy($tree, $depth);
		}
		return $tree;
	}

	public function treeFancy($tree, $depth = "all") {
		$pages = array();
		foreach ($tree->page->subpages->page as $page) {
			$pages[] = $this->treeFancyBranch($page, $depth);
		}
		return $pages;
	}

	private function treeFancyBranch($page, $depth) {
		$page_to_add = array(
			'key' => (int) $page['id'],
			'path' => (string) $page->path,
			'title' => (string) $page->title,
		);

		if (isset($page->subpages->page)) {
			$page_to_add['folder'] = true;
			if ($depth === "all" || $depth > 0) {
				if ($depth > 0) {
					$depth -= 1;
				}
				$page_to_add['children'] = array();
				foreach ($page->subpages->page as $subpage) {
					$page_to_add['children'][] = $this->treeFancyBranch($subpage, $depth);
				}
			} else {
				$page_to_add['lazy'] = true;
			}
		}

		return $page_to_add;
	}

	/**
	 * Flattens the given tree object.
	 * 
	 * @param object $tree MindTouch XML tree object.
	 * @return array Single-level array of pages.
	 */
	public function treeFlatten($tree) {
		$pages = array();
		foreach ($tree->page->subpages->page as $page) {
			$this->treeFlattenBranch($pages, $page);
		}
		return $pages;
	}

	/**
	 * Recursive function to flatten the multi-dimensional object
	 * into a single-level array.
	 * 
	 * @param array &$pages Reference to array object to update.
	 * @param object $page XML tree page object.
	 */
	private function treeFlattenBranch(&$pages, $page) {
		$pages[] = array(
			'id' => (int) $page['id'],
			'path' => (string) $page->path,
			'title' => (string) $page->title,
			'date_created' => (string) $page->{'date.created'},
			'date_modified' => (string) $page->{'date.modified'},
		);

		if (isset($page->subpages->page)) {
			foreach ($page->subpages->page as $subpage) {
				$this->treeFlattenBranch($pages, $subpage);
			}
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
	 * Returns the specified directory.
	 * @param string $directory Directory to return.
	 * @return string $dir Directory path.
	 */
	public function getDirectory($directory) {
		switch ($directory) {
			case 'client':
				$dir = $this->client_dir;
				break;

			case 'import':
				$dir = $this->import_dir;
				break;

			case 'temp':
				$dir = $this->temp_dir;
				break;

			case 'template':
				$dir = $this->template_dir;
				break;

			default:
				$dir = '';
				break;
		}
		return $dir;
	}

	/**
	 * Returns the asset directory path for the given file.
	 * @param string $file File to get asset directory.
	 * @return string $assets_dir Asset directory.
	 */
	public function getDirectoryAssets($file) {
		$assets_dir = $file . '_files';
		return $assets_dir;
	}

	public function getImportFile() {
		if (!isset($this->import_file)) {
			return false;
		}
		return $this->import_dir . '/' . $this->import_files[$this->import_file];
	}

	/**
	 * Returns the size of the import file in bytes.
	 * @return int Size of the import file in bytes.
	 */
	public function getImportFileSize() {
		$real_path = realpath($this->import_dir . '/' . $this->import_files[$this->import_file]);
		if (file_exists($real_path)) {
			return filesize($real_path);
		} else {
			return 0;
		}
	}

	/**
	 * Returns the import's array of assets.
	 * @return array $import_assets Array of assets.
	 */
	public function getImportAssets() {
		return $this->import_assets;
	}

	/**
	 * Returns array of assets not added during the import process.
	 * @param array $assets_added Array of assets added.
	 * @return array $assets_unadded Array of assets not added.
	 */
	public function getImportAssetsNotAdded($assets_added) {
		// Load the assets.
		$this->importAssetsLoad($this->getDirectoryAssets($this->import_file));

		// Build list of assets not added.
		$assets_unadded = $this->import_assets;
		foreach ($assets_added as $added) {
			$assets_unadded = array_diff($assets_unadded, $added);
		}

		// Ignore the Windows Thumbs.db file.
		if (in_array('Thumbs.db', $assets_unadded)) {
			unset($assets_unadded[array_search('Thumbs.db', $assets_unadded)]);
		}

		return $assets_unadded;
	}

	/**
	 * Returns the pages data array.
	 * @return array $pages_data
	 */
	public function getPagesData() {
		return $this->pages_data;
	}

	/**
	 * Attaches the asset to the provided MindTouch page.
	 *
	 * @param array $page Current page.
	 * @param string $assets_dir Asset directory.
	 * @param string $asset File name of asset.
	 * @param string $page_id MindTouch page ID.
	 * @return object $output XML object of the MindTouch API response.
	 */
	protected function importAssetAttach($page, $assets_dir, $asset, $page_id, $description = '') {
		// Attach asset to page.
		$asset_info = pathinfo($asset);
		if ($asset_info['extension'] === 'htm' || $asset_info['extension'] === 'html') {
			return false;
		}
		$file_name = $this->import_dir . '/' . $assets_dir . '/' . $asset;
		$output = $this->Api->pageFilePut($page_id, $file_name, urlencode($description));
		return $output;
	}

	/**
	 * Updates the page's HTML contents to point to the MindTouch location
	 * of the asset.
	 * @param string $asset File name of asset.
	 * @param string $content Contents of import file.
	 * @param integer $output_id MindTouch ID for the attachment.
	 * @param string $output_file_name MindTouch file name for the attachment.
	 * @param boolean $remove_dimensions Set to true to remove height and width settings on attached images.
	 * @return string $content Updated contents of import file.
	 */
	protected function importAssetContentReplace($asset, $content, $output_id, $output_file_name, $page, $remove_dimensions = false) {
		// Load content in phpQuery.
		$pqContent = phpQuery::newDocumentHTML($content);

		// Iterate through each image. Replace src when found.
		foreach ($pqContent->find('img') as $img) {
			$img = pq($img);
			$src = $img->attr('src');

			// phpQuery encodes the URI, so decode it for the comparison.
			$src = urldecode($src);

			if (stripos($src, $asset) !== false) {
				$img->attr('src', '/@api/deki/files/' . $output_id . '/' . $output_file_name);
				if ($remove_dimensions) {
					$img->removeAttr('height');
					$img->removeAttr('width');
				}
			}
		}
		unset($img);

		// Iterate through links. Replace href when found.
		foreach ($pqContent->find('a[href]') as $a) {
			$a = pq($a);
			$href = $a->attr('href');

			// phpQuery encodes the URI, so decode it for the comparison.
			$href = urldecode($href);

			if (stripos($href, $asset) !== false) {
				$a->attr('href', '/@api/deki/files/' . $output_id . '/' . $output_file_name);
			}
		}
		unset($a);

		$content = $pqContent->html();
		unset($pqContent);
		return $content;
	}

	/**
	 * Loads the asset and returns its contents.
	 * @param string $assets_dir Asset directory.
	 * @param string $asset File name of asset to load.
	 * @return string $contents Contents of asset.
	 */
	public function importAssetLoad($assets_dir, $asset) {
		$assets_dir = $this->import_dir . '/' . $assets_dir;
		if (in_array($asset, $this->import_assets)
			&& file_exists($assets_dir . '/' . $asset)
		) {
			return file_get_contents($assets_dir . '/' . $asset);
		}
		return false;
	}

	/**
	 * Saves the html file with the new content.
	 * @param string $assets_dir Full path to asset.
	 * @param string $asset File name of asset.
	 * @param string $content Content to save.
	 * @return boolean Returns true on success.
	 */
	public function importAssetSave($assets_dir, $asset, $content) {
		$assets_dir = $this->import_dir . '/' . $assets_dir;

		if (file_put_contents($assets_dir . '/' . $asset . '-nonames.html', $content, LOCK_EX) !== false) {
			rename($assets_dir . '/' . $asset . '-nonames.html', $assets_dir . '/' . $asset);
			return true;
		}
		return false;
	}

	/**
	 * Checks the current import to see if it has assets.
	 * @param string $assets_dir The name of the asset directory inside the imports directory.
	 * @return boolean
	 */
	public function importAssetsCheck($assets_dir) {
		if (file_exists($this->import_dir . '/' . $assets_dir)
			&& scandir($this->import_dir . '/' . $assets_dir) > 2) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Loads the files in the import's asset directory and subdirectories.
	 * 
	 * @param string $assets_dir The name of the asset directory inside the imports directory.
	 */
	public function importAssetsLoad($assets_dir) {
		if ($this->importAssetsCheck($assets_dir)) {
			$assets_dir = $this->import_dir . '/' . $assets_dir;

			$this->import_assets = array();
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($assets_dir)) as $filename) {
				$asset = str_replace($assets_dir . '/', '', $filename);
				if ($asset === '.'
					|| $asset === '..'
					|| $asset === 'Thumbs.db'
					|| stripos($asset, '/.') !== false
				) {
					continue;
				}

				// Skip HTML files because MindTouch does not allow them to be attached.
				$pathinfo = pathinfo($asset);
				if ($pathinfo['basename'] === 'Thumbs.db') {
					continue;
				}
				if ($pathinfo['extension'] === 'htm' || $pathinfo['extension'] === 'html') {
					continue;
				}

				$this->import_assets[] = $asset;
			}
		}
	}

	/**
	 * Checks to see if the given import file exists on the server.
	 * @param string $import Import file name (without extension)
	 * @return boolean
	 */
	function importFileExists($import) {
		// $import is user content, so it can't be trusted.
		// Validate it by making sure it's in the list of import files.
		if (array_key_exists($import, $this->import_files)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns the file type of the import file.
	 * @param string $import Import file to check.
	 * @return string $extension Extension of the import file.
	 */
	function importFileType($import) {
		if (!array_key_exists($import, $this->import_files)) {
			return false;
		}

		$path_info = pathinfo($this->import_dir . '/' . $this->import_files[$import]);
		return $path_info['extension'];
	}

	/**
	 * Loads the files in the import directory.
	 */
	function importFilesLoad() {
		if (!is_dir($this->import_dir)) {
			$Clients = new Clients($this->Database);
			$Clients->createClientImportsDirectory($this->config['code']);
		}
		$entries = scandir($this->import_dir);
		foreach ($entries as $entry) {
			if (is_dir($this->import_dir . '/' . $entry)) {
				continue;
			}
			$entry = pathinfo($entry);
			$this->import_files[$entry['filename']] = $entry['basename'];
		}
	}

	/**
	 * Loads the files in the import queue.
	 */
	protected function ImportQueueLoad() {
		$this->import_queue = $this->Queue->getByClient($this->config['client_id']);
	}

	/**
	 * Loads and converts the JSON import file to an array.
	 */
	public function importLoadJson() {
		// Get the contents of the JSON import file.
		$file_name = $this->import_dir . '/' . $this->import_files[$this->import_file];
		$this->import = file_get_contents($file_name);
		// Convert JSON to array.
		$this->import = json_decode($this->import, true);
	}

	/**
	 * Loads and converts the XML import file to an XML object.
	 */
	public function importLoadXml() {
		// Get the contents of the XML import file.
		$file_name = $this->import_dir . '/' . $this->import_files[$this->import_file];
		$this->import = file_get_contents($file_name);
		// Convert XML to object.
		$this->import = simplexml_load_string($this->import);
	}

	/**
	 * Loads and converts the CSV import file to an array.
	 * @param boolean $first_line_titles True when the first row are titles.
	 */
	public function importLoadCsv($first_line_titles = true) {
		// Get the contents of the CSV import file.
		$file_name = $this->import_dir . '/' . $this->import_files[$this->import_file];

		// Convert the file to an array.
		$this->import = convert_csv_to_array($file_name, $first_line_titles);
	}

	/**
	 * Loads the import.
	 * @param string $import Name of import.
	 * @return boolean True on success.
	 */
	public function importSetFile($import) {
		// Set import file.
		if (!$this->importFileExists($import)) {
			return false;
		}
		$this->import_file = $import;

		// Set import's temporary directory.
		$this->temp_dir = $this->import_dir . '/temp/' . $this->Api->buildPageId($this->import_file);

		return true;
	}

	/**
	 * Removes the loaded import file and any of its assets.
	 * @return boolean True on success.
	 */
	public function importRemove() {
		// Make sure the import exists.
		if (!$this->importFileExists($this->import_file)) {
			return false;
		}

		// Remove any assets.
		$assets_dir = $this->import_dir . '/' . $this->getDirectoryAssets($this->import_file);
		if (is_dir($assets_dir)) {
			remove_directory($assets_dir);
		}

		// Remove import file.
		$real_path = realpath($this->getDirectory('import') . '/' . $this->import_files[$this->import_file]);
		unset($this->import_files[$this->import_file]);
		if (file_exists($real_path)) {
			return unlink($real_path);
		}
	}

	/**
	 * Loads the contents of the import's temporary file.
	 * @param string $import Import's temporary import file name.
	 * @return string $content Contents of temporary file.
	 */
	public function importTempContentGet($import) {
		$content = file_get_contents($this->temp_dir . '/' . substr($import, 0, 250));
		return $content;
	}

	/**
	 * Removes the import's temporary file.
	 * @param string $import Import's temporary import file name.
	 * @return boolean True on success.
	 */
	protected function importTempContentRemove($import) {
		$real_path = realpath($this->temp_dir . '/' . substr($import, 0, 250));
		if (file_exists($real_path)) {
			return unlink($real_path);
		}
		return false;
	}

	/**
	 * Sets the contents of the import's temporary file.
	 * @param string $import Import's temporary import file name.
	 * @param string $content Contents of temporary file.
	 * @return boolean True on success.
	 */
	protected function importTempContentSet($import, $content) {
		if (file_put_contents($this->temp_dir . '/' . substr($import, 0, 250), $content) === false) {
			return false;
		}
		return true;
	}

	/**
	 * Removes all files from the import's temporary directory.
	 * @return boolean True on success.
	 */
	public function importTempDirectoryEmpty() {
		if (!file_exists($this->temp_dir)) {
			return true;
		}

		$entries = scandir($this->temp_dir);
		foreach ($entries as $entry) {
			$real_path = realpath($this->temp_dir . '/' . $entry);
			if (is_dir($real_path)) {
				continue;
			}
			if (!$this->importTempContentRemove($entry)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Removes the import's temporary directory.
	 * @return boolean True on success.
	 */
	protected function importTempDirectoryRemove() {
		$real_path = realpath($this->temp_dir);
		return rmdir($real_path);
	}

	/**
	 * Determines if the loaded import has finished importing.
	 * @return boolean
	 */
	public function isImportFinished() {
		if (file_exists($this->temp_dir)) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Parses the import file, stores data in temporary files, and returns
	 * information about the import.
	 * @param string $file File to import.
	 * @return array $parse_data
	 */
	public function parse($file) {
		// Open import file and parse.
		$this->importSetFile($file);
		if (method_exists($this, 'parseAfterSetFile')) {
			$this->parseAfterSetFile();
		}

		// Empty temporary directory.
		$this->importTempDirectoryEmpty();

		// Build array of content.
		$this->buildPagesData($file);

		// Return data.
		$parse_data = array(
			'pages_empty' => $this->pages_empty,
			'pages_import' => $this->countImportPages(),
			'pages_total' => $this->countTotalPages(),
			'pages_data' => $this->pages_data,
			'pages_links' => $this->parsePageLinks($file)
		);
		if (method_exists($this, 'parseAfterParseData')) {
			$this->parseAfterParseData($parse_data, $file);
		}

		// Remove content from returned data.
		foreach ($parse_data['pages_data'] as $key => $page) {
			unset($parse_data['pages_data'][$key]['content']);
		}

		return $parse_data;
	}

	/**
	 * Creates array mapping old URIs to new.
	 * @param string $file Name of file to import.
	 * @return array $pages_links Array of URIs.
	 */
	protected function parsePageLinks($file) {
		return array();
	}

	/**
	 * Processes the import file, adds data to MindTouch, and returns
	 * information about the import.
	 * @param string $file File to import.
	 * @param boolean $use_test Whether to use test API.
	 * @return array $process_data
	 */
	public function process($file, $target_select = 0, $use_test = true) {
		// Deal with using production API.
		if (!$use_test) {
			$credentials = array(
				'api_domain' => $this->config['api_url'],
				'api_username' => $this->config['api_username'],
				'api_password' => $this->config['api_password'],
			);
			$this->Api->setApiCredentials($credentials);
			$this->config['import_path'] = $this->config['prod_import_path'];
			$this->config['mindtouch_url'] = 'https://' . $this->config['api_url'] . '/';

			if ($target_select > 0) {
				$target = $this->Api->pageGet($target_select);
				$this->config['import_path'] = (string) $target->path;
			}
		} else {
			$this->config['mindtouch_url'] = 'https://' . $this->config['import_domain'] . '/';
		}
		// xmp_print($this->config);
		// exit;

		// Open XML file and parse.
		$this->importSetFile($file);
		if (method_exists($this, 'processAfterSetFile')) {
			$this->processAfterSetFile();
		}

		if ($this->isImportFinished()) {
			// The import has finished. Build process data from session.
			$successes = session_array('successes');
			$failures = session_array('failures');
			$pages_with_internal_links = session_array('pages_with_internal_links');
			// $pages_with_tables = session_array('pages_with_tables');
			// $pages_with_assets = session_array('pages_with_assets');
			$assets_added = session_array('assets_added');
		} else {
			// Load assets.
			$assets_dir = $this->getDirectoryAssets($file);
			$this->importAssetsLoad($assets_dir);

			// Load anything else needed.
			$extra = array();
			if (method_exists($this, 'processLoadExtra')) {
				$extra = $this->processLoadExtra($file);
			}

			// Prepare success and failure arrays.
			$successes = session_array('successes');
			$failures = session_array('failures');
			$pages_with_internal_links = session_array('pages_with_internal_links');
			// $pages_with_tables = session_array('pages_with_tables');
			// $pages_with_assets = session_array('pages_with_assets');
			$assets_added = session_array('assets_added');

			// Get the next file to import and start the loop.
			$i = 0;
			foreach ($_SESSION['data']['pages_to_import'] as $page_key => $page) {
				// if ($file === 'Salesforce_4th_iteration_2_of_2'
				// 	&& $page['title'] !== 'Web searches do not lead to the correct access index'
				// ) {
				// 	$this->importTempContentRemove($page['temp_file']);
				// 	unset($_SESSION['data']['pages_to_import'][$page_key]);
				// 	continue;
				// }

				// Prepare process import data.
				$process_import_data = array(
					'assets_added' =>& $assets_added,
					'assets_dir' => $assets_dir,
					'file' => $file,
					'page_key' => $page_key,
					'page' =>& $page,
					'successes' =>& $successes,
					'failures' =>& $failures,
					'pages_links' =>& $_SESSION['data']['pages_links'],
					'pages_with_internal_links' =>& $pages_with_internal_links,
					// 'pages_with_tables' =>& $pages_with_tables,
					// 'pages_with_assets' =>& $pages_with_assets,
					'i' => $i,
					'extra' => $extra
				);

				// Create any parents for the page first.
				if (method_exists($this, 'processImportParents')) {
					$this->processImportParents($process_import_data);
				}

				// Get and store the MindTouch page ID.
				$page_id = $this->processImportPageId($process_import_data);
				$process_import_data['page_id'] = $page_id;

				// Load content.
				$page['content'] = $this->importTempContentGet($page['temp_file']);

				// Prepare the content.
				$this->processImportContent($process_import_data);
				if (method_exists($this, 'processImportContentCustom')) {
					$this->processImportContentCustom($process_import_data);
				}

				// Attach any assets.
				if (!$this->processImportAssets($process_import_data)) {
					// There are more assets yet to add, but the page needs to
					// be reloaded to prevent timeouts. Update the temp content
					// and exit the loop.
					$this->importTempContentSet($page['temp_file'], $page['content']);
					break;
				}
				$assets_added = array();

				// Create the page.
				if ($this->processImportCreatePage($process_import_data)) {
					// Deal with security.
					$this->processImportSecurity($process_import_data);

					// Tags can be added only after the page is created.
					if (method_exists($this, 'processImportTags')) {
						$this->processImportTags($process_import_data);
					}

					// Properties can be added only after the page is created.
					if (method_exists($this, 'processImportProperties')) {
						$this->processImportProperties($process_import_data);
					}

					// Add content IDs when set.
					$this->processImportContentId($process_import_data);

					// Add the page order.
					if (method_exists($this, 'processImportOrder')) {
						$this->processImportOrder($process_import_data);
					}
				}

				// Remove the page's data.
				$this->importTempContentRemove($page['temp_file']);
				unset($_SESSION['data']['pages_to_import'][$page_key]);

				// Increment count and break when the number of pages imported has been met.
				$i ++;
				if ($i >= $this->pages_per_import) {
					break;
				}
			}

			// Save data in session.
			session_array('successes', $successes);
			session_array('failures', $failures);
			session_array('pages_with_internal_links', $pages_with_internal_links);
			// session_array('pages_with_tables', $pages_with_tables);
			// $pages_with_assets = array_unique($pages_with_assets);
			// session_array('pages_with_assets', $pages_with_assets);
			session_array('assets_added', $assets_added);

			// Remove the temporary directory when the import is done.
			if (count($_SESSION['data']['pages_to_import']) === 0) {
				$this->importTempDirectoryRemove();
			}
		}

		// Return data.
		$process_data = array(
			'assets_added' => $assets_added,
			'successes' => $successes,
			'failures' => $failures,
			'pages_with_internal_links' => $pages_with_internal_links,
			// 'pages_with_tables' => $pages_with_tables,
			// 'pages_with_assets' => $pages_with_assets
		);
		return $process_data;
	}

	/**
	 * Processes and attaches any of the import's assets.
	 * @param array $process_import_data Data for the import.
	 */
	protected function processImportAssets(&$process_import_data) {
		// Extract data.
		extract($process_import_data, EXTR_REFS);

		if (DISABLE_ATTACHMENTS) {
			return true;
		}

		// Build array of attachments for this import.
		$assets = $this->processImportAssetsToAttach($page);

		if (!isset($assets_added[$page_id]) 
			|| !is_array($assets_added[$page_id])
		) {
			$assets_added[$page_id] = array();
		}

		// Loop through array and attach each file.
		$a = 0;
		foreach ($assets as $asset) {
			$description = '';
			if (is_array($asset)) {
				$description = $asset['description'];
				$asset = $asset['asset'];
			}

			// Skip any added assets.
			if (in_array($asset, $assets_added[$page_id])) {
				continue;
			}

			// Attach asset to page.
			if (method_exists($this, 'processImportAssetPageId')) {
				$asset_page_id = $this->processImportAssetPageId($process_import_data, $asset);
			} else {
				$asset_page_id = $page_id;
			}
			$attach_output = $this->importAssetAttach($page, $assets_dir, $asset, $asset_page_id, $description);

			// Update page content to point to MindTouch attachment.
			$remove_dimensions = !empty($extra['remove_dimensions']) ? $extra['remove_dimensions'] : false;
			$page['content'] = $this->importAssetContentReplace($asset, $page['content'], (string) $attach_output['id'], (string) $attach_output->filename, $page, $remove_dimensions);
			// $pages_with_assets[] = $page_id;

			// Put the asset into the added array so it isn't added again.
			$assets_added[$page_id][] = $asset;

			// Increment count and break when the number of assets added has been met.
			$a ++;
			if ($a >= $this->assets_per_import) {
				break;
			}
		}

		// Determine if there are any assets left to attach.
		$left = 0;
		foreach ($assets as $asset) {
			if (is_array($asset)) {
				$asset = $asset['asset'];
			}
			if (!in_array($asset, $assets_added[$page_id])) {
				$left ++;
			}
		}
		if ($left === 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Build array of attachments for this page.
	 * 
	 * @param string $page Page.
	 * @return array Array of assets to attach.
	 */
	protected function processImportAssetsToAttach($page) {
		// Build array of attachments for this import.
		$assets = array();
		foreach ($this->import_assets as $asset) {
			// phpQuery encodes the image URI in the content. Encode the asset for comparison.
			// However, phpQuery does not encode ', &, (, and ).
			$encoded_asset = rawurlencode($asset);
			$search = array(
				'%27',
				'%26',
				'%28',
				'%29',
				'%2C',
				'%2F',
			);
			$replace = array(
				"'",
				"&amp;",
				"(",
				")",
				",",
				'/',
			);
			$encoded_asset = str_replace($search, $replace, $encoded_asset);
			if (stripos($page['content'], $asset) !== false 
				|| stripos($page['content'], rawurlencode($asset)) !== false
				|| stripos($page['content'], $encoded_asset) !== false
			) {
				$assets[] = $asset;
			}
		}
		return $assets;
	}

	/**
	 * Prepares the content for the import.
	 * @param array $process_import_data Data for the import.
	 */
	protected function processImportContent(&$process_import_data) {
		// Extract data.
		extract($process_import_data, EXTR_REFS);

		// Replace any page links.
		$content = $this->replacePagesLinks($page['content'], $pages_links);
		if ($content != $page['content']) {
			$page['content'] = $content;
			$pages_with_internal_links[] = $page_id;
		}
	}

	/**
	 * Sets the content ID for the page.
	 * @param array $process_import_data Data for the import.
	 * @return boolean True on success.
	 */
	protected function processImportContentId(&$process_import_data) {
		// Extract data.
		extract($process_import_data, EXTR_REFS);

		if (empty($page['content_id'])) {
			return false;
		}

		// Get the current page's numerical ID.
		if (count($successes) > 0) {
			// The last element of the successes array is this page.
			$current = end($successes);
			$output = $this->Api->contextsPut($page['content_id']);
			$output = $this->Api->contextMapsPut($page['content_id'], $current['id']);
			return true;
		}
	}

	/**
	 * Sets security policy on page.
	 * @param array $process_import_data Data for the import.
	 */
	protected function processImportSecurity(&$process_import_data) {
		// Extract data.
		extract($process_import_data, EXTR_REFS);

		if (!empty($page['security'])) {
			$this->Api->pageSecurityPut($page_id, $page['security']);
		}
	}

	/**
	 * Replaces the old, internal URIs with new MindTouch ones.
	 * @param string $content Page content.
	 * @param array $pages_links Array containing old and new URIs.
	 * @return string $content Page content.
	 */
	protected function replacePagesLinks($content, $pages_links) {
		if (count($pages_links) < 1) {
			return $content;
		}

		// Load content into PHP Query and get all the hrefs.
		$pqContent = phpQuery::newDocumentHTML($content);
		$replaced_any = false;
		foreach ($pqContent->find("a[href]") as $a) {
			$a = pq($a);
			$href = $a->attr('href');

			// phpQuery encodes the URI, so decode it for the comparison.
			$href = urldecode($href);
			$parsed_href = parse_url($href);

			// Iterate through the old URIs.
			$replaced_this_one = false;
			foreach ($pages_links as $old_uri => $new_uri) {
				// Decode the old URI.
				if (method_exists($this, 'replacePagesLinksOldUrl')) {
					$old_uri = $this->replacePagesLinksOldUrl($old_uri);
				} else {
					$old_uri = urldecode($old_uri);
				}

				// Add the fragment to the new uri when it's set.
				if (!empty($parsed_href['fragment']) && strpos($new_uri, '#') === false) {
					$new_uri .= '#' . $parsed_href['fragment'];
				}

				// Replace with the new when the old is found and it's not external.
				if (method_exists($this, 'replacePagesLinksReplace')) {
					if ($this->replacePagesLinksReplace($a, $href, $old_uri, $new_uri)) {
						$replaced_any = true;
						$replaced_this_one = true;
					}
				} else {
					if (stripos($href, 'http') !== 0 && stripos($href, $old_uri) !== false) {
						$a->attr('href', '/' . $this->config['import_path'] . $new_uri);
						$replaced_any = true;
						$replaced_this_one = true;
					}
				}
				if ($replaced_this_one) {
					break;
				}
			}
		}
		unset($a);

		if ($replaced_any) {
			$content = $pqContent->html();
		}
		unset($pqContent);

		return $content;
	}

	/**
	 * Creates the page in MindTouch.
	 * @param array $process_import_data Data for the import.
	 * @return boolean True on success.
	 */
	protected function processImportCreatePage(&$process_import_data) {
		// Extract data.
		extract($process_import_data, EXTR_REFS);

		// Create the page.
		$title = '';
		if (urldecode(urldecode($page['api_title'])) !== $page['title']) {
			$title = $page['title'];
		}

		// Check to see if the client uses drafts.
		if ($this->config['import_drafts'] == 1) {
			// Activate the draft when it doesn't exist.
			if (!$this->Api->draftsExists($page_id)) {
				if (!$this->Api->pageExists($page_id)) {
					$this->Api->draftsCreate($page_id);
				} else {
					$this->Api->draftsActivate($page_id);
				}
			}
			// Create the draft.
			$output = $this->Api->draftsContentsPost($page_id, $page['content'], $title);
		} else {
			// Create the page.
			$output = $this->Api->pageCreate($page_id, $page['content'], $title);
		}

		// Check the response for errors.
		if ($this->Api->pageCreateCheck($output)) {
			$successes[$page_id] = array(
				'id' => (int) $output->page['id'],
				'title' => (string) $output->page->title,
				'uri' => (string) $output->page->{'uri.ui'}
			);

			// Check for tables in content.
			if (strpos($page['content'], '<table') !== false) {
				// $pages_with_tables[] = $page_id;
			}
			return true;
		} else {
			$failures[] = array(
				'title' => $page['title'],
				'error' => $this->Api->parseErrorMessage($output)
			);
			return false;
		}
	}

	/**
	 * Creates the MindTouch page ID for the import.
	 * @param array $process_import_data Data for the import.
	 * @return string $page_id The MindTouch page ID.
	 */
	protected function processImportPageId(&$process_import_data) {
		// Extract data.
		extract($process_import_data, EXTR_REFS);

		// Build the page ID.
		$path = $this->config['import_path'];
		if (count($page['path']) > 0) {
			foreach ($page['path'] as $key => $page_path) {
				$page['path'][$key] = $this->Api->escapeSlashesPageId($page_path);
			}
			$path .= '/' . join('/', $page['path']);
		}
		$page_id = $this->Api->buildPageId(urldecode(urldecode($page['api_title'])), $path);
		return $page_id;
	}

	/**
	 * Replaces an ampersand in the title with 'and', or other provided text.
	 * Used because MindTouch doesn't deal well with ampersands in page IDs.
	 * @param string $title Title of page.
	 * @param string $with Text to replace the ampersand with.
	 * @return string $title Title of page without ampersands.
	 */
	public function replaceAmpersand($title, $with = 'and') {
		$search = array(
			' & ',
			'&'
		);
		$replace = array(
			' ' . $with . ' ',
			$with
		);
		$title = str_replace($search, $replace, $title);
		return $title;
	}

	/**
	 * Sets the client's directories.
	 */
	protected function setDirectories() {
		$this->client_dir = ABSPATH . 'clients/' . $this->config['code'];
		$this->import_dir = $this->client_dir . '/imports';
		$this->template_dir = $this->client_dir . '/templates';
	}

	/**
	 * Set the pages per import. This can be controlled by the client's config.
	 */
	protected function setPagesPerImport() {
		if (!empty($this->config['pages_per_import'])) {
			$this->pages_per_import = $this->config['pages_per_import'];
		} else {
			$this->pages_per_import = 7;
		}
	}

	/**
	 * Stores import page content in temporary file.
	 * 
	 * @param array $page Page data.
	 * @return boolean True on success.
	 */
	protected function storePageData(&$page) {
		// Prepare the temporary directory.
		if (!file_exists($this->temp_dir)) {
			// Directory doesn't exist. Create it.
			if (!mkdir($this->temp_dir, 0755, true)) {
				return false;
			}
		}

		// Store the page in its own temporary file.
		$page['temp_file'] = uniqid() . $page['api_title'];
		if (!$this->importTempContentSet($page['temp_file'], $page['content'])) {
			return false;
		}

		$page['content'] = null;
		unset($page['content']);

		return true;
	}

	abstract public function buildPagesData($file);
}
