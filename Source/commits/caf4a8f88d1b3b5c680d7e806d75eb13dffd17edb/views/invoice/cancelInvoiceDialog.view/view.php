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
importer::import("RTL", "Invoices");
importer::import("UI", "Apps");
importer::import("UI", "Forms");
importer::import("UI", "Presentation");

// Import APP Packages
//#section_end#
//#section#[view]
use \UI\Apps\APPContent;
use \UI\Forms\formReport\formErrorNotification;
use \UI\Forms\formReport\formNotification;
use \UI\Presentation\frames\dialogFrame;
use \RTL\Invoices\invoice;

// Create Application Content
$appContent = new APPContent();
$actionFactory = $appContent->getActionFactory();

// Get invoice id
$invoiceID = engine::getVar("iid");
if (engine::isPost())
{
	// Validate invoice
	$has_error = FALSE;
	
	// Create form Notification
	$errFormNtf = new formErrorNotification();
	$formNtfElement = $errFormNtf->build()->get();
	
	// Check session
	
	// If error, show notification
	if ($has_error)
		return $errFormNtf->getReport();
	
	// Delete invoice
	$invp = new invoice($invoiceID);
	$status = $invp->remove();
	
	// If there is an error in adding the payment, show it
	if (!$status)
	{
		$err_header = $appContent->getLiteral("main.invoice", "hd_deleteInvoice");
		$err = $errFormNtf->addHeader($err_header);
		$errFormNtf->addDescription($err, DOM::create("span", "Error removing invoice..."));
		return $errFormNtf->getReport();
	}
	
	$succFormNtf = new formNotification();
	$succFormNtf->build($type = formNotification::SUCCESS, $header = TRUE, $timeout = TRUE, $disposable = TRUE);
	
	// Reload payments and invoice payment status
	$succFormNtf->addReportAction("invoice.close");
	
	// Notification Message
	$errorMessage = $succFormNtf->getMessage("success", "success.save_success");
	$succFormNtf->append($errorMessage);
	return $succFormNtf->getReport();
}

// Build dialog
$dialogFrame = new dialogFrame();
$title = $appContent->getLiteral("main.invoice", "hd_deleteInvoice");
$dialogFrame->build($title)->engageApp("invoice/cancelInvoiceDialog");
$form = $dialogFrame->getFormFactory();

// Add invoice id
$input = $form->getInput($type = "hidden", $name = "iid", $value = $invoiceID, $class = "", $autofocus = FALSE, $required = FALSE);
$form->append($input);

// Delete invoice
$input = $form->getInput($type = "hidden", $name = "idlt", $value = 1, $class = "", $autofocus = FALSE, $required = FALSE);
$form->append($input);

// Add header
$title = $appContent->getLiteral("main.invoice", "lbl_deleteInvoice");
$hd = DOM::create("h2", $title, "", "hd_del_title");
$dialogFrame->append($hd);

return $dialogFrame->getFrame();
//#section_end#
?>