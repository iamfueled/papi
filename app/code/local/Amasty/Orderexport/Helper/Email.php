<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */
class Amasty_Orderexport_Helper_Email extends Mage_Core_Helper_Abstract
{
    public function sendExported($profile, $filePath)
    {
        $from = Mage::getStoreConfig('trans_email/ident_general/email');

        $mail = new Zend_Mail();
        $mail->setFrom($from);
        $mail->addTo($profile->getEmailAddress());
        $mail->setSubject($profile->getEmailSubject());
        $mail->setBodyHtml(""); // here u also use setBodyText options.

        // this is for to set the file format
        $at = new Zend_Mime_Part(file_get_contents($filePath));

//        $at->type        = 'application/csv'; // if u have PDF then it would like -> 'application/pdf'
        $at->disposition = Zend_Mime::DISPOSITION_INLINE;
        $at->encoding    = Zend_Mime::ENCODING_8BIT;
        $at->filename    = basename($filePath);
        $mail->addAttachment($at);
        $mail->send();
//exit(1);
//
//        $from = Mage::getStoreConfig('trans_email/ident_general/email');
//        $message = "";
//        $headers = "From: $from";
//
//        // boundary
//        $semiRand = md5(time());
//        $boundary = "==Multipart_Boundary_x{$semiRand}x";
//
//        // headers for attachment
//        $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$boundary}\"";
//
//        // multipart boundary
//        $message = "--{$boundary}\n" . "Content-Type: text/plain; charset=\"iso-8859-1\"\n" .
//            "Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n";
//
//        // preparing attachments
//        $message .= "--{$boundary}\n";
//        $fp =    @fopen($filePath,"rb");
//        $data =    @fread($fp,filesize($filePath));
//        @fclose($fp);
//        $data = chunk_split(base64_encode($data));
//        $message .= "Content-Type: application/octet-stream; name=\"".basename($filePath)."\"\n" .
//            "Content-Description: ".basename($filePath)."\n" .
//            "Content-Disposition: attachment;\n" . " filename=\"".basename($filePath)."\"; size=".filesize($filePath).";\n" .
//            "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
//        $message .= "--{$boundary}--";
//        $returnpath = "-f" . $from;
//        mail($profile->getEmailAddress(), $profile->getEmailSubject(), $message, $headers, $returnpath);
    }
}