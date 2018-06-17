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

// Import APP Packages
application::import("Invoices");
//#section_end#
//#section#[view]
use \UI\Apps\APPMIMEContent;
use \APP\Invoices\invoicePrint;

// Create MIMEContent to download the file
$mimeContent = new APPMIMEContent();

$invoiceID = engine::getVar("iid");
$invp = new invoicePrint($invoiceID);
$filename = $invp->getFilePath($type = "pdf");

// Download invoice pdf file
$mimeContent->set($filename, $type = APPMIMEContent::CONTENT_APP_STREAM, $mode = APPMIMEContent::TEAM_MODE, $shared = FALSE);
return $mimeContent->getReport($suggestedFileName = basename($filename), $ignore_user_abort = FALSE, $removeFile = FALSE);
//#section_end#
?>