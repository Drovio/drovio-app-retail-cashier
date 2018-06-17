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

// Import APP Packages
//#section_end#
//#section#[view]
use \UI\Apps\APPContent;
use \RTL\Invoices\invoice;

// Create Application Content
$appContent = new APPContent();
$actionFactory = $appContent->getActionFactory();

// Build the application view content
$appContent->build("", "paymentsStatusContainer", TRUE);

// Get invoice info
$invoiceID = engine::getVar("iid");
$invoice = new invoice($invoiceID);

// Get status payments
$st_subtotal = HTML::select(".paymentsStatus .status .st.subtotal")->item(0);
$attr = array();
$attr['total'] = number_format($invoice->getTotalPrice(), 2);
$attr['cur'] = "€";
$subtotal = $appContent->getLiteral("payments.status", "status_total", $attr);
DOM::append($st_subtotal, $subtotal);

$st_subtotal = HTML::select(".paymentsStatus .status .st.paid")->item(0);
$attr = array();
$attr['total'] = number_format($invoice->getTotalPayments(), 2);
$attr['cur'] = "€";
$subtotal = $appContent->getLiteral("payments.status", "status_paid", $attr);
DOM::append($st_subtotal, $subtotal);

$st_subtotal = HTML::select(".paymentsStatus .status .st.balance")->item(0);
$attr = array();
$attr['total'] = number_format($invoice->getBalance(), 2);
$attr['cur'] = "€";
$subtotal = $appContent->getLiteral("payments.status", "status_balance", $attr);
DOM::append($st_subtotal, $subtotal);


// Actions
$attr = array();
$attr['iid'] = $invoiceID;

// Add payment action
$addPaymentButton = HTML::select(".paymentsStatus .rtlbutton.btn-add-payment")->item(0);
$actionFactory->setAction($addPaymentButton, "payments/addPaymentDialog", "", $attr);

// Complete invoice
$completeButton = HTML::select(".paymentsStatus .rtlbutton.btn-complete")->item(0);
$actionFactory->setAction($completeButton, "invoice/completeInvoiceDialog", "", $attr);

// Cancel invoice
$completeButton = HTML::select(".paymentsStatus .rtlbutton.btn-cancel")->item(0);
$actionFactory->setAction($completeButton, "invoice/cancelInvoiceDialog", "", $attr);

// Return output
return $appContent->getReport();
//#section_end#
?>