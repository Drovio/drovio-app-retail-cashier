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
importer::import("RTL", "Products");
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
use \RTL\Invoices\invoice;
use \RTL\Products\cProduct;

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
	
	// Get product info
	$productID = $_POST['pid'];
	$product = new cProduct($productID);
	$productInfo = $product->info();
	
	// Add product to invoice
	$invoice = new invoice($invoiceID);
	$price = $_POST['price'] / (1 + $productInfo['tax_rate']);
	$status = $invoice->addProduct($productID, $price, $_POST['amount'], $discount = 0);
	
	// If there is an error in adding the payment, show it
	if (!$status)
	{
		$err_header = $appContent->getLiteral("products.dialog", "hd_addProduct");
		$err = $errFormNtf->addHeader($err_header);
		$errFormNtf->addDescription($err, DOM::create("span", "Error adding product..."));
		return $errFormNtf->getReport();
	}
	
	$succFormNtf = new formNotification();
	$succFormNtf->build($type = formNotification::SUCCESS, $header = TRUE, $timeout = TRUE, $disposable = TRUE);
	
	// Reload products and payment status
	$succFormNtf->addReportAction("product.list.reload");
	$succFormNtf->addReportAction("invoice.payment_status.reload");
	
	// Notification Message
	$errorMessage = $succFormNtf->getMessage("success", "success.save_success");
	$succFormNtf->append($errorMessage);
	return $succFormNtf->getReport();
}

// Build the application view content
$appContent->build("", "addProductDialogContainer", TRUE);

// Add new payment form
$form = new simpleForm();
$formContainer = HTML::select(".addProductDialog .addProductFormContainer")->item(0);
$addCustomerForm = $form->build("", FALSE)->engageApp("products/addProductDialog")->get();
DOM::append($formContainer, $addCustomerForm);

// invoice id
$input = $form->getInput($type = "hidden", $name = "iid", $value = $invoiceID, $class = "bginp", $autofocus = FALSE, $required = FALSE);
$form->append($input);

$productList = DOM::create("div", "", "", "prdList");
$form->append($productList);

// List all products
$allProducts = cProduct::getProducts();
foreach ($allProducts as $productInfo)
{
	// Add product item
	$pitem = DOM::create("div", "", "", "pitem");
	DOM::append($productList, $pitem);
	
	// set navigation
	$appContent->setStaticNav($pitem, $ref = "", $targetcontainer = "", $targetgroup = "", $navgroup = "prd_group", $display = "none");
	
	// Set action to load product info
	$attr = array();
	$attr['pid'] = $productInfo['id'];
	$actionFactory->setAction($pitem, "products/fproductInfo", ".addProductDialog .prdInfo", $attr);
	
	// Create radio
	$input = $form->getInput($type = "radio", $name = "pid", $value = $productInfo['id'], $class = "pradio", $autofocus = FALSE, $required = FALSE);
	DOM::append($pitem, $input);
	$inputID = DOM::attr($input, "id");
	
	// Label
	$label = $form->getLabel($text = $productInfo['title'], $for = $inputID, $class = "ptitle");
	DOM::append($pitem, $label);
}

// Product info container
$prdInfo = DOM::create("div", "", "", "prdInfo");
$form->append($prdInfo);


// Build window dialog
$wFrame = new windowFrame();
$title = $appContent->getLiteral("products.dialog", "hd_addProduct");
return $wFrame->build($title)->append($appContent->get())->getFrame();
//#section_end#
?>