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
$appContent->build("", "invoiceInfoContainer", TRUE);

// Get invoive info
$invoiceID = engine::getVar("iid");
$invoice = new invoice($invoiceID);
$invoiceInfo = $invoice->info();

$valueHolder = HTML::select(".irow.invoice_type .value")->item(0);
DOM::innerHTML($valueHolder, $invoiceInfo['type']);

$valueHolder = HTML::select(".irow.invoice_id .value")->item(0);
DOM::innerHTML($valueHolder, $invoiceInfo['invoice_id']);

$invoiceDate = $invoiceInfo['date_created'];
if (empty($invoiceDate))
	$invoiceDate = date("d F, Y \a\t H:i", $invoiceInfo['time_created']);
$valueHolder = HTML::select(".irow.invoice_date .value")->item(0);
DOM::innerHTML($valueHolder, $invoiceDate);

$valueHolder = HTML::select(".irow.invoice_seller .value")->item(0);
DOM::innerHTML($valueHolder, $invoiceInfo['seller_info']);

$valueHolder = HTML::select(".irow.invoice_notes .value")->item(0);
DOM::innerHTML($valueHolder, $invoiceInfo['notes']);

// Edit invoice info
$addCustomerBtn = HTML::select(".invoiceInfo .rtlbutton.btn-edit-invoice")->item(0);
$attr = array();
$attr['iid'] = $invoiceID;
$actionFactory->setAction($addCustomerBtn, "invoice/editInvoiceInfo", "", $attr);

// Return output
return $appContent->getReport();
//#section_end#
?>