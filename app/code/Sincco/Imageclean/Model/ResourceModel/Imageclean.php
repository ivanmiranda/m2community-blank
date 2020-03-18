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
namespace Sincco\Imageclean\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Imageclean extends AbstractDb {

    protected function _construct() 
	{
        $this->_init('imageclean', 'imageclean_id');
    }

}
