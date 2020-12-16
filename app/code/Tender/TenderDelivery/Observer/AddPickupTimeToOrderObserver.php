<?php
namespace Tender\TenderDelivery\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AddPickupTimeToOrderObserver implements ObserverInterface
{
    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;
    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    protected $objectCopyService;
    protected $quoteFactory;

    
    /**
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     */
    public function __construct(
      \Magento\Framework\DataObject\Copy $objectCopyService,
      \Magento\Quote\Model\QuoteFactory $quoteFactory,
      \Magento\Quote\Model\QuoteRepository $quoteRepository
      ) {
        $this->objectCopyService = $objectCopyService;
        $this->quoteRepository = $quoteRepository;
        $this->quoteFactory = $quoteFactory;

    }
    
    /**
     * Set delivery datetime to order
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        
        /* @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');
        /* @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getData('quote');
        $quote = $this->quoteFactory->create()->load($quote->getId());

        $this->objectCopyService->copyFieldsetToTarget('sales_convert_quote', 'to_order', $quote, $order);
        
        //$quote = $observer->getQuote();
        //$deliveryTime = $quote->getDeliveryDatetime();
        //
        //
        //if (!$deliveryTime) {
        //    return $this;
        //}
        //$order->setData('delivery_datetime', $deliveryTime)->save();

        return $this;
    }
}
