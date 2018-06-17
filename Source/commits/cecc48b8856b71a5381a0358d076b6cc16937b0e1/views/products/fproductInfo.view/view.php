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
importer::import("RTL", "Products");
importer::import("UI", "Apps");
importer::import("UI", "Forms");

// Import APP Packages
//#section_end#
//#section#[view]
use \UI\Apps\APPContent;
use \UI\Forms\templates\simpleForm;
use \RTL\Products\cProduct;
use \RTL\Products\cProductPrice;

// Create Application Content
$appContent = new APPContent();
$actionFactory = $appContent->getActionFactory();

// Build the application view content
$appContent->build("", "productInfoContainer");

// Get product info
$productID = engine::getVar("pid");
$product = new cProduct($productID);
$productInfo = $product->info();
$pprice = new cProductPrice($productID);

// Add new payment form
$form = new simpleForm();

// Get all product prices
$priceTypes = cProductPrice::getPriceTypes();
$prices = $pprice->getAllPrices($compact = TRUE);
$priceResource = array();
foreach ($prices as $type_id => $price)
	$priceResource[$price] = $priceTypes[$type_id]." (".($price * (1 + $productInfo['tax_rate']))." €)";
$input = $form->getResourceSelect($name = "price_type", $multiple = FALSE, $class = "bginp", $priceResource, $selectedValue = "");
$appContent->append($input);

// Price
$priceValue = (array_values($prices)[0]) * (1 + $productInfo['tax_rate']);
$ph = $appContent->getLiteral("products.dialog", "lbl_price_ph", array(), FALSE);
$input = $form->getInput($type = "text", $name = "price", $value = $priceValue, $class = "bginp", $autofocus = FALSE, $required = FALSE);
DOM::attr($input, "placeholder", $ph);
$appContent->append($input);


$ph = $appContent->getLiteral("products.dialog", "lbl_amount_ph", array(), FALSE);
$input = $form->getInput($type = "number", $name = "amount", $value = 1, $class = "bginp", $autofocus = FALSE, $required = FALSE);
DOM::attr($input, "placeholder", $ph);
DOM::attr($input, "min", "1");
$appContent->append($input);

// Submit button
$title = $appContent->getLiteral("products.dialog", "lbl_add_product");
$button = $form->getSubmitButton($title, $id = "btn_add_product", $name = "");
$appContent->append($button);

// Return product info
return $appContent->getReport();
//#section_end#
?>