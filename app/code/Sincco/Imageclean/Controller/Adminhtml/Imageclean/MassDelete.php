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

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Sincco\Imageclean\Model\ResourceModel\Imageclean\CollectionFactory;
use Sincco\Imageclean\Model\ImagecleanFactory;

class MassDelete extends \Magento\Backend\App\Action
{
	protected $filter;
	protected $collectionFactory;
    /**
     * @var ImagecleanFactory
     */
    protected $_modelImagecleanFactory;

    public function __construct(Context $context, Filter $filter, CollectionFactory $collectionFactory, ImagecleanFactory $modelImagecleanFactory)
    {
		$this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->_modelImagecleanFactory = $modelImagecleanFactory;
        parent::__construct($context);
    }

    public function execute()
	{
		$collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();
		$mediaPath = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'pub'.DIRECTORY_SEPARATOR.'media'.DIRECTORY_SEPARATOR.'catalog'.DIRECTORY_SEPARATOR.'product';
        foreach ($collection as $item)
		{
			$model = $this->_modelImagecleanFactory->create();
			$model->load($item->getImagecleanId());
            unlink($mediaPath.$model->getFilename());
            $item->delete();
        }

        $this->messageManager->addSuccess(__('A total of %1 image(s) have been deleted.', $collectionSize));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/index');

    }
}
