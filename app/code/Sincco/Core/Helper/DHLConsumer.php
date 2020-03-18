<?php

namespace Sincco\Core\Helper;

use \Magento\Framework\Exception\LocalizedException;

/**
 * Class DHLConsumer
 * @package Sincco\Core\Model
 */
class DHLConsumer
{
	/**
	 * @var \Magento\Sales\Model\OrderRepository
	 */
	protected $_orderRepository;
	/**
	 * @var \Magento\Sales\Model\Convert\Order
	 */
	protected $_convertOrder;
	/**
	 * @var ShipmentNotifier
	 */
	protected $_shipmentNotifier;

	protected $shipmentRequestFactory;
	protected $dhlCarrier;
	protected $_storeInfo;
	protected $_store;
	protected $_trackFactory;
	protected $dir;
	protected $_helper;

	private $_order;
	private $_data;
	private $_boxes;
	private $_totalToShip;

	const STANDARD_VOLUME = 150;
	const STANDARD_WEIGHT = 0.35;

	/*
	 * @var ScopeConfigInterface
	 */
	protected $_scopeConfig;

	public function __construct(
		\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
		\Magento\Sales\Model\Convert\Order $convertOrder,
		\Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
		\Magento\Shipping\Model\Shipment\RequestFactory $shipmentRequestFactory,
		\Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
		\Magento\Framework\Filesystem\DirectoryList $dir,
		\Magento\Store\Model\Information $storeInfo,
		\Magento\Store\Model\Store $store,
		\Sincco\Core\Helper\Core $helper
	) {
		$this->_orderRepository = $orderRepository;
		$this->_convertOrder = $convertOrder;
		$this->_shipmentNotifier = $shipmentNotifier;
		$this->shipmentRequestFactory = $shipmentRequestFactory;
		$this->_trackFactory = $trackFactory;
		$this->dir = $dir;
		$this->_storeInfo = $storeInfo;
		$this->_store = $store;
		$this->_helper = $helper;
	}

	private function getHeaders()
	{
		$this->_data = '
			<?xml version="1.0" encoding="UTF-8"?>
				<req:ShipmentValidateRequest xmlns:req="http://www.dhl.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.dhl.com ship-val-req.xsd">
					<Request>
						<ServiceHeader>
							<SiteID>v62_awSAHHy8d8</SiteID>
							<Password>j041pJwXPJ</Password>
						</ServiceHeader>
					</Request>
					<RequestedPickupTime>N</RequestedPickupTime>
					<NewShipper>N</NewShipper>
					<LanguageCode>EN</LanguageCode>
					<PiecesEnabled>Y</PiecesEnabled>
					<Billing>
						<ShipperAccountNumber>988154652</ShipperAccountNumber>
						<ShippingPaymentType>S</ShippingPaymentType>
						<BillingAccountNumber>988154652</BillingAccountNumber>
						<DutyPaymentType>S</DutyPaymentType>
						<DutyAccountNumber>988154652</DutyAccountNumber>
					</Billing>';
	}

	private function getConsignee()
	{
		$recipientAddress = $this->_order->getShippingAddress()->getData();
		$this->_data .= '<Consignee>';
		$this->_data .= '<CompanyName>' . $recipientAddress['firstname'] . ' ' . $recipientAddress['middlename'] . ' ' . $recipientAddress['lastname'] . '</CompanyName>';
		$this->_data .= '<AddressLine>' . $recipientAddress['street'] . '</AddressLine>';
		$this->_data .= '<City>' . $recipientAddress['city'] . '</City>';
		$this->_data .= '<Division>' . $recipientAddress['region'] . '</Division>';
		$this->_data .= '<PostalCode>' . $recipientAddress['postcode'] . '</PostalCode>';
		$this->_data .= '<CountryCode>MX</CountryCode>';
		$this->_data .= '<CountryName>Mexico</CountryName>';
		$this->_data .= '<Contact>';
		$this->_data .= '<PersonName>' . $recipientAddress['firstname'] . ' ' . $recipientAddress['middlename'] . ' ' . $recipientAddress['lastname'] . '</PersonName>';
		$this->_data .= '<PhoneNumber>' . $recipientAddress['telephone'] . '</PhoneNumber>';
		$this->_data .= '</Contact>';
		$this->_data .= '</Consignee>';
	}

	private function getCommodity()
	{
		$this->_data .= '
			<Commodity>
				<CommodityCode>1</CommodityCode>
			</Commodity>';
	}

