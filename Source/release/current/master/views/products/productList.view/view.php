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
use \RTL\Invoices\invoice;
use \RTL\Invoices\invoiceProduct;

// Create Application Content
$appContent = new APPContent();
$actionFactory = $appContent->getActionFactory();

// Build the application view content
$appContent->build("", "invoiceProductListContainer", TRUE);

// Get invoice id and check for products
$invoiceID = engine::getVar("iid");
$invp = new invoiceProduct($invoiceID);

// Get all products
$allProducts = $invp->getAllProducts();
if (empty($allProducts))
{
	// Remove the product list
	$productList = HTML::select(".productListOuterContainer .productListContainer")->item(0);
	HTML::replace($productList, NULL);
	
	// Add functionality to add product
	$addProductBtn = HTML::select(".productListOuterContainer .rtlbutton.btn-add-first-product")->item(0);
	$attr = array();
	$attr['iid'] = $invoiceID;
	$actionFactory->setAction($addProductBtn, "products/addProductDialog", "", $attr);
}
else
{
	// Remove add product button
	$addProductBtn = HTML::select(".productListOuterContainer .rtlbutton.btn-add-first-product")->item(0);
	HTML::replace($addProductBtn, NULL);
	
	// Add functionality to add product
	$addProductBtn = HTML::select(".productListOuterContainer .rtlbutton.btn-add-product")->item(0);
	$attr = array();
	$attr['iid'] = $invoiceID;
	$actionFactory->setAction($addProductBtn, "products/addProductDialog", "", $attr);
	
	// Add product list
	$productList = HTML::select(".productListOuterContainer .productList")->item(0);
	foreach ($allProducts as $productInfo)
	{
		$prow = DOM::create("div", "", "", "prow");
		DOM::append($productList, $prow);
		
		$pview = DOM::create("div", "", "", "pview");
		DOM::append($prow, $pview);
		
		$fld = DOM::create("div", $productInfo['product_id'], "", "fld pid");
		DOM::append($pview, $fld);
		
		$fld = DOM::create("div", $productInfo['product_title'], "", "fld ptitle");
		DOM::append($pview, $fld);
		
		$price = number_format($productInfo['product_price'], 2);
		$fld = DOM::create("div", $price." €", "", "fld pprice");
		DOM::append($pview, $fld);
		
		$price_vat = number_format($productInfo['product_price'] * (1 + $productInfo['tax_rate']), 2);
		$fld = DOM::create("div", $price_vat." €", "", "fld pprice_vat");
		DOM::append($pview, $fld);
		
		$fld = DOM::create("div", $productInfo['amount'], "", "fld pamount");
		DOM::append($pview, $fld);
		
		$tax = number_format($productInfo['tax_rate'] * $productInfo['product_price'] * $productInfo['amount'], 2);
		$fld = DOM::create("div", $tax." €", "", "fld ptax");
		DOM::append($pview, $fld);
		
		$fld = DOM::create("div", number_format($productInfo['discount'], 2)."%", "", "fld pdiscount");
		DOM::append($pview, $fld);
		
		$fld = DOM::create("div", number_format($productInfo['total_price'], 2)." €", "", "fld ptotal_price");
		DOM::append($pview, $fld);
		
		// Remove product
		$form = new simpleForm();
		$removeProductForm = $form->build("", FALSE)->engageApp("products/updateInvoiceProduct")->get();
		HTML::addClass($removeProductForm, "prform");
		DOM::append($pview, $removeProductForm);
		
		$input = $form->getInput($type = "hidden", $name = "iid", $value = $invoiceID, $class = "", $autofocus = FALSE, $required = TRUE);
		$form->append($input);
		
		$input = $form->getInput($type = "hidden", $name = "pid", $value = $productInfo['product_id'], $class = "", $autofocus = FALSE, $required = TRUE);
		$form->append($input);
		
		// Submit
		$submit = $form->getSubmitButton($title = "", $id = "", $name = "");
		HTML::addClass($submit, "fld pcancel");
		$form->append($submit);
		
		
		$fld = DOM::create("div", "", "", "fld pedit");
		DOM::append($pview, $fld);
		
		
		// Edit product
		$form = new simpleForm();
		$editProductForm = $form->build("", FALSE)->engageApp("products/updateInvoiceProduct")->get();
		HTML::addClass($editProductForm, "pform");
		DOM::append($prow, $editProductForm);
		
		$input = $form->getInput($type = "hidden", $name = "iid", $value = $invoiceID, $class = "", $autofocus = FALSE, $required = TRUE);
		$form->append($input);
		
		$input = $form->getInput($type = "hidden", $name = "pid", $value = $productInfo['product_id'], $class = "", $autofocus = FALSE, $required = TRUE);
		$form->append($input);
		
		$input = $form->getInput($type = "hidden", $name = "tax_rate", $value = $productInfo['tax_rate'], $class = "fld", $autofocus = FALSE, $required = TRUE);
		$form->append($input);
		
		// Labels
		$label = $form->getLabel($text = $productInfo['product_id'], $for = "", $class = "fld pid");
		$form->append($label);
		
		$label = $form->getLabel($text = $productInfo['product_title'], $for = "", $class = "fld ptitle");
		$form->append($label);
		
		// Inputs
		$input = $form->getInput($type = "text", $name = "price", $value = round($productInfo['product_price'], 2), $class = "fld pprice", $autofocus = FALSE, $required = TRUE);
		$form->append($input);
		
		$input = $form->getInput($type = "text", $name = "price_vat", $value = round($productInfo['product_price'] * (1 + $productInfo['tax_rate']), 2), $class = "fld pprice_vat", $autofocus = FALSE, $required = TRUE);
		$form->append($input);
		
		$input = $form->getInput($type = "number", $name = "amount", $value = $productInfo['amount'], $class = "fld pamount", $autofocus = FALSE, $required = TRUE);
		$form->append($input);
		
		$tax = number_format($productInfo['tax_rate'] * $productInfo['product_price'] * $productInfo['amount'], 2);
		$label = $form->getLabel($text = $tax, $for = "", $class = "fld ptax");
		$form->append($label);
		
		$input = $form->getInput($type = "text", $name = "discount", $value = round($productInfo['discount'], 2), $class = "fld pdiscount", $autofocus = FALSE, $required = TRUE);
		$form->append($input);
		
		$label = $form->getLabel($text = $productInfo['total_price'], $for = "", $class = "fld ptotal_price");
		$form->append($label);
		
		// Cancel
		$label = $form->getLabel($text = "", $for = "", $class = "fld pcancel");
		$form->append($label);
		
		// Submit
		$submit = $form->getSubmitButton($title = "", $id = "", $name = "");
		HTML::addClass($submit, "fld psubmit");
		$form->append($submit);
	}
}


// Return output
return $appContent->getReport();
//#section_end#
?>