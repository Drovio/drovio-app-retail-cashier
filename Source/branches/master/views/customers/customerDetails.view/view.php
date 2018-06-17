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
importer::import("ENP", "Relations");
importer::import("RTL", "Invoices");
importer::import("RTL", "Relations");
importer::import("UI", "Apps");

// Import APP Packages
//#section_end#
//#section#[view]
use \UI\Apps\APPContent;
use \RTL\Invoices\invoice;
use \RTL\Relations\customer;
use \ENP\Relations\ePersonAddress;
use \ENP\Relations\ePersonPhone;

// Create Application Content
$appContent = new APPContent();
$actionFactory = $appContent->getActionFactory();

// Build the application view content
$appContent->build("", "customerDetailsContainer", TRUE);

// Get invoice id and check for customer id
$invoiceID = engine::getVar("iid");
$invoice = new invoice($invoiceID);
$invoiceInfo = $invoice->info();
if (empty($invoiceInfo['customer_id']))
{
	// Remove the customer info
	$customerInfo = HTML::select(".customerDetails .customerInfo")->item(0);
	HTML::replace($customerInfo, NULL);
	
	// Add functionality to add customer
	$addCustomerBtn = HTML::select(".customerDetails .rtlbutton")->item(0);
	$attr = array();
	$attr['iid'] = $invoiceID;
	$actionFactory->setAction($addCustomerBtn, "customers/addCustomerDialog", "", $attr);
}
else
{
	// Remove add customer button
	$addCustomerBtn = HTML::select(".customerDetails .rtlbutton")->item(0);
	HTML::replace($addCustomerBtn, NULL);
	
	// Get customer information
	$customerID = $invoiceInfo['customer_id'];
	$customer = new customer($customerID);
	$customerInfo = $customer->info();
	
	$valueHolder = HTML::select(".crow.cname .value")->item(0);
	DOM::innerHTML($valueHolder, $customerInfo['firstname']." ".$customerInfo['lastname']);
	
	$pPhone = new ePersonPhone($customerID);
	$phones = $pPhone->getAllPhones();
	$phone = array_values($phones)[0];
	$valueHolder = HTML::select(".crow.cphone .value")->item(0);
	DOM::innerHTML($valueHolder, $phone['phone']);
	
	$pAddress = new ePersonAddress($customerID);
	$addresses = $pAddress->getAllAddresses();
	$address = array_values($addresses)[0];
	$valueHolder = HTML::select(".crow.caddress .value")->item(0);
	$adp = array();
	$adp[] = $address['address'];
	$adp[] = $address['postal_code'];
	$adp[] = $address['city'];
	DOM::innerHTML($valueHolder, implode(", ", $adp));
	
	$valueHolder = HTML::select(".crow.cjob .value")->item(0);
	DOM::innerHTML($valueHolder, $customerInfo['occupation']);
	
	$balance = number_format($customerInfo['balance'], 2)." €";
	$valueHolder = HTML::select(".crow.cbalance .value")->item(0);
	DOM::innerHTML($valueHolder, $balance);
}

// Return output
return $appContent->getReport();
//#section_end#
?>