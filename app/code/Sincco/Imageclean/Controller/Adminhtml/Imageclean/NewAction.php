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

use Sincco\Imageclean\Helper\Data as HelperData;
use Magento\Backend\App\Action\Context;

class NewAction extends AbstractImageclean
{
    /**
     * @var HelperData
     */
    protected $_helperData;

    public function __construct(Context $context, HelperData $helperData)
    {
        $this->_helperData = $helperData;

        parent::__construct($context);
    }

    public function execute() {
        $this->_helperData->compareList();
        $this->_redirect('*/*/');
    }
}
