<?php
//#section#[header]
// Use Important Headers
use \API\Platform\importer;
use \API\Platform\engine;
use \Exception;

// Check Platform Existance
if (!defined('_RB_PLATFORM_')) throw new Exception("Platform is not defined!");

// Import DOM, HTML
importer::import("UI", "Html", "DOM");
importer::import("UI", "Html", "HTML");

use \UI\Html\DOM;
use \UI\Html\HTML;

// Import application for initialization
importer::import("AEL", "Platform", "application");
use \AEL\Platform\application;

// Increase application's view loading depth
application::incLoadingDepth();

// Set Application ID
$appID = 84;

// Init Application and Application literal
application::init(84);
// Secure Importer
importer::secure(TRUE);

// Import SDK Packages
importer::import("UI", "Apps");
importer::import("UI", "Forms");
importer::import("UI", "Presentation");

// Import APP Packages
application::import("Settings");
//#section_end#
//#section#[view]
use \UI\Apps\APPContent;
use \UI\Forms\templates\simpleForm;
use \UI\Forms\formReport\formErrorNotification;
use \UI\Forms\formReport\formNotification;
use \UI\Presentation\frames\dialogFrame;

use \APP\Settings\appSettings;

// Create Application Content
$appContent = new APPContent();

// Update settings
$settings = new appSettings();
if (engine::isPost())
{
	$has_error = FALSE;
	
	// Create form Notification
	$errFormNtf = new formErrorNotification();
	$formNtfElement = $errFormNtf->build()->get();
	
	if (empty($_POST['settings']['company_name']))
	{
		$has_error = TRUE;
		
		// Header
		$err_header = $appContent->getLiteral("settings", "lbl_companyName");
		$err = $errFormNtf->addHeader($err_header);
		$errFormNtf->addDescription($err, $errFormNtf->getErrorMessage("err.required"));
	}
	
	if ($has_error)
		return $errFormNtf->getReport();
	
	// Update settings
	foreach ($_POST['settings'] as $key => $value)
		$settings->set($key, $value);
	
	$succFormNtf = new formNotification();
	$succFormNtf->build($type = formNotification::SUCCESS, $header = TRUE, $timeout = TRUE, $disposable = TRUE);
	
	// Dispose start screen notification
	$succFormNtf->addReportAction("sscreen.notification.dispose");
	
	// Notification Message
	$errorMessage = $succFormNtf->getMessage("success", "success.save_success");
	$succFormNtf->append($errorMessage);
	return $succFormNtf->getReport($reset = FALSE);
}

// Build window dialog
$wFrame = new dialogFrame();
$title = $appContent->getLiteral("settings", "hd_applicationSettings");
$wFrame->build($title)->engageApp("settings/appSettingsPopup");
$form = $wFrame->getFormFactory();

// Get settings
$allSettings = $settings->get();

// Company name
$title = $appContent->getLiteral("settings", "lbl_companyName");
$input = $form->getInput($type = "text", $name = "settings[company_name]", $value = $allSettings['COMPANY_NAME'], $class = "", $autofocus = TRUE, $required = TRUE);
$form->insertRow($title, $input, $required = TRUE, $notes = "");

// Print header
$title = $appContent->getLiteral("settings", "lbl_print_header");
$notes = $appContent->getLiteral("settings", "lbl_print_header_notes");
$input = $form->getTextarea($name = "settings[print_header]", $value = $allSettings['PRINT_HEADER'], $class = "stext", $autofocus = FALSE, $required = TRUE);
$form->insertRow($title, $input, $required = TRUE, $notes);

// Print footer
$title = $appContent->getLiteral("settings", "lbl_print_footer");
$notes = $appContent->getLiteral("settings", "lbl_print_footer_notes");
$input = $form->getTextarea($name = "settings[print_footer]", $value = $allSettings['PRINT_FOOTER'], $class = "stext", $autofocus = FALSE, $required = TRUE);
$form->insertRow($title, $input, $required = TRUE, $notes);

return $wFrame->getFrame();
//#section_end#
?>