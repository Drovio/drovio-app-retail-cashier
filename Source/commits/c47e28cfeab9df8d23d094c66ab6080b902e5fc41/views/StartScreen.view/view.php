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
importer::import("API", "Geoloc");
importer::import("API", "Profile");
importer::import("RTL", "Invoices");
importer::import("UI", "Apps");
importer::import("UI", "Forms");

// Import APP Packages
//#section_end#
//#section#[view]
use \API\Geoloc\datetimer;
use \API\Profile\account;
use \UI\Apps\APPContent;
use \UI\Forms\templates\simpleForm;
use \RTL\Invoices\invoice;

// Create Application Content
$appContent = new APPContent();
$actionFactory = $appContent->getActionFactory();

// Build the application view content
$appContent->build("", "cashierStartScreenContainer", TRUE);

// Get account information
$accountInfo = account::info();
$userInfo = HTML::select(".cashierStartScreen .user_title")->item(0);
$accountTitle = DOM::create("span", $accountInfo['accountTitle'], "", "title");
DOM::append($userInfo, $accountTitle);

// Create form to setup the invoice
$form = new simpleForm();
$formContainer = HTML::select(".cashierStartScreen .whiteBox .formContainer")->item(0);
$startForm = $form->build("", FALSE)->engageApp("MainView")->get();
DOM::append($formContainer, $startForm);

// Get all invoice types
$invoiceTypesResource = invoice::getInvoiceTypes($compact = TRUE);
$input = $form->getResourceSelect($name = "type_id", $multiple = FALSE, $class = "bginp", $invoiceTypesResource, $selectedValue = "");
$form->append($input);

// Set invoice date to auto or explicit
$dtContainer = DOM::create("div", "", "", "dtContainer");
$form->append($dtContainer);

$dtc = DOM::create("div", "", "auto", "dtc selected");
DOM::append($dtContainer, $dtc);

$title = $appContent->getLiteral("sscreen", "lbl_invoice_date_auto");
$rinput = $form->getInput($type = "radio", $name = "date_mode", $value = "auto", $class = "rinp auto", $autofocus = FALSE, $required = FALSE);
DOM::append($dtc, $rinput);
DOM::attr($rinput, "checked", "checked");
$rinputID = DOM::attr($rinput, "id");
$label = $form->getLabel($title, $for = $rinputID, $class = "rlabel");
DOM::append($dtc, $label);

$dtc = DOM::create("div", "", "explicit", "dtc");
DOM::append($dtContainer, $dtc);

$title = $appContent->getLiteral("sscreen", "lbl_invoice_date_explicit");
$rinput = $form->getInput($type = "radio", $name = "date_mode", $value = "explicit", $class = "rinp explicit", $autofocus = FALSE, $required = FALSE);
DOM::append($dtc, $rinput);
$rinputID = DOM::attr($rinput, "id");
$label = $form->getLabel($title, $for = $rinputID, $class = "rlabel");
DOM::append($dtc, $label);

// Set invoice date (optional)
$date_value = date("Y-m-d", time());
$input = $form->getInput($type = "date", $name = "invoice_date", $value = $date_value, $class = "bginp dt auto", $autofocus = FALSE, $required = FALSE);
DOM::append($dtContainer, $input);


// Set invoice number to auto or explicit
$dtContainer = DOM::create("div", "", "", "dtContainer");
$form->append($dtContainer);

$dtc = DOM::create("div", "", "auto", "dtc selected");
DOM::append($dtContainer, $dtc);

$title = $appContent->getLiteral("sscreen", "lbl_invoice_number_auto");
$rinput = $form->getInput($type = "radio", $name = "icode_mode", $value = "auto", $class = "rinp auto", $autofocus = FALSE, $required = FALSE);
DOM::append($dtc, $rinput);
DOM::attr($rinput, "checked", "checked");
$rinputID = DOM::attr($rinput, "id");
$label = $form->getLabel($title, $for = $rinputID, $class = "rlabel");
DOM::append($dtc, $label);

$dtc = DOM::create("div", "", "explicit", "dtc");
DOM::append($dtContainer, $dtc);

$title = $appContent->getLiteral("sscreen", "lbl_invoice_number_explicit");
$rinput = $form->getInput($type = "radio", $name = "icode_mode", $value = "explicit", $class = "rinp explicit", $autofocus = FALSE, $required = FALSE);
DOM::append($dtc, $rinput);
$rinputID = DOM::attr($rinput, "id");
$label = $form->getLabel($title, $for = $rinputID, $class = "rlabel");
DOM::append($dtc, $label);

// Set invoice number manual (optional)
$input = $form->getInput($type = "number", $name = "invoice_code", $value = 1, $class = "bginp dt auto", $autofocus = FALSE, $required = FALSE);
DOM::append($dtContainer, $input);


// Submit button
$title = $appContent->getLiteral("sscreen", "lbl_startInvoice");
$button = $form->getSubmitButton($title, $id = "btn_start_invoice", $name = "");
$form->append($button);


// Get all current pending invoices for the current user
$pendingContainer = HTML::select(".cashierStartScreen .pending_invoices")->item(0);
$pendingInvoices = invoice::getAllPendingInvoices($fromTime = "0", $toTime = "", $accountID = account::getAccountID());
foreach ($pendingInvoices as $invoiceInfo)
{
	$pinv = DOM::create("div", "", "", "pinv");
	DOM::append($pendingContainer, $pinv);
	
	// Get invoice type
	$invoiceType = $invoiceTypesResource[$invoiceInfo['type_id']];
	$title = DOM::create("div", $invoiceType, "", "title");
	DOM::append($pinv, $title);
	
	// Invoice time created
	$live = datetimer::live($invoiceInfo['time_created']);
	$date = DOM::create("div", $live, "", "date");
	DOM::append($pinv, $date);
	
	// Set action to load main view
	$attr = array();
	$attr['iid'] = $invoiceInfo['id'];
	$actionFactory->setAction($pinv, "MainView", "", $attr);
}

// Return output
return $appContent->getReport($holder = ".retailCashierApplication .mainViewContainer", $method = APPContent::REPLACE_METHOD);
//#section_end#
?>