<?php

class Mage_PagLegal_Block_Form_PagLegal  extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construnct();
        $this->setTemplate('paglegal/form/paglegal.phtml');
  }
}
