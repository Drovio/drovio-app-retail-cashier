<?php
//#section#[header]
// Namespace
namespace APP\Invoices;

require_once($_SERVER['DOCUMENT_ROOT'].'/_domainConfig.php');

// Use Important Headers
use \API\Platform\importer;
use \Exception;

// Check Platform Existance
if (!defined('_RB_PLATFORM_')) throw new Exception("Platform is not defined!");

// Import application loader
importer::import("AEL", "Platform", "application");
use \AEL\Platform\application;
//#section_end#
//#section#[class]
/**
 * @library	APP
 * @package	Invoices
 * 
 * @copyright	Copyright (C) 2015 RetailCashier. All rights reserved.
 */

importer::import("AEL", "Docs", "pdfCreator");
importer::import("AEL", "Resources", "filesystem/fileManager");
importer::import("ENP", "Relations", "ePersonAddress");
importer::import("ENP", "Relations", "ePersonPhone");
importer::import("RTL", "Invoices", "invoice");
importer::import("RTL", "Products", "cProductStock");
importer::import("RTL", "Profile", "company");
importer::import("RTL", "Relations", "customer");

use \AEL\Docs\pdfCreator;
use \AEL\Resources\filesystem\fileManager;
use \ENP\Relations\ePersonAddress;
use \ENP\Relations\ePersonPhone;
use \RTL\Invoices\invoice;
use \RTL\Products\cProductStock;
use \RTL\Profile\company;
use \RTL\Relations\customer;

/**
 * Invoice Printer
 * 
 * Exports the invoice to a pdf for printing.
 * It stores the pdf files inside the private or shared (option) directory: /retail/invoices/exports/.
 * 
 * @version	1.2-3
 * @created	September 28, 2015, 21:50 (EEST)
 * @updated	October 3, 2015, 16:55 (EEST)
 */
class invoicePrint
{
	/**
	 * The invoice id to print.
	 * 
	 * @type	string
	 */
	private $invoiceID;
	
	/**
	 * The pdf parser object.
	 * 
	 * @type	pdfDoc
	 */
	private $pdfParser;
	
	/**
	 * Create a new invoice print/exporter instance.
	 * 
	 * @param	string	$invoiceID
	 * 		The invoice id to export.
	 * 
	 * @return	void
	 */
	public function __construct($invoiceID = "")
	{
		// Initialize invoice id
		$this->invoiceID = $invoiceID;
		$this->pdfParser = new pdfCreator();
	}
	
	/**
	 * Exports the invoice to a pdf file.
	 * 
	 * @param	boolean	$shared
	 * 		Whether to use the shared directory or not.
	 * 
	 * @param	boolean	$doubleCopy
	 * 		Set TRUE to print two copies of the invoice, original and copy, in case there is no specific tool to setup this.
	 * 
	 * @return	boolean
	 * 		True on success, false on failure.
	 */
	public function exportPDF($shared = FALSE, $doubleCopy = FALSE)
	{
		// Create pdf file
		$fileContents = $this->createInvoicePDF($doubleCopy);
		
		// Save to file
		$filePath = $this->getFilePath($type = "pdf");
		$fm = new fileManager($mode = fileManager::TEAM_MODE, $shared);
		return $fm->create($filePath, $fileContents);
	}
	
	/**
	 * Get the pdf file contents.
	 * 
	 * @param	boolean	$shared
	 * 		Whether to use the shared directory or not.
	 * 
	 * @return	mixed
	 * 		The pdf file contents.
	 */
	public function getPDF($shared = FALSE)
	{
		$filePath = $this->getFilePath($type = "pdf");
		$fm = new fileManager($mode = fileManager::TEAM_MODE, $shared);
		return $fm->get($filePath);
	}
	
