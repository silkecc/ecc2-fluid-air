<?php

namespace Silk\ProductFilter\Plugin\Block\Codazon\ProductFilter\Product;

class ProductsList
{
    /**
     * @var \Epicor\Comm\Helper\Data
     */
    protected $commHelper;

    protected $_cache;
    protected $_cacheStage;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    protected $productCollectionFactory;

    protected $commMessagingHelper;

    public function __construct(
        \Epicor\Comm\Helper\Data $commHelper,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\App\Cache\StateInterface $cacheStage,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Epicor\Comm\Helper\Messaging $commMessagingHelper,
        \Epicor\Lists\Helper\Frontend\Pricelist $listsFrontendPricelistHelper
    )
    {
        $this->commHelper = $commHelper;
        $this->_cache = $cache;
        $this->_cacheStage = $cacheStage;
        $this->serializer   = $serializer;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->commMessagingHelper = $commMessagingHelper;
        $this->listsFrontendPricelistHelper = $listsFrontendPricelistHelper;
    }

    public function aroundCreateCollection(\Codazon\ProductFilter\Block\Product\ProductsList $subject, callable $proceed)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/plugin_create_productfilter_collection.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $starttime = microtime(true);
        $data = [
            'is_ajax'           =>  1,
            'title'             =>  $subject->getData('title'),
            'display_type'      =>  $subject->getData('display_type'),
            'products_count'    =>  $subject->getData('products_count'),
            'order_by'          =>  $subject->getData('order_by'),
            'show'              =>  $subject->getData('show'),
            'thumb_width'       =>  $subject->getData('thumb_width'),
            'thumb_height'      =>  $subject->getData('thumb_height'),
            'filter_template'   =>  $subject->getData('filter_template'),
            'custom_template'   =>  $subject->getData('custom_template'),
            'show_slider'       =>  $subject->getData('show_slider'),
            'slider_item'       =>  $subject->getData('slider_item'),
            'conditions_encoded'        =>  $subject->getData('conditions_encoded'),
            'erp_account_id' => $this->commHelper->getErpAccountInfo() ? $this->commHelper->getErpAccountInfo()->getErpCode() : ''
        ];

        $key = md5($this->serializer->serialize($data));

        $logger->info($key);

        $entityIds = [];
        if($this->_cacheStage->isEnabled('collections')){
            //$logger->info("Try to load from cache.");
            //$loadFromCacheStartTime = microtime(true);
            $collectionData = $this->_cache->load($key);
            //$logger->info("Cached entity ids:". $collectionData);
            if($collectionData){
                $entityIds = $this->serializer->unserialize($collectionData);
                $sort = explode(' ', $subject->getData('order_by'));
                $collection = $this->productCollectionFactory
                    ->create()
                    ->addFieldToFilter('entity_id', ['in' => $entityIds]);
                $collection->addAttributeToSort($sort[0],$sort[1]);
                $collection->addAttributeToSelect('*');
                $this->commMessagingHelper->sendMsq($collection, 'featured_product');
                //$logger->info("Loaded from cache.");
            } else {
                //$logger->info("Not found from cache.");
            }
            //$endtime = microtime(true);
            //$timediff = $endtime - $loadFromCacheStartTime;
            //$logger->info("Load from cache duration: ".$timediff);
        }

        if (empty($entityIds)) {
            //$startCreateCollectionTime = microtime(true);
            //$logger->info("Data has not been found from cache.");
            $collection = $proceed();
            //$endtime = microtime(true);
            //$timediff = $endtime - $startCreateCollectionTime;
            //$logger->info("Create collection duration: ".$timediff);
            //$startSavingCacheTime = microtime(true);
            foreach ($collection as $c) {
                $entityIds[] = $c->getId();
            }
            $collectionData = $this->serializer->serialize($entityIds);
            //$logger->info("Caching entity ids:". $collectionData);
            $this->_cache->save($collectionData,$key);
            //$logger->info("Data saved to cache.");
            //$endtime = microtime(true);
            //$timediff = $endtime - $startSavingCacheTime;
            //$logger->info("Saving cache duration: ".$timediff);
        }

        $endtime = microtime(true);
        $timediff = $endtime - $starttime;

        $logger->info("Overall duration: ".$timediff);

        return $collection;
    }

    public function afterGetCacheKeyInfo(
        \Codazon\ProductFilter\Block\Product\ProductsList $controller, 
        $result
    ){
        $pricelistHelper = $this->listsFrontendPricelistHelper;
        /* @var $contractHelper Epicor_Lists_Helper_Frontend_Contract */
        $activePricelists = $pricelistHelper->getPriceLists();
        $result['ecc_pricelists'] = json_encode($activePricelists);
        $result['erp_account_id'] = $this->commHelper->getErpAccountInfo() ? $this->commHelper->getErpAccountInfo()->getErpCode() : '';
        return $result;
    }

}


