<?php
//#section#[header]
// Namespace
namespace APP\Settings;

require_once($_SERVER['DOCUMENT_ROOT'].'/_domainConfig.php');

// Use Important Headers
use \API\Platform\importer;
use \Exception;

// Check Platform Existance
if (!defined('_RB_PLATFORM_')) throw new Exception("Platform is not defined!");

// Import application loader
importer::import("AEL", "Platform", "application");
use \AEL\Platform\application;
//#section_end#
//#section#[class]
/**
 * @library	APP
 * @package	Settings
 * 
 * @copyright	Copyright (C) 2015 RetailCashier. All rights reserved.
 */

importer::import("AEL", "Resources", "appSettings");

use \AEL\Resources\appSettings as APISettings;

/**
 * Cashier application settings
 * 
 * Manages all application settings, mainly for the printing process.
 * 
 * @version	0.1-1
 * @created	October 6, 2015, 1:18 (EEST)
 * @updated	October 6, 2015, 1:18 (EEST)
 */
class appSettings extends APISettings
{
	/**
	 * Create a settings object instance.
	 * 
	 * @return	void
	 */
	public function __construct()
	{
		// Construct application settings in private mode for the team
		return parent::__construct($mode = self::TEAM_MODE, $shared = FALSE, $settingsFolder = "/Settings/", $filename = "ConfigSettings");
	}
}
//#section_end#
?>