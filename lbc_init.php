<?php

/**
 * Lbc
 * 
 * Leverage browser caching library for 3rd party resources
 * 
 * @author Karel Hink <info@karelhink.cz>
 * @copyright (c) 2017, Karel Hink
 * @license LICENSE.md New BSD License
 * @version 1.1
 */

/**
 * Lbc class load
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'LeverageBrowserCaching.php';

/**
 * Source file for download (absolute URI)
 */
$files['google_analytics']['source'] = "http://www.google-analytics.com/analytics.js";

/**
 * Another source file for download
 */
// $files['ANOTHER_NAME']['source'] = "http://www.example.com/js/ANOTHER_FILE_NAME.js";

/**
 * File path to store log messages (optional)
 */
//$logfile = __DIR__ . DIRECTORY_SEPARATOR . "files" . DIRECTORY_SEPARATOR . "custom.log";


foreach ($files as $name => $file) {
	/**
	 * Create an instance of LBC
	 */
	$$name = new Hinyka\Lbc\LeverageBrowserCaching($file['source']);

	/**
	 * Configure your own path to the folder for storing files (optional)
	 */
	// $$name->setDestinationFolder(__DIR__ . DIRECTORY_SEPARATOR . "customFolder");

	/**
	 * Configure your own maximum lifetime for cached file (optional)
	 * The default value is: 60*60*24 (24 hours)
	 */
	// $$name->setCacheTime(60*60*24);

	/**
	 * Minify the downloaded file or not
	 * 
	 * Possible values are TRUE or FALSE. Default value is FALSE.
	 */
	$$name->setMinify(TRUE);

	/**
	 * Launch the application
	 */
	$$name->run();

	/**
	 * Enable logging (optional)
	 * 
	 * Possible values are "screen" "console" "file"
	 */
	//$$name->log("file", $logfile);
	$$name->log("file");
}