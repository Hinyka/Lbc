<?php

/**
 * Lbc
 * 
 * Leverage browser caching library for 3rd party resources
 * 
 * @author Karel Hink <info@karelhink.cz>
 * @copyright (c) 2017, Karel Hink
 * @license LICENSE.txt New BSD License
 * @version 1.0
 */

namespace Hinyka\Lbc;

class LeverageBrowserCaching
{

	/**
	 *
	 * @var string Source file for download
	 */
	private $source;

	/**
	 *
	 * @var string The path of file where will be stored or is stored the downloaded file
	 */
	private $destination;

	/**
	 *
	 * @var string The base file name
	 */
	private $destinationFile;

	/**
	 *
	 * @var string The path to the folder for storing files
	 */
	private $destinationFolder = __DIR__ . DIRECTORY_SEPARATOR . "files";

	/**
	 *
	 * @var integer Time, in seconds, after which the file will be cached and will not be downloaded again
	 */
	private $cacheTime = 60 * 60 * 24; // 24 hours

	/**
	 *
	 * @var bolean The file has already been or not sometimes downloaded
	 */
	private $fileExists;

	/**
	 *
	 * @var string The contents of the downloaded file
	 */
	private $data;

	/**
	 *
	 * @var array Error messages.
	 */
	private $logMessage = array();

	/**
	 * The LeverageBrowserCaching class constructor
	 * 
	 * @param string $source
	 */
	public function __construct($source)
	{
		$this->source = $source;
		$this->destinationFile = pathinfo(parse_url($this->source, PHP_URL_PATH), PATHINFO_BASENAME);
		$this->setDestination();
	}

	/**
	 * Sets path to the file where will be stored or is stored the downloaded file
	 */
	private function setDestination()
	{
		$this->destination = $this->destinationFolder . DIRECTORY_SEPARATOR . $this->destinationFile;
	}

	/**
	 * Sets path to the folder for storing files
	 * 
	 * @param string $folder
	 */
	public function setDestinationFolder($folder)
	{
		if (!empty($folder) && is_dir($folder)) {
			$this->destinationFolder = $folder;
		} elseif (!empty($folder)) {
			if (mkdir($folder, NULL, TRUE)) {
				$this->destinationFolder = $folder;
			} else {
				$this->logMessage[] = "Failed to create your own path to the folder for storing files.";
			}
		}

		$this->setDestination();
	}

	/**
	 * Sets the maximum lifetime for cached files
	 * 
	 * @param integer $cacheTime
	 */
	public function setCacheTime($cacheTime)
	{
		if (is_int($cacheTime)) {
			$this->cacheTime = $cacheTime;
		}
	}

	/**
	 * Launch the application
	 * 
	 * @return boolean
	 */
	public function run()
	{
		if (!$this->checkAll()) {
			return FALSE;
		}

		if (!$this->downloadSource()) {
			return FALSE;
		}

		if (!$this->saveSource()) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Runs tests before downloading
	 * 
	 * @return boolean
	 */
	private function checkAll()
	{
		if (!$this->checkDestination()) {
			return FALSE;
		}

		if (!$this->checkHeaders()) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Verifying the existence of a saved file, write access and last modified time of the saved file
	 * 
	 * @return boolean
	 */
	private function checkDestination()
	{
		if (file_exists($this->destination) && is_writable($this->destination)) {
			$this->fileExists = TRUE;

			if (time() - filemtime($this->destination) < $this->cacheTime) {
				$this->logMessage[] = "Time of " . $this->cacheTime . " seconds, set for file ["
						. $this->source . "] caching, have not yet elapsed.";
				return FALSE;
			} else {
				return TRUE;
			}
		} elseif (!file_exists($this->destination) && is_writable($this->destinationFolder)) {
			return TRUE;
		} elseif (mkdir($this->destinationFolder, NULL, TRUE)) {
			return TRUE;
		} else {
			$this->logMessage[] = "Insufficient write permissions to the file [" . $this->destination . "].";
			return FALSE;
		}
	}

	/**
	 * Checks the HTTP Status Code
	 * 
	 * @return boolean
	 */
	private function checkHeaders()
	{
		$http_code = $this->getHeaders();

		if ($http_code === 200) {
			return TRUE;
		} elseif ($http_code === 304) {
			$this->logMessage[] = "Response code: [" . $http_code . "] File [" . $this->source . "] has not been modified.";
			return FALSE;
		} else {
			$this->logMessage[] = "Response code: [" . $http_code . "]";
			return FALSE;
		}
	}

	/**
	 * Download HTTP response headers
	 * 
	 * @return integer HTTP response code
	 */
	private function getHeaders()
	{
		$ch = curl_init($this->source);

		curl_setopt($ch, CURLOPT_NOBODY, 1);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 3);

		if ($this->fileExists) {
			curl_setopt($ch, CURLOPT_TIMEVALUE, filemtime($this->destination));
			curl_setopt($ch, CURLOPT_TIMECONDITION, CURL_TIMECOND_IFMODSINCE);
		}

		curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return $http_code;
	}

	/**
	 * Downloading the source file
	 * 
	 * @return boolean
	 */
	private function downloadSource()
	{
		$ch = curl_init($this->source);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 3);

		$this->data = curl_exec($ch);

		curl_close($ch);

		if (!empty($this->data)) {
			$this->logMessage[] = "File [" . $this->source . "] has been downloaded successfully.";
			return TRUE;
		} else {
			$this->logMessage[] = "File [" . $this->source . "] has not been downloaded successfully.";
			return FALSE;
		}
	}

	/**
	 * Store the downloaded file
	 * 
	 * @return boolean
	 */
	private function saveSource()
	{
		if (!$file = fopen($this->destination, "w")) {
			$this->logMessage[] = "Unable to open the [" . $this->destination . "] file for writing.";
			return FALSE;
		}
		if (!fwrite($file, $this->data)) {
			$this->logMessage[] = "Failed to write data to the [" . $this->destination . "] file.";
			return FALSE;
		}
		fclose($file);
		$this->logMessage[] = "The [" . $this->destination . "] file saved successfully.";
		return TRUE;
	}

	/**
	 * Logging errors
	 * 
	 * @param string $type Possible values are "screen", "console" or "file"
	 * @param string $logfile The path to the log file
	 * @return boolean
	 */
	public function log($type, $logfile = NULL)
	{
		$logTypes = array("screen", "console", "file");
		$logging = strtolower($type);

		if (!in_array($logging, $logTypes) || empty($this->logMessage)) {
			return FALSE;
		}

		$logContent = "";
		foreach ($this->logMessage as $message) {
			$logContent .= date("Y-m-d H:i:s", time()) . ": " . $message;
			if ($logging != "console") {
				$logContent .= "\n";
			} else {
				$logContent .= "\\n";
			}
		}

		// Display log messages on the screen
		if ($logging == "screen") {
			echo "<pre>" . $logContent . "</pre>";
		}
		// Display log messages on the browser's javascript console
		elseif ($logging == "console") {
			echo "<script>console.log('" . $logContent . "');</script>";
		}
		// Save log messages to the file
		elseif ($logging == "file") {
			// If the path to the log is empty, the log is written into the files path
			if (empty($logfile)) {
				$logfile = __DIR__ . DIRECTORY_SEPARATOR . "files" . DIRECTORY_SEPARATOR . "lbc.log";
			}

			$file = fopen($logfile, "a");
			fwrite($file, $logContent);
			fclose($file);
		}

		return TRUE;
	}

}
