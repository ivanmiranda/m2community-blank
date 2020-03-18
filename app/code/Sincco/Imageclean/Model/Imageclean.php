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
namespace Sincco\Imageclean\Model;

use Magento\Framework\Model\AbstractModel;

class Imageclean extends AbstractModel implements \Magento\Framework\DataObject\IdentityInterface {

	const CACHE_TAG = 'iimageclean_id';
	
    protected function _construct()
	{
        $this->_init('Sincco\Imageclean\Model\ResourceModel\Imageclean');
    }
	
	public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

}
