<?php
class Zip {
	/**
	 * Whether the unzipping encountered errors.
	 * @var boolean
	 */
	private $error = false;
	/**
	 * Whether the extraction was successful.
	 * @var boolean
	 */
	private $extracted = false;
	/**
	 * Path to zipped archive.
	 * @var string
	 */
	private $filename;
	/**
	 * Array of files in the archive.
	 * @var array
	 */
	private $files = array();
	/**
	 * The main import file from the zip without its extension.
	 * @var string
	 */
	private $import_file;
	/**
	 * Array of folders in the archive.
	 * @var array
	 */
	private $folders = array();
	/**
	 * ZipArchive object.
	 * @var object
	 */
	private $zip;
	/**
	 * Whether the archive was unzipped.
	 * @var boolean
	 */
	public $unzipped = true;

	/**
	 * Creates the zip object.
	 * @param string $filename Path to filename to check and unzip.
	 */
	public function __construct($filename) {
		$this->filename = $filename;
		$this->zip = new ZipArchive();
		$this->error = $this->zip->open($this->filename);
		if ($this->error !== true) {
			$this->unzipped = false;
		}
		if ($this->unzipped) {
			$this->setFilesAndFolders();
		}
	}

	/**
	 * Makes sure the archive matches the requirements.
	 * Requirements:
	 * 	* 1 file in the main level. This is the import file.
	 * 	* 1 folder containing the assets.
	 * @param array $extensions Allowed extensions for the import file.
	 * @return mixed True on success. Error message on failure.
	 */
	public function checkImportFormat($extensions = array(), $zip_file = '') {
		if (!$this->unzipped) {
			return 'The archive could not be unzipped.';
		}

		// Count the folders.
		if (count($this->folders) > 2) {
			return 'The zipped archive can contain only one folder.';
		}

		// Count the base files.
		if (count($this->files['.']) > 1) {
			return 'The zipped archive can contain only one import file.';
		}
		if (!empty($zip_file)) {
			$zip_file_found = false;
			foreach ($this->files as $folder => $files) {
				if (in_array($zip_file, $files)) {
					$zip_file_found = true;
					break;
				}
			}
			if (!$zip_file_found) {
				return 'The zip master file was not found.';
			}
		} else {
			if (count($this->files['.']) < 1) {
				return 'The import file is missing.';
			}

			// Check the base file's extension.
			if (!empty($extensions)) {
				$pathinfo = pathinfo($this->files['.'][0]);
				if (!in_array($pathinfo['extension'], $extensions)) {
					return 'The import file is not an accepted file format.';
				}
			}
		}

		return true;
	}

	/**
	 * Extracts the archive's contents to the given path.
	 * @param string $path Path to extract the archive to.
	 */
	public function extract($path) {
		$this->zip->extractTo($path);
		$this->zip->close();
		$this->extracted = true;
	}

	/**
	 * Returns the files array.
	 * @return array Array of files in the archive.
	 */
	public function getFiles() {
		return $this->files;
	}

	/**
	 * Returns the folders array.
	 * @return array Array of folders in the archive.
	 */
	public function getFolders() {
		return $this->folders;
	}

	/**
	 * Returns the import file.
	 * @return string The main import file from the zip without its extension.
	 */
	public function getImportFile() {
		return $this->import_file;
	}

	/**
	 * After extracting, make sure the assets folder is named correctly.
	 * @return boolean True on success.
	 */
	public function renameAssetFolder($path, $zip_file) {
		if (!$this->extracted) {
			return false;
		}

		// Get the name of the assets folder.
		if (!empty($this->folders[1])
			&& $this->folders[1] !== '.'
		) {
			$assets_folder = $this->folders[1];
		} else {
			$assets_folder = $this->folders[0];
		}

		// Put the assets in the expected folder.
		if ($assets_folder !== $this->import_file . '_files') {
			// Delete the existing directory and its files.
			if (is_dir($path . '/' . $this->import_file . '_files')) {
				remove_directory($path . '/' . $this->import_file . '_files');
			}

			// Change the folder's name.
			if (!rename($path . '/' . $assets_folder, $path . '/' . $this->import_file . '_files')) {
				// The folder may already exist. Rename doesn't overwrite. Copy instead.
				if (copy($path .'/' .  $assets_folder, $path . '/' . $this->import_file . '_files')) {
					remove_directory($path .'/' . $assets_folder);
					return true;
				}
				return false;
			}
		}

		// Deal with the zip_file.
		if (!empty($zip_file)) {
			$pathinfo = pathinfo($zip_file);
			if ($assets_folder === $this->import_file . '_files') {
				$assets_folder = substr($assets_folder, -6);
			}
			copy($path . '/' . $this->import_file . '_files/' . $zip_file, $path . '/' . $assets_folder . '.' . $pathinfo['extension']);
		}

		return true;
	}

	function moveMasterZipFile($zip_file) {

	}

	/**
	 * Sets the files and folders arrays.
	 */
	private function setFilesAndFolders() {
		// Reset the files and folders arrays.
		$this->files = array();
		$this->folders = array();
		for ($i = 0; $i < $this->zip->numFiles; $i++) {
			// Get information on the current item in the archive.
			$stat = $this->zip->statIndex($i);
			$pathinfo = pathinfo($stat['name']);

			// Store each of the files in the archive.
			if (!is_array($this->files[$pathinfo['dirname']])) {
				$this->files[$pathinfo['dirname']] = array();
			}
			$this->files[$pathinfo['dirname']][] = $pathinfo['basename'];
		}

		// Store the folders in the archive.
		$this->folders = array_keys($this->files);

		// Store the import file without an extension.
		if (!empty($this->files['.'][0])) {
			$pathinfo = pathinfo($this->files['.'][0]);
			$this->import_file = $pathinfo['filename'];
		} else {
			reset($this->files);
			$this->import_file = key($this->files);
		}
	}
}
