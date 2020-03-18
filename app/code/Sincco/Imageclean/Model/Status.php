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

use Magento\Framework\DataObject;

class Status extends DataObject {

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    static public function getOptionArray() {
        return [
            self::STATUS_ENABLED => __('Enabled'),
            self::STATUS_DISABLED => __('Disabled')
        ];
    }

}
