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
	
	// Update invoice info
	$invp = new invoice($invoiceID);
	
	// Set seller info
	$invp->setSellerInfo($_POST['seller_info']);
	
	// Gather notes
	$notes = "";
	$notes .= (!empty($_POST['account_number']) ? "Account Number: ".$_POST['account_number']."\n" : "");
	$notes .= (!empty($_POST['pay_date']) ? "To Be Paid: ".$_POST['to_be_paid']."\n" : "");
	$notes .= $_POST['notes'];
	$invp->setNotes($notes);
	
	$succFormNtf = new formNotification();
	$succFormNtf->build($type = formNotification::SUCCESS, $header = TRUE, $timeout = TRUE, $disposable = TRUE);
	
	// Reload invoice info
	$succFormNtf->addReportAction("invoice.info.reload");
	
	// Notification Message
	$errorMessage = $succFormNtf->getMessage("success", "success.save_success");
	$succFormNtf->append($errorMessage);
	return $succFormNtf->getReport();
}

// Build dialog
$dialogFrame = new dialogFrame();
$title = $appContent->getLiteral("invoice.editor", "hd_editInvoiceInfo");
$dialogFrame->build($title)->engageApp("invoice/editInvoiceInfo");
$form = $dialogFrame->getFormFactory();

// Get invoice info
$invoice = new invoice($invoiceID);
$invoiceInfo = $invoice->info();

// Add invoice id
$input = $form->getInput($type = "hidden", $name = "iid", $value = $invoiceID, $class = "", $autofocus = FALSE, $required = FALSE);
$form->append($input);

// Complete invoice
$input = $form->getInput($type = "hidden", $name = "icplt", $value = 1, $class = "", $autofocus = FALSE, $required = FALSE);
$form->append($input);

$title = $appContent->getLiteral("invoice.editor", "lbl_sellerInfo");
$input = $form->getInput($type = "text", $name = "seller_info", $value = $invoiceInfo['seller_info'], $class = "", $autofocus = FALSE, $required = FALSE);
$form->insertRow($title, $input, $required = FALSE, $notes = "");

$title = $appContent->getLiteral("invoice.editor", "lbl_accountNumber");
$input = $form->getInput($type = "text", $name = "account_number", $value = "", $class = "", $autofocus = FALSE, $required = FALSE);
$form->insertRow($title, $input, $required = FALSE, $notes = "");

$title = $appContent->getLiteral("invoice.editor", "lbl_payDate");
$input = $form->getInput($type = "date", $name = "pay_date", $value = "", $class = "", $autofocus = FALSE, $required = FALSE);
$form->insertRow($title, $input, $required = FALSE, $notes = "");

$title = $appContent->getLiteral("invoice.editor", "lbl_notes");
$input = $form->getTextarea($name = "notes", $value = $invoiceInfo['notes'], $class = "", $autofocus = FALSE, $required = FALSE);
$form->insertRow($title, $input, $required = FALSE, $notes = "");

return $dialogFrame->getFrame();
//#section_end#
?>