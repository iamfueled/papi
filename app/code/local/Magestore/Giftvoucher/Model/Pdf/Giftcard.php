<?php
class Magestore_Giftvoucher_Model_Pdf_Giftcard extends Varien_Object
{
	public function getPdf($giftvoucherIds) {
		// $this->_beforeGetPdf();
		if($giftvoucherIds) {
			$pdf = new Zend_Pdf();
			$this->_setPdf($pdf);
			$style = new Zend_Pdf_Style();
			$this->_setFontBold($style, 10);
			// $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
			// $pdf->pages[] = $page;
			$giftvoucherIds = array_chunk($giftvoucherIds, 3);
			
			// var_dump(count($giftvoucherIds));die();
			foreach($giftvoucherIds as $giftvouchers) {
				$page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
				$pdf->pages[] = $page;
				$this->y = 790;
				$i == 0;
				foreach($giftvouchers as $giftvoucherId) {
					$giftvoucher = Mage::getModel('giftvoucher/giftvoucher')->load($giftvoucherId);
				
					if($giftvoucher->getId()) {
						/*Add Border*/
						$page->setFillColor(new Zend_Pdf_Color_Rgb(1, 1, 1));
						$page->setLineColor(new Zend_Pdf_Color_Html('#59b1e1'));
						$page->setLineWidth(5);
						$page->drawRectangle(50, $this->y, 545, $this->y-225+70);
						
						/*Insert Title Logo*/
						$page->setFillColor(new Zend_Pdf_Color_Html('#ff7200'));
						$this->_setFontRegular($page, 20);
						$page->drawText(Mage::helper('giftvoucher')->__('GIFT CARD'), 85, $this->y-30 , 'UTF-8');
						
						/*Insert Email Logo*/
						$store = Mage::app()->getStore($giftvoucher->getStoreId());
						$image = Mage::getStoreConfig('giftvoucher/print_voucher/logo', $store->getId());
						if($image) {
							$image = Mage::getStoreConfig('system/filesystem/media', $store->getId()) . '/giftvoucher/pdf/logo/' . $image;
							if(is_file($image)) {
								$image = Zend_Pdf_Image::imageWithPath($image);
								$page->drawImage($image, 338, $this->y-57, 505, $this->y-10);
							}
						}
						
						/*Expire date*/
						$page->setFillColor(new Zend_Pdf_Color_Html('#000000'));
						$this->_setFontRegular($page, 8);
						$page->drawText(Mage::helper('giftvoucher')->__('EXPIRATION DATE: '), 85, $this->y-50 , 'UTF-8');
						$page->drawText(Mage::helper('giftvoucher')->__('.............................................................................................'), 155, $this->y-51 , 'UTF-8');
						$page->setFillColor(new Zend_Pdf_Color_Html('#ff7200'));
						$this->_setFontRegular($page, 9);
                        if($giftvoucher->getExpiredAt()) {
                            $page->drawText(Mage::getModel('core/date')->date('M d, Y',$giftvoucher->getExpiredAt()), 165, $this->y-49 , 'UTF-8');
                        } else {
                            $page->drawText(Mage::helper('giftvoucher')->__('Permanent'), 165, $this->y-49 , 'UTF-8');
                        }
						
						/*To*/
						// $page->setFillColor(new Zend_Pdf_Color_Html('#000000'));
						// $this->_setFontRegular($page, 8);
						// $page->drawText(Mage::helper('giftvoucher')->__('TO: '), 85, $this->y-70 , 'UTF-8');
						// $page->drawText(Mage::helper('giftvoucher')->__('.....................................................................................................................................................................................................................................'), 98, $this->y-71 , 'UTF-8');
						// $page->setFillColor(new Zend_Pdf_Color_Html('#ff7200'));
						// $this->_setFontRegular($page, 8);
						// $page->drawText($giftvoucher->getRecipientName(), 108, $this->y-69 , 'UTF-8');
						
						/*Message*/
						// $page->setFillColor(new Zend_Pdf_Color_Html('#000000'));
						// $this->_setFontRegular($page, 8);
						// $page->drawText(Mage::helper('giftvoucher')->__('MESSAGE: '), 85, $this->y-90 , 'UTF-8');
						// $page->drawText(Mage::helper('giftvoucher')->__('......................................................................................................................................................................................................................'), 124, $this->y-91 , 'UTF-8');
						// $page->drawText(Mage::helper('giftvoucher')->__('.............................................................................................................................................................................................................................................'), 85, $this->y-111 , 'UTF-8');
						// $page->setFillColor(new Zend_Pdf_Color_Html('#ff7200'));
						// $this->_setFontRegular($page, 8);
						// $page->drawText(substr($giftvoucher->getFormatedMessage(),0,100), 135, $this->y-89 , 'UTF-8');
						// $page->drawText(substr($giftvoucher->getFormatedMessage(),100), 95, $this->y-109 , 'UTF-8');
						
						/*FROM*/
						// $page->setFillColor(new Zend_Pdf_Color_Html('#000000'));
						// $this->_setFontRegular($page, 8);
						// $page->drawText(Mage::helper('giftvoucher')->__('FROM: '), 85, $this->y-130 , 'UTF-8');
						// $page->drawText(Mage::helper('giftvoucher')->__('................................................................................................................................................................................................................................'), 108, $this->y-131 , 'UTF-8');
						// $page->setFillColor(new Zend_Pdf_Color_Html('#ff7200'));
						// $this->_setFontRegular($page, 8);
						// $page->drawText($giftvoucher->getCustomerName(), 118, $this->y-129 , 'UTF-8');
						
                        /* decrease line */
                        $this->y += 70;
                        
						/*REDEMPTION*/
						$page->setFillColor(new Zend_Pdf_Color_Html('#000000'));
						$this->_setFontRegular($page, 8);
						$page->drawText(Mage::helper('giftvoucher')->__('REDEMPTION: '), 85, $this->y-150 , 'UTF-8');
						
						$page->setFillColor(new Zend_Pdf_Color_RGB(1, 1, 1));
						$page->setLineColor(new Zend_Pdf_Color_Html('#999999'));
						$page->setLineWidth(0.5);
						$page->drawRectangle(150, $this->y-139, 325, $this->y-154);
						
						$page->setFillColor(new Zend_Pdf_Color_Html('#ff7200'));
						$this->_setFontBold($page, 10);
						$page->drawText($giftvoucher->getGiftCode(), 155, $this->y-150 , 'UTF-8');
						
						/*TOTAL THE VALUE OF*/
						$currency = Mage::getModel('directory/currency')->load($giftvoucher->getCurrency());
						$page->setFillColor(new Zend_Pdf_Color_Html('#000000'));
						$this->_setFontRegular($page, 8);
						$page->drawText(Mage::helper('giftvoucher')->__('TOTAL THE VALUE OF: '), 335, $this->y-150 , 'UTF-8');
						$page->drawText(Mage::helper('giftvoucher')->__('................................................'), 418, $this->y-151 , 'UTF-8');
						$page->setFillColor(new Zend_Pdf_Color_Html('#ff7200'));
						$this->_setFontBold($page, 18);
						$page->drawText($currency->formatTxt($giftvoucher->getBalance()), 425, $this->y-150 , 'UTF-8');
						
						/*NOTE*/
						$page->setFillColor(new Zend_Pdf_Color_Html('#000000'));
						$this->_setFontBold($page, 8);
						$page->drawText(Mage::helper('giftvoucher')->__('Notes: '), 85, $this->y-175 , 'UTF-8');
						
						$page->setFillColor(new Zend_Pdf_Color_Html('#000000'));
						$font = $this->_setFontItalic($page, 8);
                        $notes = $this->getPrintNotes($giftvoucher, $font);
                        if ($giftvoucher->getDescription()) {
                            $notes = array_merge($notes, $this->wrapWordLine($giftvoucher->getDescription(), $font));
                        }
                        $drawY = $this->y-185;
                        foreach ($notes as $note) {
                            $page->drawText($note, 85, $drawY, 'UTF-8');
                            $drawY -= 9;
                        }
                        
                        /* return line */
                        $this->y -= 70;
                        
						// $page->drawText($giftvoucher->getPrintNotes(), 85, $this->y-185 , 'UTF-8');
					}
					$temp = $this->y-245;
					$this->y = $temp;
				}
			}
		}
		return $pdf;
	}
    
