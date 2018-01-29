<?php
class Upload {
	/**
	 * The default allowed extensions.
	 * @var array
	 */
	private $allowed = array(
		'doc',
		'docx',
		'htm',
		'html',
		'json',
		'txt',
		'xml'
	);
	private $error = '';
	private $errors = array(
		'too_big_ini' => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
		'too_big_html' => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
		'partial' => "The uploaded was only partially uploaded.",
		'missing' => "No file was uploaded.",
		'no_tmp_dir' => "The temporary folder is missing.",
		'cant_write' => "Failed to write the file to disk.",
		'extension' => "That file type is not allowed.",
		'unknown' => "Unknown upload error.",
		'moving' => "Moving the file to the import directory failed.",
		'zip_rename' => "The zip archives assets folder could not be stored."
	);
	private $import_file = '';
	private $file;
	private $uploaded = false;
	private $Zip;
	private $zip_file = '';

	/**
	 * Validates the upload.
	 * @param array $file File array from $_FILES.
	 * @param array $allowed Overwrite the default allowed extensions.
	 */
	public function __construct($file, $allowed = '', $zip_file = '') {
		// Set uploaded to true.
		$this->uploaded = true;

		// Store the file.
		$this->file = $file;
		$this->zip_file = $zip_file;

		// Check for empty upload.
		if (empty($this->file['name'])) {
			$this->uploaded = false;
			$this->error = $this->errors['missing'];
			return;
		}

		// Store and check the file upload error field.
		switch($this->file['error']) {
			case UPLOAD_ERR_OK: 
			break;
			case UPLOAD_ERR_INI_SIZE:
				$this->uploaded = false;
				$this->error = $this->errors['too_big_ini'];
			break;
			case UPLOAD_ERR_FORM_SIZE:
				$this->uploaded = false;
				$this->error = $this->errors['too_big_html'];
			break;
			case UPLOAD_ERR_PARTIAL:
				$this->uploaded = false;
				$this->error = $this->errors['partial'];
			break;
			case UPLOAD_ERR_NO_FILE:
				$this->uploaded = false;
				$this->error = $this->errors['missing'];
			break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$this->uploaded = false;
				$this->error = $this->errors['no_tmp_dir'];
			break;
			case UPLOAD_ERR_CANT_WRITE:
				$this->uploaded = false;
				$this->error = $this->errors['cant_write'];
			break;
			case UPLOAD_ERR_EXTENSION:
				$this->uploaded = false;
				$this->error = $this->errors['extension'];
			break;
			default:
				$this->uploaded = false;
				$this->error = $this->errors['unknown'];
			break;
		}

		// Exit the function when an error was found.
		if (!$this->uploaded) {
			return;
		}

		// Deal with extensions.
		if (!empty($allowed) && is_array($allowed)) {
			$this->allowed = $allowed;
		}

		// Make sure the extension is allowed.
		$pathinfo = pathinfo($this->file['name']);
		$this->file['extension'] = $pathinfo['extension'];
		$this->file['filename'] = $pathinfo['filename'];
		if (!in_array($this->file['extension'], $this->allowed)) {
			$this->uploaded = false;
			$this->error = $this->errors['extension'];
			return;
		}
	}

	/**
	 * Returns the error message.
	 * @return string $error.
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Returns uploaded file array.
	 * @return array $file
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * Returns import file without its extension.
	 * @return array $file
	 */
	public function getImportFile() {
		return $this->import_file;
	}

	/**
	 * Moves the uploaded file to the destination.
	 * @param string $destination Path where the file should be moved to.
	 * @param string $name Optional name of the file.
	 * @return boolean True on success.
	 */
	public function move($destination, $name = '') {
		if (!$this->uploaded) {
			return false;
		}

		if (empty($name)) {
			$name = $this->file['name'];
		}

		if ($this->file['extension'] === 'zip') {
			// Open the zipped package and get the folder and file list.
			$this->Zip = new Zip($this->file['tmp_name']);
			$folders = $this->Zip->getFolders();
			$files = $this->Zip->getFiles();

			// Build the allowed extensions without zip.
			$extensions = array();
			foreach ($this->allowed as $allowed) {
				if ($allowed === 'zip') {
					continue;
				}
				$extensions[] = $allowed;
			}

			// Make sure the import file has an allowed extension.
			$check = $this->Zip->checkImportFormat($extensions, $this->zip_file);
			if ($check !== true) {
				$this->error = $check;
				return false;
			}

			// Extract the contents to the destination.
			$this->Zip->extract($destination);
			if (!$this->Zip->renameAssetFolder($destination, $this->zip_file)) {
				$this->error = $this->errors['zip_rename'];
				return false;
			} else {
				$this->import_file = $this->Zip->getImportFile();
				return true;
			}
		} else {
			// Move the uploaded file.
			if (move_uploaded_file($this->file['tmp_name'], $destination . $name)) {
				$this->import_file = $this->file['filename'];
				return true;
			} else {
				$this->error = $this->errors['moving'];
				return false;
			}
		}
	}

	/**
	 * Returns the status of the upload.
	 * @return boolean
	 */
	public function uploaded() {
		return $this->uploaded;
	}
}
