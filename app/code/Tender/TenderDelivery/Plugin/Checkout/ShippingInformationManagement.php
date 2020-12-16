<?php
/**
 * @author Tender
 * @package Tender_TenderDelivery
 */
namespace Tender\TenderDelivery\Plugin\Checkout;

class ShippingInformationManagement
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

    public function aroundSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        $extAttributes = $addressInformation->getExtensionAttributes();
        if ($extAttributes instanceof \Magento\Checkout\Api\Data\ShippingInformationExtension) {
            $data = [];
            if ($extAttributes->getStorepickupShippingChecked()) {
                $data = [
                    'store_pickup'          => $extAttributes->getStorePickup(),
                    'calendar_inputField'   => $extAttributes->getCalendarInputField(),
                    'mobile_delivery_date'  => $extAttributes->getMobileDeliveryDate(),
                    'mobile_delivery_time'  => $extAttributes->getMobileDeliveryTime(),
                    'tenderinstant_time'  => $extAttributes->getTenderinstantTime()
                ];
            }
            $this->tenderDeliveryHelper->setStorepickupDataToSession($data);
            $quote = $this->quoteRepository->getActive($cartId);
           
            if($addressInformation->getShippingMethodCode()=='tenderschedule'){
                $deliveryTime = $extAttributes->getMobileDeliveryTime()?$extAttributes->getMobileDeliveryTime():'12:00';
                if($extAttributes->getMobileDeliveryDate() && $deliveryTime){
                    $deliveryTime = $extAttributes->getMobileDeliveryDate().' '.$deliveryTime;
                    $quote->setDeliveryDatetime($deliveryTime);
                }
            }


            if($addressInformation->getShippingMethodCode()=='tenderinstant'){
                if($extAttributes->getTenderinstantTime()){
                    $quote->setDeliveryDatetime($extAttributes->getTenderinstantTime());
                }
            }



        }
        
        return $proceed($cartId, $addressInformation);
    }
}