	private function defineBoxes()
	{
		$boxes = [];
		$boxes[1] = ['name'=>'No 3', 'width'=>20, 'height'=>7, 'length'=>5, 'volume'=>700];
		$boxes[2] = ['name'=>'No 69', 'width'=>20, 'height'=>7, 'length'=>11, 'volume'=>1500];
		$boxes[3] = ['name'=>'No 5', 'width'=>20, 'height'=>15, 'length'=>15, 'volume'=>4500];
		$this->_boxes = $boxes;
	}

	private function selectBoxByVolumeToShip($volume)
	{
		$selectedBox = false;
		foreach ($this->_boxes as $id => $box)
		{
			if ($volume <= $box['volume'])
			{
				$selectedBox = $id;
				$pieces = $volume / self::STANDARD_VOLUME;
				break;
			}
		}
		if (!$selectedBox)
		{
			$id = count($this->_boxes);
			$box = $this->_boxes[$id];
			$pieces = $box['volume'] / self::STANDARD_VOLUME;
			$selectedBox = $id;
		}
		return ['id'=>$selectedBox, 'pieces'=>$pieces];
	}

	private function getPieces()
	{
		$this->_totalToShip = $this->processItems();
		$packages = [];
		$boxes = [];
		$pieces = 0;
		$xml = '';
		while ($this->_totalToShip['volumeToShip'] > 0)
		{
			$selected = $this->selectBoxByVolumeToShip($this->_totalToShip['volumeToShip']);
			$box = $selected['id'];
			$this->_totalToShip['volumeToShip'] -= $this->_boxes[$box]['volume'];
			if (!isset($boxes[$box]))
			{
				$boxes[$box] = 0;
			}
			$boxes[$box] ++;

			$pieces++;
			$box = $this->_boxes[$box];
			$xml .= '<Piece>';
			$xml .= '<PieceID>' . $pieces . '</PieceID>';
			$xml .= '<PackageType>CP</PackageType>';
			$xml .= '<Weight>' . round($selected['pieces'] * self::STANDARD_WEIGHT, 1) . '</Weight>';
			$xml .= '<Width>' . $box['width'] . '</Width>';
			$xml .= '<Height>' . $box['height'] . '</Height>';
			$xml .= '<Depth>' . $box['length'] . '</Depth>';
			$xml .= '</Piece>';
		}
		return ['pieces'=>$pieces, 'boxes'=>$boxes, 'xml'=>$xml];
	}

	private function getReference()
	{
		$this->_data .= '
			<Reference>
				<ReferenceID>shipment reference</ReferenceID>
				<ReferenceType>St</ReferenceType>
			</Reference>';
	}

	private function getShipmentDetails()
	{
		$piecesData = $this->getPieces();
		$this->_data .= '<ShipmentDetails>';
		$this->_data .= '<NumberOfPieces>' . $piecesData['pieces'] . '</NumberOfPieces>';
		$this->_data .= '<Pieces>';
		$this->_data .= $piecesData['xml'];
		$this->_data .= '</Pieces>';
		$this->getShipmentDetailsTotal();
		$this->_data .= '</ShipmentDetails>';
	}

	private function getShipmentDetailsTotal()
	{
		$this->_data .= '<Weight>' . round($this->_totalToShip['weightToShip'], 1) . '</Weight>';
		$this->_data .= '<WeightUnit>K</WeightUnit>';
		$this->_data .= '<GlobalProductCode>N</GlobalProductCode>';
		$this->_data .= '<LocalProductCode>N</LocalProductCode>';
		$this->_data .= '<Date>' . date('Y-m-d') . '</Date>';
		$this->_data .= '<Contents>DHL Parcel</Contents>';
		$this->_data .= '<DoorTo>DD</DoorTo>';
		$this->_data .= '<DimensionUnit>C</DimensionUnit>';
		$this->_data .= '<PackageType>CP</PackageType>';
		$this->_data .= '<IsDutiable>Y</IsDutiable>';
		$this->_data .= '<CurrencyCode>MXN</CurrencyCode>';
	}

	private function getShipper()
	{
		$storeInfo = $this->_storeInfo->getStoreInformationObject($this->_store)->getData();
		$this->_data .= '<Shipper>';
		$this->_data .= '<ShipperID>988154652</ShipperID>';
		$this->_data .= '<CompanyName>Universo de Fragancias</CompanyName>';
		$this->_data .= '<RegisteredAccount>988154652</RegisteredAccount>';
		$this->_data .= '<AddressLine>Montes Urales 424</AddressLine>';
		$this->_data .= '<City>CDMX</City>';
		$this->_data .= '<Division>CDMX</Division>';
		$this->_data .= '<PostalCode>01100</PostalCode>';
		$this->_data .= '<CountryCode>MX</CountryCode>';
		$this->_data .= '<CountryName>Mexico</CountryName>';
		$this->_data .= '<Contact>';
		$this->_data .= '<PersonName>Admin</PersonName>';
		$this->_data .= '<PhoneNumber>56581111</PhoneNumber>';
		$this->_data .= '</Contact>';
		$this->_data .= '</Shipper>';
	}

