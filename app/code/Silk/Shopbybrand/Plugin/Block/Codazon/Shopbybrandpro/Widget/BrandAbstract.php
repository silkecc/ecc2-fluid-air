<?php

namespace Silk\Shopbybrand\Plugin\Block\Codazon\Shopbybrandpro\Widget;

class BrandAbstract
{

    protected $_cache;
    protected $_cacheStage;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;


    public function __construct(
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\App\Cache\StateInterface $cacheStage,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->_cache = $cache;
        $this->_cacheStage = $cacheStage;
        $this->serializer   = $serializer;
        $this->storeManager = $storeManager;
    }

    public function aroundGetBrandObject(
        \Codazon\Shopbybrandpro\Block\Widget\BrandAbstract $subject,
        callable $proceed,
        $orderBy = 'brand_label',
        $order = 'asc',
        $onlyFeaturedBrands = false,
        $getCount = false,
        $limit = false)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/plugin_aroundgetbrandobject.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $starttime = microtime(true);
        $currentStoreCode = $this->storeManager->getStore()->getCode();
        $registryName = 'cdz_brand_' . $orderBy . '_' . $order . '_' . (string)$onlyFeaturedBrands . '_' . (string)$limit . '_' . $currentStoreCode;
        $logger->info($registryName);

        $brandObject = [];

        if($this->_cacheStage->isEnabled('collections')){
            $cachedBrandObject = $this->_cache->load($registryName);
            if($cachedBrandObject){
                $cachedBrandsData = $this->serializer->unserialize($cachedBrandObject);
                $logger->info("Loaded from cache.");
                $logger->info($cachedBrandsData);
                foreach ($cachedBrandsData as $brandData) {
                    $brandObject[] = new \Magento\Framework\DataObject($brandData);;
                }
            }
        }

        if(empty($brandObject)){
            $logger->info("Not found from cache.");
            $brandObject = $proceed($orderBy, $order, $onlyFeaturedBrands, $getCount, $limit);
            $cachingBrandsData = [];
            if(!empty($brandObject)){
                foreach ($brandObject as $brandModel) {
                    $cachingBrandsData[] = $brandModel->getData();
                }
                $cachingBrands = $this->serializer->serialize($cachingBrandsData);
                $this->_cache->save($cachingBrands,$registryName);
            }
        }

        $endtime = microtime(true);
        $timediff = $endtime - $starttime;

        $logger->info("Overall duration: ".$timediff);

        return $brandObject;
    }
}



