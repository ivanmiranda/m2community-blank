<?php
/**
 * # NOTICE OF LICENSE
 * This work is licensed under a ***Creative Commons Attribution-NonCommercial-
 * NoDerivs 3.0 Unported License*** http://creativecommons.org/licenses/by-nc-nd/3.0
 *
 * ## Authors
 *
 * Iván Miranda @deivanmiranda
 */
namespace Sincco\Core\Helper;

class Core
{
	protected $_logger;
	protected $_scopeConfig;
	protected $_storeManager;
	protected $_transportBuilder;
	protected $_productRepository;
	protected $_searchCriteriaBuilder;
	protected $_filterBuilder;

	public function __construct(
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
		\Magento\Catalog\Model\ProductRepository $productRepository,
		\Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
		\Magento\Framework\Api\FilterBuilder $filterBuilder
	) {
		$this->_scopeConfig  = $scopeConfig;
		$this->_storeManager = $storeManager;
		$this->_transportBuilder = $transportBuilder;
		$this->_productRepository = $productRepository;
		$this->_searchCriteriaBuilder = $searchCriteriaBuilder;
		$this->_filterBuilder = $filterBuilder;
		$writer = new \Zend\Log\Writer\Stream(BP . $scopeConfig->getValue('sincco_core/debug/path', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null));
		$this->_logger = new \Zend\Log\Logger();
		$this->_logger->addWriter($writer);
	}

	/**
	 * Get config value from a path
	 * @param string $path Config path
	 * @param integer $store Store Id
	 * @return string
	 */
	public function getConfig($path, $store = null)
	{
		return $this->_scopeConfig->getValue(
			$path,
			\Magento\Store\Model\ScopeInterface::SCOPE_STORE,
			$store
		);
	}

	/**
	  * Get internal Debug
	  * @param integer $store Store ID
	  * @return boolean
	  */
	private function getDebug($store = null)
	{
		return boolval($this->getConfig('sincco_core/debug/enabled', $store));
	}

	/**
	 * Return full media path for a file
	 * @param string $path File path
	 * @return string
	 */
	public function getMediaUrl($path = '')
	{
		$base = $this ->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
		return $base . $path;
	}

	/**
	 * Get produc object based on a field selector
	 * @param string $value Data to compare
	 * @param string $field Field to compare
	 * @return \Magento\Catalog\Model\ProductRepository
	 */
	public function getProduct($value, $field='sku')
	{
		try {
			$product = null;
			$generalFilter = [];
			$generalFilter[] = $this->_filterBuilder
				->setField($field)
				->setConditionType('eq')
				->setValue($value)
				->create();
			$searchCriteria = $this->_searchCriteriaBuilder
				->addFilters($generalFilter)
				->create();
			foreach ($this->_productRepository->getList($searchCriteria)->getItems() as $item)
			{
				$product = $item;
			}
			return $product;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Return actual Store Code
	 * @return string
	 */
	public function getStoreCode()
	{
		$store = $this->_storeManager->getStore();
		return $store->getCode();
	}

	/**
	 * Return Actual StoreId
	 * @return Integer
	 */
	public function getStoreId()
	{
		$store = $this->_storeManager->getStore()->getStoreId();
		return $store;
	}

	/**
	 * Print log if debug is enabled
	 * @param string $message Message
	 * @param array $data Complement Data
	 * @return none
	 */
	public function log($message, $data=[]) {
		if ($this->getDebug()) {
			$this->_logger->info($message . "::" . serialize($data));
		}
	}

	/**
	 * Report an error via email based on a template
	 * @param string $origin Error's origin
	 * @param string $log Log text
	 * @param string $email Email Address to report
	 * @return none
	 */
	public function sendEmail($origin, $log, $email)
	{
		try {
			$store = $this->getStoreId();
			$transport = $this->_transportBuilder->setTemplateIdentifier('sincco_core_error_template')
				->setTemplateOptions(['area' => 'frontend', 'store' => $store])
				->setTemplateVars(
					[
						'origin' => $origin,
						'log' => $log,
					]
				)
				->setFrom('general')
				->addTo($email, 'Debug')
				->getTransport();
			$transport->sendMessage();
		} catch (Exception $e) {
			var_dump('ERROR en correo');
		}
	}
}