	private function getFooter()
	{
		$this->_data .= '
			<LabelImageFormat>PDF</LabelImageFormat>
		</req:ShipmentValidateRequest>';
	}

	private function processItems()
	{
		$qtyToShip = 0;
		$weightToShip = 0;
		$volumeToShip = 0;
		foreach ($this->_order->getAllItems() AS $orderItem) {
		// Check if order item has qty to ship or is virtual
			if ($orderItem->getQtyToShip() || ! $orderItem->getIsVirtual()) {
				$itemVolume = $orderItem->getLength() * $orderItem->getWidth() * $orderItem->getHeight();
				$itemVolume = ($itemVolume == 0 ? self::STANDARD_VOLUME : $itemVolume);
				$qtyToShip += $orderItem->getQtyToShip();
				$weightToShip += $orderItem->getWeight() * $orderItem->getQtyToShip();
				$volumeToShip += $itemVolume * $orderItem->getQtyToShip();
			}
		}
		return ['qtyToShip'=>$qtyToShip, 'weightToShip'=>$weightToShip, 'volumeToShip'=>$volumeToShip];
	}

	private function consumeService()
	{
		try
		{
//			$this->_helper->log('DHL', $this->_data);
			// $url = 'https://xmlpi-ea.dhl.com/XMLShippingServlet';
			$url = 'https://xmlpitest-ea.dhl.com/XMLShippingServlet';
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output = curl_exec($ch);
			curl_close($ch);
			$xml = simplexml_load_string($output);
			$data = json_decode(json_encode((array) $xml), true);
			return $data;
		} catch (\Exception $e) {
			$this->_helper->log('ERROR AL CONSUMIR DHL');
			return false;
		}
	}

	private function _prepareShipment($order)
	{
		$shipment = $this->_convertOrder->toShipment($order);
		if (!$shipment->getTotalQty()) {
			return false;
		}
		return $shipment->register();
	}

	private function shipOrder($tracking)
	{
		$order = $this->_orderRepository->get($this->_order->getId());
		// to check order can ship or not
		if (!$order->canShip())
		{
			return false;
		}
		$orderShipment = $this->_convertOrder->toShipment($order);
		foreach ($order->getAllItems() AS $orderItem)
		{
			// Check virtual item and item Quantity
			if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual())
			{
				continue;
			}
			$qty = $orderItem->getQtyToShip();
			$shipmentItem = $this->_convertOrder->itemToShipmentItem($orderItem)->setQty($qty);
			$orderShipment->addItem($shipmentItem);
		}
		$track = $this->_trackFactory->create()->addData(
			[
				'carrier_code'=>'dhl',
				'title'=>'Envío gestionado por DHL',
				'number'=>$tracking['track']
			]
		);
		$orderShipment->addTrack($track);
		$orderShipment->register();
		$orderShipment->getOrder()->setIsInProcess(true);
		try {
			// Save created Order Shipment
			$orderShipment->save();
			$orderShipment->getOrder()->save();
			// Send Shipment Email
			$this->_shipmentNotifier->notify($orderShipment);
			$orderShipment->save();
			return true;
		} catch (\Exception $e) {
			$this->_helper->log('No se puede hacer el envio de la orden');
			return false;
		}
	}

	/**
	 * @param \Magento\Sales\Model\Order $order
	 */
	public function createShipment($order)
	{
		$this->_order = $order;
		if (!$this->_order->canShip())
		{
			return false;
		}
		$this->defineBoxes();
	// Create XML
		$this->getHeaders();
		$this->getConsignee();
		$this->getCommodity();
		$this->getReference();
		$this->getShipmentDetails();
		$this->getShipper();
		$this->getFooter();
	// Get Label & Tracking
		$response = $this->consumeService();
		if ($response != false)
		{
			$trackingId = $response['AirwayBillNumber'];
			$label = $response['LabelImage'];
			$response = $this->shipOrder(['track'=>$trackingId, 'order'=>$this->_order->getIncrementId()]);
			if ($response)
			{
				$this->_helper->log('DHL', $this->_data);
				if ($label['OutputFormat'] == 'PDF')
				{
					$fileName = $this->dir->getPath('pub') . '/media/' . $this->_order->getIncrementId() . '.pdf';
					file_put_contents($fileName, base64_decode($label['OutputImage']));

				}
				$response = ['tracking'=>$trackingId, 'label'=>$fileName];
			} else
			{
				$response = false;
			}
		} else
		{
			$response = false;
		}
		return $response;
	}
}
