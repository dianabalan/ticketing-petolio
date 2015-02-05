<?php

require_once('tcpdf/config/lang/eng.php');
require_once('tcpdf/tcpdf.php');

class Petolio_Service_Pdf extends TCPDF {

	//Page header
	public function Header() {
		// Logo
		$image_file = K_PATH_IMAGES.'logo.png';
		$this->Image($image_file, 10, 10, 0, 0, 'PNG', '', 'T', false, 300, 'R', false, false, 0, false, false, false);
		$this->SetTextColor(180, 180, 180);
		$this->SetDrawColor(180, 180, 180);
		$this->Cell(0, 10, '', 'B', 1);
		$this->Cell(0, 13, '', 'B');
	}

	// Page footer
	public function Footer() {
		$translate = Zend_Registry::get('Zend_Translate');
		$config = Zend_Registry::get("config");

		// Position at 15 mm from bottom
		$this->SetY(-20);
		// Set font
		$this->SetFont('helvetica', 'I', 8);
		$this->SetTextColor(180, 180, 180);
		$this->SetDrawColor(180, 180, 180);
		$this->Cell(100, 5, $translate->_('This medical record is created by www.petolio.com'), 'T', 0);
		$this->Cell(0, 5, Petolio_Service_Util::formatDate(strtotime('now'), null, true, true), 'T', 1, 'R');
		// Page number
		$this->Cell(0, 5, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
	}
}