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

use Sincco\Imageclean\Model\ImagecleanFactory;
use Magento\Backend\App\Action\Context;

class Delete extends AbstractImageclean
{
    /**
     * @var ImagecleanFactory
     */
    protected $_modelImagecleanFactory;

    public function __construct(Context $context, 
        ImagecleanFactory $modelImagecleanFactory)
    {
        $this->_modelImagecleanFactory = $modelImagecleanFactory;

        parent::__construct($context);
    }

    public function execute() {
        if ($this->getRequest()->getParam('id') > 0) 
		{
            try 
			{
				$mediaPath = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'pub'.DIRECTORY_SEPARATOR.'media'.DIRECTORY_SEPARATOR.'catalog'.DIRECTORY_SEPARATOR.'product';
                $model = $this->_modelImagecleanFactory->create();
                $model->load($this->getRequest()->getParam('id'));
                unlink($mediaPath.$model->getFilename());
                $model->setId($this->getRequest()->getParam('id'))->delete();

                $this->messageManager->addSuccess(__('Image was successfully deleted'));
                $this->_redirect('*/*/');
            } 
			catch (\Exception $e) 
			{
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
            }
        }
        $this->_redirect('*/*/');
    }
}
