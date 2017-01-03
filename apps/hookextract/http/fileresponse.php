<?php
namespace OCA\HookExtract\Http;

use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\DownloadResponse;

class FileResponse extends DownloadResponse {	

	private $filename;
	private $contentType;
	private $content;
	
	/**
	 * Creates a response that prompts the user to download the file
	 * @param string $filename the name that the downloaded file should have
	 * @param string $contentType the mimetype that the downloaded file should have
	 */
	public function __construct($filename, $contentType, $content) {
		$this->filename = $filename;
		$this->contentType = $contentType;
		$this->content = $content;
	
		$this->addHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
		$this->addHeader('Content-Type', $contentType);
	}

	public function render() {
		return $this->content;
	}

}