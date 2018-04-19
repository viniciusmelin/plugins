<?php 

class Mage_PagLegal_Model_Paymentmethod extends Mage_Payment_Model_Method_Abstract
{
  protected $_code = 'mage_paglegal';
  protected $_formBlockType = 'paglegal/form_paglegal';
  protected $_infoBlockType = 'paglegal/info_paglegal';

  public function assignData($data)
  {
    $info = $this->getInfoInstance();

    if ($data->getCpf()) {
      $info->setCpf($data->getCpf());
    }

    return $this;
  }

  public function validate()
  {
    parent::validate();
    $info = $this->getInfoInstance();

    if (!$info->getCpf()) {
      $errorCode = 'invalid_data';
      $errorMsg = $this->_getHelper()->__("Cpf é obrigatório!.\n");
    } else {

      if(!validar_cpf($info->getCpf()))
      {
        $errorCode = 'invalid_data';
        $errorMsg = $this->_getHelper()->__("Cpf é invalido!.\n");
      }
    }

    if ($errorMsg) {
      Mage::throwException($errorMsg);
    }

    return $this;
  }

  public function getOrderPlaceRedirectUrl()
  {
    return Mage::getUrl('paglegal/payment/redirect', array('_secure' => false));
  }

  function validar_cpf($cpf)
  {
    $cpf = preg_replace('/[^0-9]/', '', (string)$cpf);
    if (strlen($cpf) != 11)
      return false;

    for ($i = 0, $j = 10, $soma = 0; $i < 9; $i++, $j--)
      $soma += $cpf {
      $i} * $j;
    $resto = $soma % 11;
    if ($cpf {
      9} != ($resto < 2 ? 0 : 11 - $resto))
      return false;

    for ($i = 0, $j = 11, $soma = 0; $i < 10; $i++, $j--)
      $soma += $cpf {
      $i} * $j;
    $resto = $soma % 11;
    return $cpf {
      10} == ($resto < 2 ? 0 : 11 - $resto);
  }
}