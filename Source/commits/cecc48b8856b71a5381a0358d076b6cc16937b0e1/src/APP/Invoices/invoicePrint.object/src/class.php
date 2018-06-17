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

importer::import("GTL", "Docs", "pdfDoc");
importer::import("AEL", "Resources", "filesystem/fileManager");
importer::import("ENP", "Relations", "ePersonAddress");
importer::import("ENP", "Relations", "ePersonPhone");
importer::import("RTL", "Invoices", "invoice");
importer::import("RTL", "Products", "cProductStock");
importer::import("RTL", "Profile", "company");
importer::import("RTL", "Relations", "customer");

use \GTL\Docs\pdfDoc;
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
 * @version	1.0-1
 * @created	September 28, 2015, 21:50 (EEST)
 * @updated	September 28, 2015, 22:31 (EEST)
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
		$this->pdfParser = new pdfDoc();
	}
	
	/**
	 * Exports the invoice to a pdf file.
	 * 
	 * @param	boolean	$shared
	 * 		Whether to use the shared directory or not.
	 * 
	 * @return	boolean
	 * 		True on success, false on failure.
	 */
	public function exportPDF($shared = FALSE)
	{
		// Check if invoice is completed
		$invoice = new invoice($this->invoiceID);
		$invoiceInfo = $invoice->info();
		//if (!$invoiceInfo['completed'])
			//return FALSE;
		
		// Create pdf file
		$fileContents = $this->createInvoicePDF();
		
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
	 * @return	mixed
	 * 		The pdf created.
	 */
	private function createInvoicePDF()
	{
		// Get pdf parser
		$pdf = $this->pdfParser;
		
		// Load company invoice template
		$productsPerPage = 20;
		
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
		for ($i = 0; $i < $numberOfPages; $i++)
		{
			// Add page
			$pdf->AddPage();
			
			// Print page template
			$this->printPageTemplate($pdf);
			
			// Print products
			$anchorY = 100;
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
			$pdf->SetXY(150, 270);
			$pdf->Cell($w = 50, $h = 5, $txt = "ΣΕΛΙΔΑ ".($i + 1)."/".$numberOfPages, $border = FALSE, $ln = 5, $align = "R");
		}
		
		// Print totals
		$anchorY = 225;
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
		
		$currentCustomerBalance = $customer->getBalance();
		$previousCustomerBalance = $currentCustomerBalance - $invoiceBalance;
		$anchorY = 225;
		$anchorX = 125;
		$pdf->SetXY($anchorX, $anchorY);
		$pdf->Cell($w = 80, $h = 5, number_format($totalPayments, 2), $border = FALSE, $ln = 25, $align = "R");
		$pdf->SetXY($anchorX, $anchorY += 10);
		$pdf->Cell($w = 80, $h = 5, number_format($previousCustomerBalance, 2), $border = FALSE, $ln = 25, $align = "R");
		$pdf->SetXY($anchorX, $anchorY += 10);
		$pdf->Cell($w = 80, $h = 5, number_format($currentCustomerBalance, 2), $border = FALSE, $ln = 25, $align = "R");
		
		// Prices and taxes per category
		$anchorY = 235;
		$anchorX = 25;
		$i = 0;
		foreach ($taxCategoryTax as $taxRate => $tax)
		{
			// tax rate
			$pdf->SetXY($anchorX + $i*30, $anchorY);
			$pdf->Cell($w = 30, $h = 5, number_format($taxRate * 100, 2), $border = FALSE, $ln = 25, $align = "C");
			
			$pdf->SetXY($anchorX + $i*30, $anchorY + 5);
			$pdf->Cell($w = 30, $h = 5, number_format($taxCategoryPrice[$taxRate], 2), $border = FALSE, $ln = 25, $align = "C");
			
			$pdf->SetXY($anchorX + $i*30, $anchorY + 10);
			$pdf->Cell($w = 30, $h = 5, number_format($taxCategoryTax[$taxRate], 2), $border = FALSE, $ln = 25, $align = "C");
		}
		
		// Return output
		$pdf->Close();
		return $pdf->Output();
	}
	
	/**
	 * Print the page template.
	 * 
	 * @return	void
	 */
	private function printPageTemplate()
	{
		// Get pdf parser
		$pdf = $this->pdfParser;
		
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
		//$pdf->Image('logo.png', 10,12,30,0,'','http://www.fpdf.org');
		
		// Company info
		
		// Set anchor
		$anchorX = 80;
		$anchorY = 3;
		$lineHeight = 7;
		// Company Name
		$companyName = "ΠΑΠΑΔΟΠΟΥΛΟΥ ΔΗΜ. ΙΩΑΝΝΑ";
		$pdf->WriteLine($companyName, $anchorX, $posY = $anchorY + (0 * $lineHeight), $lnH = 8, $fontSize = 20, $fontColorHex = "000");
		$templateDescription = "ΒΙΟΤΕΧΝΙΑ ΠΑΡΑΓΩΓΗΣ ΧΑΡΤΟΥ
ΑΦΜ 113117347 - ΔΟΥ ΙΩΝΙΑΣ
57600 ΚΥΜΙΝΑ ΘΕΣ/ΝΙΚΗ - ΤΗΛ. ΦΑΞ 23910-43034
EMAIL: ioannappdpl@gmail.com";
		$descLines = explode("\n", $templateDescription);
		foreach ($descLines as $i => $text)
			$pdf->WriteLine($text, $anchorX, $anchorY + (($i+1) * $lineHeight), $lnH = 8, $fontSize = 13, $fontColorHex = "000");
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
		$anchorY = 40;
		
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
		$pdf->Cell($w = 57.5, $h = 5, $txt = "ΤΟΙΣ ΜΕΤΡΗΤΟΙΣ", $border = FALSE, $ln = 25);
		
		$anchorY += $lineHeight;
		$pdf->SetXY(10, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΣΚΟΠΟΣ ΔΙΑΚΙΝΗΣΗΣ", $border = FALSE, $ln = 25);
		$pdf->SetXY(50, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = "ΠΩΛΗΣΗ", $border = FALSE, $ln = 25);
		
		$anchorY += $lineHeight;
		$pdf->SetXY(10, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΤΡΟΠΟΣ ΑΠΟΣΤΟΛΗΣ", $border = FALSE, $ln = 25);
		$pdf->SetXY(50, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = "", $border = FALSE, $ln = 25);
		
		$anchorY += $lineHeight;
		$pdf->SetXY(10, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΤΟΠΟΣ ΑΠΟΣΤΟΛΗΣ", $border = FALSE, $ln = 25);
		$pdf->SetXY(50, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = "ΕΔΡΑ ΜΑΣ", $border = FALSE, $ln = 25);
		
		$anchorY += $lineHeight;
		$pdf->SetXY(10, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΤΟΠΟΣ ΠΑΡΑΔΟΣΗΣ", $border = FALSE, $ln = 25);
		$pdf->SetXY(50, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = "ΕΔΡΑ ΣΑΣ", $border = FALSE, $ln = 25);
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
		$anchorY = 52;
		
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
		$pdf->Cell($w = 57.5, $h = 5, $txt = $customerInfo['ssn'], $border = FALSE, $ln = 25);
		
		$anchorY += $lineHeight;
		$pdf->SetXY(110, $anchorY);
		$pdf->Cell($w = 50, $h = 5, $txt = "ΔΟΥ", $border = FALSE, $ln = 25);
		$pdf->SetXY(135, $anchorY);
		$pdf->Cell($w = 57.5, $h = 5, $txt = $customerInfo['doy'], $border = FALSE, $ln = 25);
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
		$anchorY = 95;
		
		// Print invoice header info
		$pdf->SetDrawColor($r = 0);
		$pdf->Rect(5, $anchorY, 200, 5);
		$pdf->Rect(5, $anchorY, 200, 120);
		
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
		$anchorY = 220;
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
		$pdf->Rect($anchorX, $anchorY, 117.5, 15);
		$pdf->SetXY($anchorX, $anchorY);
		$pdf->Cell($w = 10, $h = 5, $txt = "%ΦΠΑ");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΥΠΟΚ. ΑΞΙΑ");
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΑΞΙΑ ΦΠΑ");
		
		// Total payments
		$anchorY = 220;
		$anchorX = 127.5;
		$pdf->Rect($anchorX, $anchorY, 77.5, 30);
		$anchorY += 5;
		$pdf->SetXY($anchorX, $anchorY);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΠΛΗΡΩΤΕΟ");
		$pdf->SetXY($anchorX, $anchorY += 10);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΠΡΟΗΓ. ΥΠΟΛΟΙΠΟ");
		$pdf->SetXY($anchorX, $anchorY += 10);
		$pdf->Cell($w = 10, $h = 5, $txt = "ΝΕΟ ΥΠΟΛΟΙΠΟ");
		
		// Place signatures
		$pdf->SetXY($anchorX, $anchorY += 5);
		$pdf->Cell($w = 40, $h = 5, $txt = "Ο ΕΚΔΟΤΗΣ", $border = FALSE, $ln = 5, $align = "C");
		$pdf->SetXY($anchorX += 40, $anchorY);
		$pdf->Cell($w = 40, $h = 5, $txt = "Ο ΠΑΡΑΛΑΒΩΝ", $border = FALSE, $ln = 5, $align = "C");
		
		// Add invoice 'disclaimer' information
		$anchorY = 255;
		$anchorX = 5;
		$text = "* Τα εμπορεύματα ταξιδεύουν για λογαριασμό και με κίνδυνο του αγοραστή.
* Σας ενημερώνουμε ότι βάσει του Ν.2472/97 τηρούμε τα προσωπικά σας στοιχεία
στο αρχείο μας και έχετε πρόσβαση σε αυτά σύμφωνα με το νόμο.
Η ΕΞΟΦΛΗΣΗ ΤΩΝ ΤΙΜΟΛΟΓΙΩΝ ΓΙΝΕΤΑΙ ΜΟΝΟ ΜΕ ΕΝΤΥΠΗ ΑΠΟΔΕΙΞΗ ΤΗΣ ΕΤΑΙΡΙΑΣ";
		$lines = explode("\n", $text);
		foreach ($lines as $i => $line)
		{
			$pdf->SetXY($anchorX, $anchorY + (5 * $i));
			$pdf->Cell($w = 40, $h = 5, trim($line));
		}
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