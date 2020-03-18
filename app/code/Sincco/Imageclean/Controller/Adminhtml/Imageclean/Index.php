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

class Index extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;

	public function __construct(\Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

	public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Sincco_Imageclean::imageclean');
        $resultPage->addBreadcrumb(__('Sincco'), __('Sincco'));
        $resultPage->addBreadcrumb(__('Imageclean'), __('Imageclean'));
        $resultPage->getConfig()->getTitle()->prepend(__('Images Cleaner'));
		$dataPersistor = $this->_objectManager->get('Magento\Framework\App\Request\DataPersistorInterface');
        $dataPersistor->clear('imageclean_data');
        return $resultPage;
    }

	protected function _isAllowed()
    {
		return $this->_authorization->isAllowed('Sincco_Imageclean::imageclean');
    }
}
