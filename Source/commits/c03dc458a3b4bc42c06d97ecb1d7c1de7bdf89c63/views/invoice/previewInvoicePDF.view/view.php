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
importer::import("AEL", "Resources");
importer::import("UI", "Apps");
importer::import("UI", "Content");
importer::import("UI", "Presentation");

// Import APP Packages
application::import("Invoices");
//#section_end#
//#section#[view]
use \AEL\Resources\filesystem\fileManager;
use \UI\Apps\APPContent;
use \UI\Content\HTMLFrame;
use \UI\Presentation\popups\popup;
use \APP\Invoices\invoicePrint;

// Create Application Content
$appContent = new APPContent();
$actionFactory = $appContent->getActionFactory();

// Get invoice id
$invoiceID = engine::getVar("iid");

// Build the application view content
$appContent->build("", "previewInvoiceDialog", TRUE);
$previewContainer = HTML::select(".previewInvoiceDialog .previewContainer")->item(0);

// Get invoice file path
$invp = new invoicePrint($invoiceID);
$filePath = $invp->getFilePath($type = "pdf");

// Get file contents
$fm = new fileManager($mode = fileManager::TEAM_MODE, $shared = FALSE);
$fileUrl = $fm->getPublicUrl($filePath);

// Create iframe
$frame = new HTMLFrame();
$frameElement = $frame->build($src = $fileUrl, $name = "invoice_preview", $id = "invoice_preview_".$invoiceID, $class = "pvframe", $sandbox = array())->get();
DOM::append($previewContainer, $frameElement);

// Create popup
$pp = new popup();
$pp->type($type = popup::TP_PERSISTENT, $toggle = FALSE);
$pp->background(TRUE);
$pp->build($appContent->get());

return $pp->getReport();
//#section_end#
?>