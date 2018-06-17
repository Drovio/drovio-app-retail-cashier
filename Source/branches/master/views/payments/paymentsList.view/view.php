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

// Import APP Packages
//#section_end#
//#section#[view]
use \UI\Apps\APPContent;
use \UI\Forms\templates\simpleForm;
use \RTL\Invoices\invoicePayment;

// Create Application Content
$appContent = new APPContent();
$actionFactory = $appContent->getActionFactory();

if (engine::isPost())
{
	if (!$_POST['dltp'])
		return FALSE;
		
	// Check if it's for delete
	$invoiceID = engine::getVar("iid");
	$paymentID = engine::getVar("pid");
	$invp = new invoicePayment($invoiceID, $paymentID);
	$invp->remove();
	
	$appContent->addReportAction($name = "invoice.payments.list.reload", $value = "");
	$appContent->addReportAction($name = "invoice.payment_status.reload", $value = "");
	return $appContent->getReport();
}

// Build the application view content
$appContent->build("", "paymentsList");

// Get all invoice payments
$invoiceID = engine::getVar("iid");
$invp = new invoicePayment($invoiceID);
$payments = $invp->getAllPayments();
foreach ($payments as $paymentInfo)
{
	$ptile = DOM::create("div", "", "", "ptile");
	$appContent->append($ptile);
	
	// Delete payment form
	$form = new simpleForm();
	$deletePaymentForm = $form->build("", FALSE)->engageApp("payments/paymentsList")->get();
	HTML::addClass($deletePaymentForm, "removePaymentForm");
	DOM::append($ptile, $deletePaymentForm);
	
	$input = $form->getInput($type ="hidden", $name = "iid", $value = $invoiceID, $class = "", $autofocus = FALSE, $required = FALSE);
	$form->append($input);
	
	$input = $form->getInput($type ="hidden", $name = "pid", $value = $paymentInfo['id'], $class = "", $autofocus = FALSE, $required = FALSE);
	$form->append($input);
	
	$input = $form->getInput($type ="hidden", $name = "dltp", $value = 1, $class = "", $autofocus = FALSE, $required = FALSE);
	$form->append($input);
	
	// Submit button
	$submit = $form->getSubmitButton(NULL, "removeBtn");
	$form->append($submit);
	
	// Payment
	$payment = number_format($paymentInfo['payment'], 2);
	$ppay = DOM::create("div", $payment, "", "ppayment");
	DOM::append($ptile, $ppay);
	$currency = DOM::create("span", "€", "", "currency");
	DOM::append($ppay, $currency);
	
	// Type of payment
	$ptype = DOM::create("div", $paymentInfo['type'], "", "ptype");
	DOM::append($ptile, $ptype);
	
	// Notes
	$pnotes = DOM::create("div", $paymentInfo['notes'], "", "pnotes");
	DOM::append($ptile, $pnotes);
}

if (empty($payments))
{
	$hd = DOM::create("h2", "No Payments", "", "hd");
	$appContent->append($hd);
}

// Return output
return $appContent->getReport();
//#section_end#
?>