    public function getPrintNotes($giftvoucher, $font, $fontSize = 8) {
        $keyCache = 'print_notes_' . $giftvoucher->getStoreId();
        if (!$this->hasData($keyCache)) {
            $notes = $this->wrapWordLine($giftvoucher->getPrintNotes(), $font, $fontSize);
            $this->setData($keyCache, array_filter($notes));
        }
        return $this->getData($keyCache);
    }
    
    public function wrapWordLine($string, $font, $fontSize = 8) {
        $notes = array();
        $string = str_replace(array(chr(10),chr(13)), array(' ', ' '), $string);
        $words = array_filter(explode(' ', $string));
        $string = '';
        foreach ($words as $word) {
            if ($this->widthForStringUsingFontSize($string . ' ' . $word, $font, $fontSize) > 416) {
                if ($string) {
                    $notes[] = $string;
                    $string = $word;
                } else {
                    $notes[] = $word;
                    $string = '';
                }
            } else {
                $string .= ' ' . $word;
            }
        }
        if ($string) {
            $notes[] = $string;
        }
        return $notes;
    }
    
    public function widthForStringUsingFontSize($string, $font, $fontSize) {
        $drawingString = '"libiconv"' == ICONV_IMPL ?
            iconv('UTF-8', 'UTF-16BE//IGNORE', $string) :
            @iconv('UTF-8', 'UTF-16BE', $string);

        $characters = array();
        for ($i = 0; $i < strlen($drawingString); $i++) {
            $characters[] = (ord($drawingString[$i++]) << 8) | ord($drawingString[$i]);
        }
        $glyphs = $font->glyphNumbersForCharacters($characters);
        $widths = $font->widthsForGlyphs($glyphs);
        $stringWidth = (array_sum($widths) / $font->getUnitsPerEm()) * $fontSize;
        return $stringWidth;
    }
	
	protected function _beforeGetPdf() {
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);
    }
	
	protected function _afterGetPdf() {
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(true);
    }
	
	protected function _setPdf(Zend_Pdf $pdf)
    {
        $this->_pdf = $pdf;
        return $this;
    }
	
	protected function _setFontRegular($object, $size = 7)
    {
        $font = Zend_Pdf_Font::fontWithPath(Mage::getBaseDir() . '/lib/LinLibertineFont/LinLibertineC_Re-2.8.0.ttf');
        $object->setFont($font, $size);
        return $font;
    }

    protected function _setFontBold($object, $size = 7)
    {
        $font = Zend_Pdf_Font::fontWithPath(Mage::getBaseDir() . '/lib/LinLibertineFont/LinLibertine_Bd-2.8.1.ttf');
        $object->setFont($font, $size);
        return $font;
    }

    protected function _setFontItalic($object, $size = 7)
    {
        $font = Zend_Pdf_Font::fontWithPath(Mage::getBaseDir() . '/lib/LinLibertineFont/LinLibertine_It-2.8.2.ttf');
        $object->setFont($font, $size);
        return $font;
    }
}