	/**
	 * Create the invoice pdf in its total.
	 * 
	 * @param	boolean	$doubleCopy
	 * 		Set TRUE to print two copies of the invoice, original and copy, in case there is no specific tool to setup this.
	 * 
	 * @return	mixed
	 * 		The pdf created.
	 */
	private function createInvoicePDF($doubleCopy = FALSE)
	{
		// Check for double copy
		if ($doubleCopy)
		{
			// Print original
			$this->printInvoiceCopy($label = "ΠΡΩΤΟΤΥΠΟ");
			
			// Print copy
			$this->printInvoiceCopy($label = "ΑΝΤΙΓΡΑΦΟ");
		}
		else
		{
			// Print original only
			$this->printInvoiceCopy();
		}
		
		// Get pdf parser
		$pdf = $this->pdfParser;
		
		// Close and return output
		$pdf->Close();
		return $pdf->Output();
	}
	
	/**
	 * Print the entire invoice.
	 * 
	 * @param	string	$copyLabel
	 * 		In case of double copies, set the copy label here.
	 * 		This will set a label on the upper right corner.
	 * 
	 * @return	void
	 */
	private function printInvoiceCopy($copyLabel = "")
	{
		// Load company invoice template
		$productsPerPage = 20;
		
		// Get pdf parser
		$pdf = $this->pdfParser;
		
		// Initial settings for pdf
		$pdf->AddFont('DejaVu', $style = '', $file = 'DejaVuSansCondensed.ttf', $uni = TRUE);
		$pdf->SetFont('DejaVu', '', 12);
		
		// Get all invoice products
		$invoice = new invoice($this->invoiceID);
		$invoiceInfo = $invoice->info($includeProducts = TRUE);
		$invoiceProducts = $invoiceInfo['products'];
		
		// Get customer
		$customer = new customer($invoiceInfo['customer_id']);
		
		// Prepare product info
		$mUnits = cProductStock::getMeasurementUnits();
		
		// Prepare sums
		$totalPriceSumNoTaxNoDiscount = 0;
		$totalPriceSum = 0;
		$totalTaxSum = 0;
		$totalDiscountSum = 0;
		$taxCategoryPrice = array();
		$taxCategoryTax = array();
		$totalAmount = 0;
		
		// Get all pages
		$numberOfPages = ceil(count($invoiceProducts) / $productsPerPage);
		$numberOfPages = ($numberOfPages < 1 ? 1 : $numberOfPages);
		for ($i = 0; $i < $numberOfPages; $i++)
		{
			// Add page
			$pdf->AddPage();
			
			// Print page template
			$this->printPageTemplate($copyLabel);
			
			// Print products
			$anchorY = 110;
			$productsSlice = array_slice($invoiceProducts, $i * $productsPerPage, $productsPerPage);
			foreach ($productsSlice as $productInfo)
			{
				// Add invoice info
				$pdf->SetFontSize(9);
				$anchorX = 5;
				$pdf->SetXY($anchorX, $anchorY);
				$pdf->Cell($w = 100, $h = 5, $productInfo['product_id']);
				$pdf->SetXY($anchorX += 20, $anchorY);
				$pdf->Cell($w = 100, $h = 5, $productInfo['product_title']);
				$pdf->SetXY($anchorX += 60, $anchorY);
				$pdf->Cell($w = 100, $h = 5, $mUnits[$productInfo['m_unit_id']]);
				$pdf->SetXY($anchorX += 20, $anchorY);
				$pdf->Cell($w = 100, $h = 5, $productInfo['amount']);
				$pdf->SetXY($anchorX += 20, $anchorY);
				$pdf->Cell($w = 100, $h = 5, number_format($productInfo['product_price'], 2));
				$pdf->SetXY($anchorX += 20, $anchorY);
				$pdf->Cell($w = 100, $h = 5, $productInfo['discount']."%");
				$pdf->SetXY($anchorX += 20, $anchorY);
				$valueAfterDiscount = ($productInfo['product_price'] * $productInfo['amount']) * (1 - $productInfo['discount']/100);
				$pdf->Cell($w = 100, $h = 5, number_format($valueAfterDiscount, 2));
				$pdf->SetXY($anchorX += 20, $anchorY);
				$pdf->Cell($w = 100, $h = 5, number_format($productInfo['tax_rate'] * 100, 2));
				
				// Increase anchor
				$anchorY += 5;
				
				// Add to sums
				$totalPriceSumNoTaxNoDiscount += ($productInfo['product_price'] * $productInfo['amount']);
				$totalPriceSum += $valueAfterDiscount * (1 + $productInfo['tax_rate']);
				$totalTaxSum += $valueAfterDiscount * $productInfo['tax_rate'];
				$totalDiscountSum += ($productInfo['discount']/100) * $productInfo['product_price'] * $productInfo['amount'];
				$taxCategoryPrice[$productInfo['tax_rate']] += $valueAfterDiscount;
				$taxCategoryTax[$productInfo['tax_rate']] += $valueAfterDiscount * $productInfo['tax_rate'];
				$totalAmount += $productInfo['amount'];
			}
			
			// Print current page
			$pdf->SetXY(10, 270);
			$pdf->Cell($w = 50, $h = 5, $txt = "ΣΕΛΙΔΑ ".($i + 1)."/".$numberOfPages);
		}
		
		// Total amount
		$anchorY = 200;
		$anchorX = 125;
		$pdf->SetXY($anchorX, $anchorY);
		$pdf->Cell($w = 200, $h = 5, "ΣΥΝ. ΠΟΣΟΤΗΤΑ: ".number_format($totalAmount, 2));
		
		// Print totals
		$anchorY = 215;
		$anchorX = 5;
		$pdf->SetXY($anchorX, $anchorY);
		$pdf->Cell($w = 40, $h = 5, number_format($totalPriceSumNoTaxNoDiscount, 2), $border = FALSE, $ln = 25, $align = "C");
		$pdf->SetXY($anchorX += 40, $anchorY);
		$pdf->Cell($w = 40, $h = 5, number_format($totalDiscountSum, 2), $border = FALSE, $ln = 25, $align = "C");
		$pdf->SetXY($anchorX += 40, $anchorY);
		$pdf->Cell($w = 40, $h = 5, number_format($totalTaxSum, 2), $border = FALSE, $ln = 25, $align = "C");
		
		// Print payments
		$totalPrice = $invoice->getTotalPrice();
		$totalPayments = $invoice->getTotalPayments();
		$invoiceBalance = $invoice->getBalance();
		
		// Get invoice type flow
		$invTypes = $invoice->getInvoiceTypes();
		$flow = $invTypes[$invoiceInfo['type_id']]['transaction_flow'];
		
		// Balances
		$currentCustomerBalance = $customer->getBalance();
		$previousCustomerBalance = $currentCustomerBalance - ($flow * $invoiceBalance);
		$anchorY = 225;
		$anchorX = 75;
		$pdf->SetXY($anchorX, $anchorY);
		$pdf->Cell($w = 47.5, $h = 5, number_format($previousCustomerBalance, 2)." €", $border = FALSE, $ln = 25, $align = "R");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 47.5, $h = 5, number_format($totalPayments, 2)." €", $border = FALSE, $ln = 25, $align = "R");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 47.5, $h = 5, number_format($currentCustomerBalance, 2)." €", $border = FALSE, $ln = 25, $align = "R");
		
		// Payments
		$anchorY = 210;
		$anchorX = 125;
		$pdf->SetXY($anchorX, $anchorY);
		$pdf->Cell($w = 80, $h = 5, number_format($totalPriceSumNoTaxNoDiscount, 2)." €", $border = FALSE, $ln = 25, $align = "R");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 80, $h = 5, number_format(($totalDiscountSum / $totalPriceSumNoTaxNoDiscount) * 100, 2)." %", $border = FALSE, $ln = 25, $align = "R");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 80, $h = 5, number_format($totalDiscountSum, 2)." €", $border = FALSE, $ln = 25, $align = "R");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 80, $h = 5, number_format($totalPriceSumNoTaxNoDiscount - $totalDiscountSum, 2)." €", $border = FALSE, $ln = 25, $align = "R");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 80, $h = 5, number_format($totalPriceSumNoTaxNoDiscount - $totalDiscountSum, 2)." €", $border = FALSE, $ln = 25, $align = "R");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 80, $h = 5, number_format($totalTaxSum, 2)." €", $border = FALSE, $ln = 25, $align = "R");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->SetFontSize(11);
		$pdf->Cell($w = 80, $h = 5, number_format($totalPrice, 2)." €", $border = FALSE, $ln = 25, $align = "R");
		
		// Reset font size
		$pdf->SetFontSize(9);
		
		// Prices and taxes per category
		$anchorY = 225;
		$anchorX = 25;
		$i = 0;
		foreach ($taxCategoryTax as $taxRate => $tax)
		{
			// tax rate
			$pdf->SetXY($anchorX + $i*15, $anchorY);
			$pdf->Cell($w = 15, $h = 5, number_format($taxRate * 100, 2), $border = FALSE, $ln = 25, $align = "C");
			
			$pdf->SetXY($anchorX + $i*15, $anchorY + 5);
			$pdf->Cell($w = 15, $h = 5, number_format($taxCategoryPrice[$taxRate], 2), $border = FALSE, $ln = 25, $align = "C");
			
			$pdf->SetXY($anchorX + $i*15, $anchorY + 10);
			$pdf->Cell($w = 15, $h = 5, number_format($taxCategoryTax[$taxRate], 2), $border = FALSE, $ln = 25, $align = "C");
		}
	}
	
	/**
	 * Print the page template.
	 * 
	 * @param	string	$copyLabel
	 * 		The upper right corner label of the invoice print.
	 * 
	 * @return	void
	 */
	private function printPageTemplate($copyLabel = "")
	{
		// Get pdf parser
		$pdf = $this->pdfParser;
		
		// Print copy label
		if (!empty($copyLabel))
		{
			$pdf->SetXY(150, 8);
			$pdf->SetTextColor($r = 100);
			$pdf->SetFontSize(9);
			$pdf->Cell($w = 50, $h = 5, $txt = $copyLabel, $border = FALSE, $ln = 25, $align = "R");
		}
		
		// Print company info
		$this->printCompanyInfo($pdf);
		
		// Print invoice and customer Info
		$this->printInvoiceInfo($pdf);
		$this->printCustomerInfo($pdf);
		
		// Print products container
		$this->printProductInfo($pdf);
		
		// Print footer/disclaimer/pages
		$this->printFooterInfo($pdf);
	}
	
	/**
	 * Print the company info for the invoice.
	 * 
	 * @return	void
	 */
	private function printCompanyInfo()
	{
		// Get pdf parser
		$pdf = $this->pdfParser;
		
		// Get company info
		$companyTemplateInfo = array();
		$companyTemplateInfo['logo_image_path'] = "";
		
		// Logo
		$fm = new fileManager($mode = fileManager::TEAM_MODE, $shared = TRUE);
		//$logoContents = $fm->get("logo.jpg");
		//$pdf->ImageFromContents($logoContents, 10, 10);
		
		// Company info
		
		// Set anchor
		$anchorY = 8;
		$anchorX = 80;
		$lineHeight = 6;
		// Company Name
		$companyName = "ΝΤΟΥΛΙΑΣ ΑΧΙΛΛΕΑΣ";
		$this->WriteLine($companyName, $anchorX, $posY = $anchorY + (0 * $lineHeight), $lnH = 8, $fontSize = 18, $fontColorHex = "000");
		$templateDescription = "ΣΤΟΥΠΙΑ – ΠΑΝΙΑ
ΑΦΜ: 042452320 ΔΟΥ: ΙΩΝΙΑΣ
ΝΕΑ ΜΑΛΓΑΡΑ 57300, ΔΗΜΟΣ ΔΕΛΤΑ – ΘΕΣΣΑΛΟΝΙΚΗ
ΤΗΛ. 2391042362 ΦΑΞ: 2312 208768 ΚΙΝ. 6980216020
e-mail: antoulias@yahoo.gr";
		$descLines = explode("\n", $templateDescription);
		foreach ($descLines as $i => $text)
			$this->WriteLine($text, $anchorX, $anchorY + (($i+1) * $lineHeight), $lnH = 8, $fontSize = 12, $fontColorHex = "000");
	}
	
	/**
	 * Print all the invoice relative info.
	 * 
	 * @param	pdfDoc	$pdf
	 * 		The pdf creator object.
	 * 
	 * @return	void
	 */
	private function printInvoiceInfo($pdf)
	{
		// Set y anchor
		$anchorY = 50;
		
		// Get invoice info
		$invoice = new invoice($this->invoiceID);
		$invoiceInfo = $invoice->info($includeProducts = FALSE);
		
		// Print invoice header info
		$pdf->SetDrawColor($r = 0);
		$pdf->Rect(5, $anchorY, 200, 10);
		
		// Add invoice info
		$pdf->SetFontSize(10);
		
		$pdf->SetXY(10, $anchorY);
		$pdf->Cell($w = 100, $h = 5, $txt = "ΕΙΔΟΣ ΠΑΡΑΣΤΑΤΙΚΟΥ", $border = FALSE, $ln = 25, $align = "C");
		$pdf->SetXY(10, $anchorY + 5);
		$pdf->Cell($w = 100, $h = 5, $txt = $invoiceInfo['type'], $border = FALSE, $ln = 25, $align = "C");
		
		$pdf->SetXY(100, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "Νο ΠΑΡΑΣΤΑΤΙΚΟΥ", $border = FALSE, $ln = 25, $align = "C");
		$pdf->SetXY(100, $anchorY + 5);
		$pdf->Cell($w = 50, $h = 5, $txt = $invoiceInfo['invoice_id'], $border = FALSE, $ln = 25, $align = "C");
		
		$pdf->SetXY(150, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΗΜΕΡΟΜΗΝΙΑ", $border = FALSE, $ln = 25, $align = "C");
		$pdf->SetXY(150, $anchorY + 5);
		$invoiceDate = $invoiceInfo['date_created'];
		if (empty($invoiceDate))
			$invoiceDate = date("H:i d/m/Y", $invoiceInfo['time_created']);
		$pdf->Cell($w = 50, $h = 5, $txt = $invoiceDate, $border = FALSE, $ln = 25, $align = "C");
		
		// Create rectangle with more detailed invoice info
		$anchorY += 12;
		$pdf->Rect(5, $anchorY, 97.5, 40);
		$lineHeight = 6;
		
		$pdf->SetXY(10, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΤΡΟΠΟΣ ΠΛΗΡΩΜΗΣ", $border = FALSE, $ln = 25);
		$pdf->SetXY(50, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = $invoiceInfo['way_of_payment'], $border = FALSE, $ln = 25);
		
		$anchorY += $lineHeight;
		$pdf->SetXY(10, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΣΚΟΠΟΣ ΔΙΑΚΙΝΗΣΗΣ", $border = FALSE, $ln = 25);
		$pdf->SetXY(50, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = $invoiceInfo['purpose_of_trafficking'], $border = FALSE, $ln = 25);
		
		$anchorY += $lineHeight;
		$pdf->SetXY(10, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΤΡΟΠΟΣ ΑΠΟΣΤΟΛΗΣ", $border = FALSE, $ln = 25);
		$pdf->SetXY(50, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = $invoiceInfo['way_of_shipping'], $border = FALSE, $ln = 25);
		
		$anchorY += $lineHeight;
		$pdf->SetXY(10, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΤΟΠΟΣ ΑΠΟΣΤΟΛΗΣ", $border = FALSE, $ln = 25);
		$pdf->SetXY(50, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = $invoiceInfo['shipping_location'], $border = FALSE, $ln = 25);
		
		$anchorY += $lineHeight;
		$pdf->SetXY(10, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΤΟΠΟΣ ΠΑΡΑΔΟΣΗΣ", $border = FALSE, $ln = 25);
		$pdf->SetXY(50, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = $invoiceInfo['delivery_location'], $border = FALSE, $ln = 25);
	}
	
	/**
	 * Print the customer info.
	 * 
	 * @return	void
	 */
	private function printCustomerInfo()
	{
		// Get pdf parser
		$pdf = $this->pdfParser;
		
		// Set y anchor
		$anchorY = 62;
		
		// Get customer info
		$invoice = new invoice($this->invoiceID);
		$invoiceInfo = $invoice->info($includeProducts = FALSE);
		$invc = new customer($invoiceInfo['customer_id']);
		$customerInfo = $invc->getCustomerInfo();
		
		$pdf->Rect(107.5, $anchorY, 97.5, 40);
		$lineHeight = 5;
		
		$pdf->SetXY(110, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΚΩΔΙΚΟΣ", $border = FALSE, $ln = 25);
		$pdf->SetXY(135, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = $customerInfo['person_id'], $border = FALSE, $ln = 25);
		
		$anchorY += $lineHeight;
		$pdf->SetXY(110, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΕΠΩΝΥΜΙΑ", $border = FALSE, $ln = 25);
		$pdf->SetXY(135, $anchorY);
		$customerName = $customerInfo['firstname']." ".$customerInfo['lastname'];
		$pdf->Cell($w = 57.5, $h = 5, $txt = $customerName, $border = FALSE, $ln = 25);
		
		$anchorY += $lineHeight;
		$pdf->SetXY(110, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΕΠΑΓΓΕΛΜΑ", $border = FALSE, $ln = 25);
		$pdf->SetXY(135, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = $customerInfo['occupation'], $border = FALSE, $ln = 25);
		
		// Get customer's person address
		$pAddress = new ePersonAddress($customerInfo['person_id']);
		$allAddresses = $pAddress->getAllAddresses();
		$address = array_values($allAddresses)[0];
		
		$anchorY += $lineHeight;
		$pdf->SetXY(110, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΔΙΕΥΘΥΝΣΗ", $border = FALSE, $ln = 25);
		$pdf->SetXY(135, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = $address['address'].", ".$address['postal_code'], $border = FALSE, $ln = 25);
		
		$anchorY += $lineHeight;
		$pdf->SetXY(110, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΠΟΛΗ", $border = FALSE, $ln = 25);
		$pdf->SetXY(135, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = $address['city'], $border = FALSE, $ln = 25);
		
		// Get customer's person phone
		$pPhone = new ePersonPhone($customerInfo['person_id']);
		$allPhones = $pPhone->getAllPhones();
		$phone = array_values($allPhones)[0];
		
		$anchorY += $lineHeight;
		$pdf->SetXY(110, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΤΗΛΕΦΩΝΟ", $border = FALSE, $ln = 25);
		$pdf->SetXY(135, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = $phone['phone'], $border = FALSE, $ln = 25);
		
		$anchorY += $lineHeight;
		$pdf->SetXY(110, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΑΦΜ", $border = FALSE, $ln = 25);
		$pdf->SetXY(135, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = $customerInfo['tax_id'], $border = FALSE, $ln = 25);
		
		$anchorY += $lineHeight;
		$pdf->SetXY(110, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΔΟΥ", $border = FALSE, $ln = 25);
		$pdf->SetXY(135, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = $customerInfo['irs'], $border = FALSE, $ln = 25);
	}
	
	/**
	 * Print all the product info.
	 * 
	 * @return	void
	 */
	private function printProductInfo()
	{
		// Get pdf parser
		$pdf = $this->pdfParser;
		
		// Set y anchor
		$anchorY = 105;
		
		// Print invoice header info
		$pdf->SetDrawColor($r = 0);
		$pdf->Rect(5, $anchorY, 200, 5);
		$pdf->Rect(5, $anchorY, 200, 100);
		
		// Add invoice info
		$pdf->SetFontSize(9);
		$anchorX = 5;
		$pdf->SetXY($anchorX, $anchorY);
		$pdf->Cell($w = 100, $h = 5, $txt = "ΚΩΔΙΚΟΣ");
		$pdf->SetXY($anchorX += 20, $anchorY);
		$pdf->Cell($w = 100, $h = 5, $txt = "ΠΕΡΙΓΡΑΦΗ ΕΙΔΟΥΣ");
		$pdf->SetXY($anchorX += 60, $anchorY);
		$pdf->Cell($w = 100, $h = 5, $txt = "Μ/Μ ΣΥΣΚ");
		$pdf->SetXY($anchorX += 20, $anchorY);
		$pdf->Cell($w = 100, $h = 5, $txt = "ΠΟΣΟΤΗΤΑ");
		$pdf->SetXY($anchorX += 20, $anchorY);
		$pdf->Cell($w = 100, $h = 5, $txt = "ΤΙΜΗ ΜΟΝ.");
		$pdf->SetXY($anchorX += 20, $anchorY);
		$pdf->Cell($w = 100, $h = 5, $txt = "ΕΚΠ%");
		$pdf->SetXY($anchorX += 20, $anchorY);
		$pdf->Cell($w = 100, $h = 5, $txt = "ΑΞΙΑ");
		$pdf->SetXY($anchorX += 20, $anchorY);
		$pdf->Cell($w = 100, $h = 5, $txt = "ΦΠΑ%");
	}
	
	/**
	 * Print the invoice footer including totals and disclaimers.
	 * 
	 * @return	void
	 */
	private function printFooterInfo()
	{
		// Get pdf parser
		$pdf = $this->pdfParser;
		
		// Set y anchor
		$anchorY = 210;
		$anchorX = 5;
		
		// Total value
		$pdf->SetDrawColor($r = 0);
		$pdf->Rect($anchorX, $anchorY, 117.5, 12);
		$pdf->SetXY($anchorX, $anchorY);
		$pdf->Cell($w = 40, $h = 5, $txt = "ΣΥΝΟΛΙΚΗ ΑΞΙΑ", $border = FALSE, $ln = 25, $align = "C");
		$pdf->SetXY($anchorX += 40, $anchorY);
		$pdf->Cell($w = 40, $h = 5, $txt = "ΕΚΠΤΩΣΗ", $border = FALSE, $ln = 25, $align = "C");
		$pdf->SetXY($anchorX += 40, $anchorY);
		$pdf->Cell($w = 40, $h = 5, $txt = "ΦΠΑ", $border = FALSE, $ln = 25, $align = "C");
		
		// Total tax analysis
		$anchorY += 15;
		$anchorX = 5;
		$pdf->Rect($anchorX, $anchorY, 70, 15);
		$pdf->SetXY($anchorX, $anchorY);
		$pdf->Cell($w = 10, $h = 5, $txt = "%ΦΠΑ");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΥΠΟΚ. ΑΞΙΑ");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΑΞΙΑ ΦΠΑ");
		
		// Balances
		$anchorY = 225;
		$anchorX = 75;
		$pdf->Rect($anchorX, $anchorY, 47.5, 15);
		$pdf->SetXY($anchorX, $anchorY);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΠΡ. ΥΠΟΛΟΙΠΟ");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΕΙΣΠΡΑΞΗ");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΝΕΟ ΥΠΟΛΟΙΠΟ");
		
		// Total payments
		$anchorY = 210;
		$anchorX = 127.5;
		$pdf->Rect($anchorX, $anchorY, 77.5, 35);
		$pdf->SetXY($anchorX, $anchorY);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΣΥΝΟΛΙΚΗ ΑΞΙΑ");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΕΚΠΤΩΣΗ %");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΕΚΠΤΩΣΗ");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΚΑΘΑΡΗ ΑΞΙΑ");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΓΕΝΙΚΟ ΣΥΝΟΛΟ");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΦΠΑ");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Rect($anchorX, $anchorY, 77.5, 0);
		$pdf->SetFontSize(11);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΠΛΗΡΩΤΕΟ");
		
		// Reset font size
		$pdf->SetFontSize(9);
		
		// Place signatures
		$pdf->SetXY($anchorX, $anchorY += 8);
		$pdf->Cell($w = 40, $h = 5, $txt = "Ο ΕΚΔΟΤΗΣ", $border = FALSE, $ln = 5, $align = "C");
		$pdf->SetXY($anchorX += 40, $anchorY);
		$pdf->Cell($w = 40, $h = 5, $txt = "Ο ΠΑΡΑΛΑΒΩΝ", $border = FALSE, $ln = 5, $align = "C");
		
		// Add invoice 'disclaimer' information
		$anchorY = 245;
		$anchorX = 5;
		$pdf->SetFontSize(8);
		$text = "* Σας ενημερώνουμε ότι βάσει του Ν.2472/97 τηρούμε τα προσωπικά σας στοιχεία
στο αρχείο μας και έχετε πρόσβαση σε αυτά σύμφωνα με το νόμο.
Η ΕΞΟΦΛΗΣΗ ΤΩΝ ΤΙΜΟΛΟΓΙΩΝ ΓΙΝΕΤΑΙ ΜΟΝΟ ΜΕ ΕΝΤΥΠΗ ΑΠΟΔΕΙΞΗ ΤΗΣ ΕΤΑΙΡΙΑΣ
ΣΥΝΕΡΓΑΖΟΜΕΝΕΣ ΤΡΑΠΕΖΕΣ
ΠΕΙΡΑΙΩΣ: 5242-071222-537 , IBAN: GR6601722420005242071222537";
		$lines = explode("\n", $text);
		foreach ($lines as $i => $line)
		{
			$pdf->SetXY($anchorX, $anchorY + (4 * $i));
			$pdf->Cell($w = 40, $h = 5, trim($line));
		}
	}
	
	/**
	 * Write a line on the pdf file.
	 * 
	 * @param	float	$text
	 * 		The text to write.
	 * 
	 * @param	float	$posX
	 * 		The x position for the upper right corner.
	 * 
	 * @param	float	$posY
	 * 		The y position for the upper right corner.
	 * 
	 * @param	float	$lnH
	 * 		The line height.
	 * 
	 * @param	float	$fontSize
	 * 		The font size.
	 * 		It is 11 by default.
	 * 
	 * @param	string	$fontColorHex
	 * 		The font color in hex mode.
	 * 
	 * @return	void
	 */
	private  function WriteLine($text, $posX, $posY, $lnH, $fontSize = 11, $fontColorHex = "FFF")
	{
		// Get pdf parser
		$pdf = $this->pdfParser;
		
		// Set font size and color
		$pdf->SetFontSize($fontSize);
		$pdf->SetTextColor($r = 0);
		
		// Set position and write
		$pdf->SetXY($posX, $posY);
		return $pdf->Write($lnH, $text);
	}
	
	/**
	 * Get the file path for the invoice.
	 * 
	 * @param	string	$type
	 * 		The file extension (type).
	 * 
	 * @return	string
	 * 		The invoice file path.
	 */
	public function getFilePath($type)
	{
		// Set filename
		$fileName = "inv-cmp".company::getCompanyID()."-id".$this->invoiceID.".".$type;
		
		// Get full path
		return "/retail/invoices/exports/".$fileName;
	}
}
//#section_end#
?>