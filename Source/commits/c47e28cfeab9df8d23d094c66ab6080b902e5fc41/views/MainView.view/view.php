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
importer::import("RTL", "Profile");
importer::import("UI", "Apps");
importer::import("UI", "Forms");

// Import APP Packages
//#section_end#
//#section#[view]
use \UI\Apps\APPContent;
use \UI\Forms\templates\simpleForm;
use \UI\Forms\formReport\formErrorNotification;
use \RTL\Invoices\invoice;
use \RTL\Profile\company;

// Create Application Content
$appContent = new APPContent();
$actionFactory = $appContent->getActionFactory();

// Build the application view content
$appContent->build("", "retailCashierApplicationContainer", TRUE);

// Get invoice id to initialize
$invoiceID = engine::getVar("iid");

// Create invoice
if (engine::isPost())
{
	// Register team as company
	company::register();

	// Get posted information
	$invoiceDate = NULL;
	if ($_POST['date_mode'] == "explicit")
		$invoiceDate = $_POST['invoice_date'];
	
	$typeInvoiceID = "";
	if ($_POST['icode_mode'] == "explicit")
		$typeInvoiceID = $_POST['invoice_code'];
	
	$inv = new invoice();
	$status = $inv->create($typeID = $_POST['type_id'], $typeInvoiceID, $invoiceDate);
	if (!$status)
	{
		$errFormNtf = new formErrorNotification();
		$errFormNtf->build();
		
		$err_header = $appContent->getLiteral("main.invoice", "hd_create_error");
		$err = $errFormNtf->addHeader($err_header);
		$errFormNtf->addDescription($err, $appContent->getLiteral("main.invoice", "lbl_create_error_desc"));
		return $errFormNtf->getReport();
	}
	
	// Set initial invoice extra information
	$inv->updateExtraInformation($wayOfPayment = "Τοις Μετρητοίς", $purposeOfTrafficking = "ΠΩΛΗΣΗ", $wayOfShipping = "", $shippingLocation = "ΕΔΡΑ ΜΑΣ", $deliveryLocation = "ΕΔΡΑ ΜΑΣ");
	
	// Get created invoice id
	$invoiceID = $inv->getInvoiceID();
}


// Initialize invoice
$invoice = new invoice($invoiceID);
$invoiceInfo = $invoice->info();

// Set invoice type title
$typeTitle = HTML::select(".inv-header .invoice_type")->item(0);
HTML::innerHTML($typeTitle, $invoiceInfo['type']);

// Set close button action
$closeButton = HTML::select(".inv-header .close_button")->item(0);
$actionFactory->setAction($closeButton, "StartScreen");


// Invoice info
$invoiceInfoContainer = HTML::select(".retailCashierApplication .panel.invoice_info")->item(0);
$attr = array();
$attr['iid'] = $invoiceID;
$appContainer = $appContent->getAppViewContainer($viewName = "/invoice/invoiceInfo", $attr, $startup = TRUE, $containerID = "invoiceInfoOuterContainer", $loading = TRUE, $preload = FALSE);
DOM::append($invoiceInfoContainer, $appContainer);

$customerInfoContainer = HTML::select(".retailCashierApplication .panel.customer_info")->item(0);
$attr = array();
$attr['iid'] = $invoiceID;
$appContainer = $appContent->getAppViewContainer($viewName = "/customers/customerDetails", $attr, $startup = TRUE, $containerID = "customerDetailsOuterContainer", $loading = TRUE, $preload = FALSE);
DOM::append($customerInfoContainer, $appContainer);


// Add product list container
$startup = (!empty($invoiceInfo['customer_id']));
$productListContainer = HTML::select(".retailCashierApplication .section.section-products")->item(0);
$attr = array();
$attr['iid'] = $invoiceID;
$appContainer = $appContent->getAppViewContainer($viewName = "/products/productList", $attr, $startup, $containerID = "invoiceProductListOuterContainer", $loading = TRUE, $preload = FALSE);
DOM::append($productListContainer, $appContainer);



// Add payments info container
$paymentsContainer = HTML::select(".retailCashierApplication .inv-paymentsContainer")->item(0);
$attr = array();
$attr['iid'] = $invoiceID;
$appContainer = $appContent->getAppViewContainer($viewName = "/payments/paymentsStatus", $attr, $startup = TRUE, $containerID = "paymentsOuterContainer", $loading = TRUE, $preload = FALSE);
DOM::append($paymentsContainer, $appContainer);

// Return output
return $appContent->getReport($holder = ".retailCashierApplication .mainViewContainer", $method = APPContent::REPLACE_METHOD);
//#section_end#
?>