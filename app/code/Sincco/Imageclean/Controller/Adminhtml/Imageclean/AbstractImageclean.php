<?php
/**
 * # NOTICE OF LICENSE
 * This work is licensed under a ***Creative Commons Attribution-NonCommercial-
 * NoDerivs 3.0 Unported License*** http://creativecommons.org/licenses/by-nc-nd/3.0
 *
 * ## Authors
 *
 * Iván Miranda @ivanmiranda
 */
namespace Sincco\Imageclean\Controller\Adminhtml\Imageclean;

abstract class AbstractImageclean extends \Magento\Backend\App\Action {

    protected function _isAllowed() 
	{
        return $this->_authorization->isAllowed('Sincco_Imageclean::imageclean');
    }






}
