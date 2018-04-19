<?php
class Mage_PagLegal_Helper_Data extends Mage_Core_Helper_Abstract
{
    
    function getPaymentGatewayUrl() 
    {
      return Mage::getUrl('paglegal/payment/gateway', array('_secure' => false));
    }
  
}