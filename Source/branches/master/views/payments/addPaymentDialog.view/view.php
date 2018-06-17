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
use \UI\Forms\templates\simpleForm;
use \UI\Forms\formReport\formErrorNotification;
use \UI\Forms\formReport\formNotification;
use \UI\Presentation\frames\windowFrame;
use \RTL\Invoices\invoicePayment;

// Create Application Content
$appContent = new APPContent();
$actionFactory = $appContent->getActionFactory();

// Get invoice id
$invoiceID = engine::getVar("iid");
if (engine::isPost())
{
	$has_error = FALSE;
	
	// Create form Notification
	$errFormNtf = new formErrorNotification();
	$formNtfElement = $errFormNtf->build()->get();
	
	// Check payment
	$payment = $_POST['payment'];
	if (empty($payment))
	{
		$has_error = TRUE;
		
		// Header
		$err_header = $appContent->getLiteral("payments.dialog", "lbl_payment_amount");
		$err = $errFormNtf->addHeader($err_header);
		$errFormNtf->addDescription($err, $errFormNtf->getErrorMessage("err.required"));
	}
	$payment = $_POST['type_id'];
	if (empty($payment))
	{
		$has_error = TRUE;
		
		// Header
		$err_header = $appContent->getLiteral("payments.dialog", "lbl_payment_type");
		$err = $errFormNtf->addHeader($err_header);
		$errFormNtf->addDescription($err, $errFormNtf->getErrorMessage("err.required"));
	}
	
	// If error, show notification
	if ($has_error)
		return $errFormNtf->getReport();
	
	// Add the payment
	$invp = new invoicePayment($invoiceID);
	$status = $invp->add($_POST['type_id'], $_POST['payment'], $notes = $_POST['notes'], $referenceID = $_POST['ref_id']);
	
	
	// If there is an error in adding the payment, show it
	if (!$status)
	{
		$err_header = $appContent->getLiteral("payments.dialog", "hd_addPayment");
		$err = $errFormNtf->addHeader($err_header);
		$errFormNtf->addDescription($err, DOM::create("span", "Error adding payment..."));
		return $errFormNtf->getReport();
	}
	
	$succFormNtf = new formNotification();
	$succFormNtf->build($type = formNotification::SUCCESS, $header = TRUE, $timeout = TRUE, $disposable = TRUE);
	
	// Reload payments and invoice payment status
	$succFormNtf->addReportAction("invoice.payments.list.reload");
	$succFormNtf->addReportAction("invoice.payment_status.reload");
	
	// Notification Message
	$errorMessage = $succFormNtf->getMessage("success", "success.save_success");
	$succFormNtf->append($errorMessage);
	return $succFormNtf->getReport();
}

// Build the application view content
$appContent->build("", "addPaymentDialogContainer", TRUE);

// List all current payments
$plistContainer = HTML::select(".addPaymentDialog .payment-list-container")->item(0);
$attr = array();
$attr['iid'] = $invoiceID;
$viewContainer = $appContent->getAppViewContainer($viewName = "/payments/paymentsList", $attr, $startup = TRUE, $containerID = "paymentListOuterContainer", $loading = TRUE, $preload = TRUE);
DOM::append($plistContainer, $viewContainer);

// Add new payment form
$form = new simpleForm();
$formContainer = HTML::select(".addPaymentDialog .newPaymentFormContainer")->item(0);
$addPaymentForm = $form->build("", FALSE)->engageApp("payments/addPaymentDialog")->get();
DOM::append($formContainer, $addPaymentForm);

// invoice id
$input = $form->getInput($type = "hidden", $name = "iid", $value = $invoiceID, $class = "bginp", $autofocus = FALSE, $required = FALSE);
$form->append($input);

// Get all payment types
$paymentTypesResource = invoicePayment::getAllPaymentTypes();
$input = $form->getResourceSelect($name = "type_id", $multiple = FALSE, $class = "bginp", $paymentTypesResource, $selectedValue = "");
$form->append($input);

// Add payment
$ph = $appContent->getLiteral("payments.dialog", "lbl_payment_amount", array(), FALSE);
$input = $form->getInput($type = "text", $name = "payment", $value = "", $class = "bginp", $autofocus = TRUE, $required = TRUE);
DOM::attr($input, "placeholder", $ph);
$form->append($input);

$title = $appContent->getLiteral("payments.dialog", "lbl_pay");
$button = $form->getSubmitButton($title, $id = "btn_add_payment", $name = "");
$form->append($button);


// Build window dialog
$wFrame = new windowFrame();
$title = $appContent->getLiteral("payments.dialog", "hd_addPayment");
return $wFrame->build($title)->append($appContent->get())->getFrame();
//#section_end#
?>