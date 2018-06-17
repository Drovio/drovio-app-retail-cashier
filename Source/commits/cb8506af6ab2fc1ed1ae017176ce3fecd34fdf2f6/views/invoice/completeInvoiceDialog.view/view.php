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
application::import("Invoices");
//#section_end#
//#section#[view]
use \UI\Apps\APPContent;
use \UI\Forms\templates\simpleForm;
use \UI\Forms\formReport\formNotification;
use \UI\Forms\formReport\formErrorNotification;
use \UI\Presentation\popups\popup;
use \RTL\Invoices\invoice;
use \APP\Invoices\invoicePrint;

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
	$export = TRUE;
	$double = (isset($_POST['dcopy']) || $_POST['dcopy'] == "on");
	
	// If error, show notification
	if ($has_error)
		return $errFormNtf->getReport();
	
	// Close/complete invoice (for editing)
	$invp = new invoice($invoiceID);
	$status = $invp->close();
	// If there is an error in adding the payment, show it
	if (!$status)
	{
		$err_header = $appContent->getLiteral("main.invoice", "hd_completeInvoice");
		$err = $errFormNtf->addHeader($err_header);
		$errFormNtf->addDescription($err, DOM::create("span", "Error completing invoice..."));
		return $errFormNtf->getReport();
	}
	
	// Export invoice to pdf
	$invp = new invoicePrint($invoiceID);
	$invp->exportPDF($shared = FALSE, $double);
	
	// Create content
	$appContent->build("", "downloader");
	
	// Add header
	$attr = array();
	$attr['fname'] = "Private Folder > ".$invp->getFilePath($type = "pdf");
	$title = $appContent->getLiteral("main.invoice", "lbl_exportFilename", $attr);
	$hd = DOM::create("h2", $title, "", "hd");
	$appContent->append($hd);
	
	// Create download button with attributes
	$title = $appContent->getLiteral("main.invoice", "lbl_downloadFile");
	$dlButton = DOM::create("div", $title, "btn_download");
	$attr = array();
	$attr['iid'] = $invoiceID;
	$actionFactory->setDownloadAction($dlButton, $viewName = "invoice/downloadInvoicePDF", $attr);
	$appContent->append($dlButton);
	
	return $appContent->getReport($holder = ".completeInvoiceDialog .downloaderContainer");
}

// Build the application view content
$appContent->build("", "completeInvoiceDialog", TRUE);

$formContainer = HTML::select(".completeInvoiceDialog .formContainer")->item(0);
// Build form
$form = new simpleForm("");
$imageForm = $form->build($action = "", $defaultButtons = FALSE)->engageApp("invoice/completeInvoiceDialog")->get();
DOM::append($formContainer, $imageForm);

// Add invoice id
$input = $form->getInput($type = "hidden", $name = "iid", $value = $invoiceID, $class = "", $autofocus = FALSE, $required = FALSE);
$form->append($input);

// Complete invoice
$input = $form->getInput($type = "hidden", $name = "icplt", $value = 1, $class = "", $autofocus = FALSE, $required = FALSE);
$form->append($input);

$title = $appContent->getLiteral("main.invoice", "lbl_doubleCopy");
$input = $form->getInput($type = "checkbox", $name = "dcopy", $value = 1, $class = "", $autofocus = FALSE, $required = FALSE);
$form->insertRow($title, $input, $required = FALSE, $notes = "");


$title = $appContent->getLiteral("main.invoice", "lbl_closeInvoice");
$create_btn = $form->getSubmitButton($title, $id = "btn_close", $name = "");
$form->append($create_btn);

// Create popup
$pp = new popup();
$pp->type($type = popup::TP_PERSISTENT, $toggle = FALSE);
$pp->background(TRUE);
$pp->build($appContent->get());

return $pp->getReport();
//#section_end#
?>