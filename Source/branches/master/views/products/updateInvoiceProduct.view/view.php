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
use \RTL\Invoices\invoiceProduct;

// Create Application Content
$appContent = new APPContent();
$actionFactory = $appContent->getActionFactory();

// Get invoice id
if (engine::isPost())
{
	// Update product to invoice
	$invoiceID = engine::getVar("iid");
	$productID = engine::getVar("pid");
	$invp = new invoiceProduct($invoiceID, $productID);
	
	if ($_POST['amount'] == 0)
		$status = $invp->remove();
	else
		$status = $invp->update($_POST['price'], $_POST['amount'], $_POST['discount']);
	
	// Reload products
	$appContent->addReportAction("product.list.reload");
	$appContent->addReportAction("invoice.payment_status.reload");
	
	return $appContent->getReport();
}
//#section_end#
?>