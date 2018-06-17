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
importer::import("API", "Profile");
importer::import("ENP", "Relations");
importer::import("RTL", "Invoices");
importer::import("RTL", "Relations");
importer::import("UI", "Apps");
importer::import("UI", "Forms");
importer::import("UI", "Presentation");

// Import APP Packages
//#section_end#
//#section#[view]
use \API\Profile\account;
use \UI\Apps\APPContent;
use \UI\Forms\templates\simpleForm;
use \UI\Forms\formReport\formErrorNotification;
use \UI\Forms\formReport\formNotification;
use \UI\Presentation\frames\windowFrame;
use \RTL\Invoices\invoice;
use \RTL\Relations\customer;
use \ENP\Relations\ePerson;

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
	
	// Check customer
	$customerID = $_POST['cid'];
	$customer = new customer($customerID);
	$customerInfo = $customer->getCustomerInfo();
	if (empty($customerInfo))
	{
		// Add person as customer
		$customer->addCustomer();
		
		// Get person info and update
		$personInfo = $customer->info();
		$customer->update($personInfo['firstname'], $personInfo['lastname'], $personInfo['middle_name']);
	}
		
	// Set invoice customer
	$invoice = new invoice($invoiceID);
	$status = $invoice->setCustomer($customerID);
	
	// If there is an error in adding the payment, show it
	if (!$status)
	{
		$err_header = $appContent->getLiteral("customer.dialog", "hd_addCustomer");
		$err = $errFormNtf->addHeader($err_header);
		$errFormNtf->addDescription($err, DOM::create("span", "Error setting customer..."));
		return $errFormNtf->getReport();
	}
	
	$succFormNtf = new formNotification();
	$succFormNtf->build($type = formNotification::SUCCESS, $header = TRUE, $timeout = TRUE, $disposable = TRUE);
	
	// Reload customer info
	$succFormNtf->addReportAction("customer.info.reload");
	
	// Enable products
	$succFormNtf->addReportAction("product.list.reload");
	
	// Notification Message
	$errorMessage = $succFormNtf->getMessage("success", "success.save_success");
	$succFormNtf->append($errorMessage);
	return $succFormNtf->getReport();
}

// Build the application view content
$appContent->build("", "addCustomerDialogContainer", TRUE);

// Add new payment form
$form = new simpleForm();
$formContainer = HTML::select(".addCustomerDialog .setCustomerFormContainer")->item(0);
$addCustomerForm = $form->build("", FALSE)->engageApp("customers/addCustomerDialog")->get();
DOM::append($formContainer, $addCustomerForm);

// invoice id
$input = $form->getInput($type = "hidden", $name = "iid", $value = $invoiceID, $class = "bginp", $autofocus = FALSE, $required = FALSE);
$form->append($input);

$customerList = DOM::create("div", "", "", "custList");
$form->append($customerList);

// List all customers
$allCustomers = customer::getCustomers();
$personCustomers = array();
foreach ($allCustomers as $customerInfo)
{
	// Set person checked
	if (!empty($customerInfo['person']))
		$personCustomers[$customerInfo['person_id']] = 1;
	
	// Add customer item
	$customerName = $customerInfo['firstname']." ".$customerInfo['lastname'];
	$citem = getCustomerItem($form, $customerInfo['person_id'], $customerName);
	DOM::append($customerList, $citem);
}

// Get rest of the persons
$allPersons = ePerson::getPersons();
foreach ($allPersons as $personInfo)
{
	// Check if is customer
	if (!empty($personCustomers[$personInfo['id']]))
		continue;
		
	// Add customer item
	$customerName = $personInfo['firstname']." ".$personInfo['lastname'];
	$citem = getCustomerItem($form, $personInfo['id'], $customerName);
	DOM::append($customerList, $citem);
}

function getCustomerItem($form, $customerID, $customerName)
{
	$citem = DOM::create("div", "", "", "citem");
	
	// Create radio
	$input = $form->getInput($type = "radio", $name = "cid", $value = $customerID, $class = "cradio", $autofocus = FALSE, $required = FALSE);
	DOM::append($citem, $input);
	$inputID = DOM::attr($input, "id");
	
	// Label
	$label = $form->getLabel($text = $customerName, $for = $inputID, $class = "cname");
	DOM::append($citem, $label);
	
	return $citem;
}


// Build window dialog
$wFrame = new windowFrame();
$title = $appContent->getLiteral("customer.dialog", "hd_addCustomer");
return $wFrame->build($title)->append($appContent->get())->getFrame();
//#section_end#
?>