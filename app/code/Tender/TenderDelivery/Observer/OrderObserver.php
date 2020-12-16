<?php
namespace Tender\TenderDelivery\Observer;
 
use Magento\Framework\Event\ObserverInterface;
 
class OrderObserver implements ObserverInterface
{
    
    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;
    
    /**
     * @var \Tender\TenderDelivery\Helper\Data
     */
    protected $tenderDeliveryHelper;

    public function __construct(
        \Tender\TenderDelivery\Helper\Data $tenderDeliveryHelper,
        \Magento\Quote\Model\QuoteRepository $quoteRepository
    ) {
        $this->tenderDeliveryHelper = $tenderDeliveryHelper;
        $this->quoteRepository = $quoteRepository;
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if($this->tenderDeliveryHelper->isTookanEnabled()){
            
        
           	$order = $observer->getEvent()->getOrder();
            $shippingAddress = $order->getShippingAddress();
            
            if($order->getShippingMethod()=='tenderschedule_tenderschedule'
               || $order->getShippingMethod()=='tenderinstant_tenderinstant'){
               

                // logined customer
                 if ($order->getCustomerFirstname()) {
                   $customerName = $order->getCustomerName();
                 } else {
                  // guest customer
                   $billingAddress = $order->getBillingAddress();
                   $customerName = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
                 }

        
                $deliveryTime = '';
                $pickupTime = '';
                 if($order->getShippingMethod()=='tenderschedule_tenderschedule'){
                    $deliveryTime = $order->getDeliveryDatetime();
                    //$pickupTime = date('Y-m-d H:i:s',strtotime('-45 minutes',strtotime($deliveryTime)));
                    $pickupTime = date("Y-m-d H:i:s", strtotime("{$deliveryTime} -45 minutes"));
                }else{
                    $orderDate = $order->getCreatedAt();            
                    $pickupTime = date("Y-m-d H:i:s", strtotime("{$orderDate} +30 minutes"));
                    $deliveryTime = date("Y-m-d H:i:s", strtotime("{$orderDate} +1 hour"));
                }
                
                
                
                $customerAddressData = array();        
                $customerAddressData['street'] = implode(',', $shippingAddress->getStreet());
                $customerAddressData['city'] = $shippingAddress->getCity();
                $customerAddressData['region'] = $shippingAddress->getRegion();
                $customerAddressData['country'] = $this->tenderDeliveryHelper->getCountryName($shippingAddress->getCountryId());
                $customerAddressData['postcode'] = $shippingAddress->getPostcode();
                
                $customerAddress = implode(',', $customerAddressData);
                
                $googleApi = "https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($customerAddress)."&key=".$this->tenderDeliveryHelper->getGoogleMapKey();
        				
                $addressJsonData =  file_get_contents($googleApi);
                $addressDatas = json_decode($addressJsonData, 1);
                $customerAddressLat = '';
                $customerAddressLng = '';
                foreach($addressDatas['results'] as $addressData){    
                   $customerAddressLat = $addressData['geometry']['location']['lat'];
                   $customerAddressLng = $addressData['geometry']['location']['lng'];
                }
                
                $pickupPoint = $this->tenderDeliveryHelper->getPickupPoint();
                $pickupAddress = array();        
                $pickupAddress['street'] = $pickupPoint->getStreet();
                $pickupAddress['city'] = $pickupPoint->getCity();
                $pickupAddress['region'] = $pickupPoint->getRegion();
                $pickupAddress['country'] = $pickupPoint->getCountry();
                $pickupAddress['postcode'] = $shippingAddress->getPostcode();
                $pickupAddress = implode(',',$pickupAddress);
                
                
                
                //exit;
                $pickupdata = array(
                    "api_key"=> $this->tenderDeliveryHelper->getTookanApiKey(),
                    "order_id"=> $order->getIncrementId(),
                    "team_id"=> "",
                    "auto_assignment"=> "0",
                    "job_description"=> "Ordine da e-commerce",
                    "job_pickup_phone"=> $pickupPoint->getPhone(),
                    "job_pickup_name"=> $pickupPoint->getName(),
                    "job_pickup_email"=> $pickupPoint->getEmail(),
                    "job_pickup_address"=> $pickupAddress,
                    "job_pickup_latitude"=> $pickupPoint->getLatitude(),
                    "job_pickup_longitude"=> $pickupPoint->getLongitude(),
                    "job_pickup_datetime"=> $pickupTime,
                    "customer_email" => $order->getCustomerEmail()?$order->getCustomerEmail():$shippingAddress->getEmail(),
                    "customer_username"=> $customerName,
                    "customer_phone"=> $shippingAddress->getTelephone(),
                    "customer_address"=> $customerAddress,
                    "latitude"=> $customerAddressLat,
                    "longitude"=> $customerAddressLng,
                    "job_delivery_datetime"=> $deliveryTime,
                    "has_pickup"=> "1",
                    "has_delivery"=> "1",
                    "layout_type"=> "0",
                    "tracking_link"=> 1,
                    "timezone"=> "-330",
                    "notify"=> 1,
                    "tags"=> "",
                    "geofence"=> 0,
                    "ride_type"=> 0
                );
                
                $tookanEndPoints = $this->tenderDeliveryHelper->getEndpointUrls();
                $tookanTaskCreateApiUrl = $tookanEndPoints->getTaskCreateApiUrl();
                $tookanApiRequest = $this->tenderDeliveryHelper->callApi($tookanTaskCreateApiUrl,json_encode($pickupdata));
                
                
                
                $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/tender.log');
                $logger = new \Zend\Log\Logger();
                $logger->addWriter($writer);
                $logger->info(print_r($pickupdata,true));

                $logger->info(print_r($tookanApiRequest,true));
            }
        }
    
        //exit;
 
    }
}