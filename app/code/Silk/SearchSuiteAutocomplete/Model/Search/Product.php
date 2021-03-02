<?php

namespace Silk\SearchSuiteAutocomplete\Model\Search;

use \Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use \MageWorx\SearchSuiteAutocomplete\Model\Source\AutocompleteFields;
use \MageWorx\SearchSuiteAutocomplete\Model\Source\ProductFields;

/**
 * Product model. Return product data used in search autocomplete
 */
class Product implements \MageWorx\SearchSuiteAutocomplete\Model\SearchInterface
{
    /**
     * @var \MageWorx\SearchSuiteAutocomplete\Helper\Data
     */
    protected $helperData;

    /**
     * @var \Magento\Search\Helper\Data
     */
    protected $searchHelper;

    /**
     * @var \Magento\Catalog\Model\Layer\Resolver
     */
    protected $layerResolver;


    /**
     * @var \Magento\Search\Model\QueryFactory
     */
    protected $queryFactory;

    /**
     * Product constructor.
     *
     * @param \MageWorx\SearchSuiteAutocomplete\Helper\Data $helperData
     * @param \Magento\Search\Helper\Data $searchHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Search\Model\QueryFactory $queryFactory
     */
    public function __construct(
        \MageWorx\SearchSuiteAutocomplete\Helper\Data $helperData,
        \Magento\Search\Helper\Data $searchHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Search\Model\QueryFactory $queryFactory,
        \Magento\Framework\Api\Search\SearchInterface $search,
        \Magento\Framework\Api\Search\SearchCriteriaFactory $searchCriteriaFactory,
        \Magento\Framework\Api\Search\FilterGroupBuilder $searchFilterGroupBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Epicor\Comm\Helper\Messaging $commMessagingHelper,
        \MageWorx\SearchSuiteAutocomplete\Block\Autocomplete\ProductAgregator $productAgregator,
        \Magento\Framework\App\RequestInterface $request,
        \Epicor\Lists\Helper\Frontend\Product $listsFrontendProductHelper,
        \Epicor\Lists\Helper\Frontend\Contract $listsFrontendContractHelper
    ) {

        $this->helperData = $helperData;
        $this->searchHelper = $searchHelper;
        $this->layerResolver = $layerResolver;
        $this->queryFactory = $queryFactory;
        $this->fullTextSearchApi             = $search;
        $this->fullTextSearchCriteriaFactory = $searchCriteriaFactory;
        $this->filterBuilder                 = $filterBuilder;
        $this->searchFilterGroupBuilder      = $searchFilterGroupBuilder;
        $this->productRepository             = $productRepository;
        $this->searchCriteriaBuilder         = $searchCriteriaBuilder;
        $this->storeManager                  = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->commMessagingHelper = $commMessagingHelper;
        $this->productAgregator = $productAgregator;
        $this->request = $request;
        $this->listsFrontendProductHelper = $listsFrontendProductHelper;
        $this->listsFrontendContractHelper = $listsFrontendContractHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseData()
    {
        $responseData['code'] = AutocompleteFields::PRODUCT;
        $responseData['data'] = [];

        if (!$this->canAddToResult()) {
            return $responseData;
        }

        $queryText = $this->queryFactory->get()->getQueryText();
        $productResultFields = $this->helperData->getProductResultFieldsAsArray();
        $productResultFields[] = ProductFields::URL;

        $searchResult = $this->searchProductsFullText($queryText);
        $productIds = $searchResult['product_ids'];
        $size = $searchResult['size'];

        $collection = $this->productCollectionFactory
            ->create()
            ->addFieldToFilter('entity_id', ['in' => $productIds]);
        $collection->addAttributeToSelect('*');
        if (!empty($productIds)) {
            $sortedProductIds = implode(",", $productIds);
            $collection
                ->getSelect()
                ->order(new \Zend_Db_Expr("FIELD(entity_id,$sortedProductIds)"));
        }

        /* custom */
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/searchautocompletion.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($collection->getSelect()->__toString());

        if (in_array(ProductFields::PRICE, $productResultFields)) {
            $this->commMessagingHelper->sendMsq($collection, 'featured_product');
        }

        foreach ($collection as $product) {
            $responseData['data'][] = array_intersect_key($this->getProductData($product), array_flip($productResultFields));
        }

        $responseData['size'] = $size;
        $responseData['url'] = (count($productIds) > 0) ? $this->searchHelper->getResultUrl($queryText) : '';

        return $responseData;
    }

    /**
     * Retrive product collection by query text
     *
     * @param  string $queryText
     * @return mixed
     */
    protected function getProductCollection($queryText)
    {
        $productResultNumber = $this->helperData->getProductResultNumber();

        $this->layerResolver->create(LayerResolver::CATALOG_LAYER_SEARCH);

        $productCollection = $this->layerResolver->get()
            ->getProductCollection()
            ->addAttributeToSelect([ProductFields::DESCRIPTION, ProductFields::SHORT_DESCRIPTION]);


        $searchResult = $this->searchProductsFullText($queryText);
        $productIds = $searchResult['product_ids'];
        /* custom */
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/searchautocompletion.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($productCollection->getSelect()->__toString());

        $productCollection->getSelect()->limit($productResultNumber);

        return $productCollection;
    }

    /**
     * Retrieve all product data
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    protected function getProductData($product)
    {
        /** @var \MageWorx\SearchSuiteAutocomplete\Block\Autocomplete\Product $product */
        $product = $this->productAgregator->setProduct($product);
        if (method_exists($product, 'getSmallImage')) {
            $image = $product->getSmallImage();
        } else {
            $image = $product->getImage();
        }
        $data = [
            ProductFields::NAME => $product->getName(),
            ProductFields::SKU => $product->getSku(),
            ProductFields::IMAGE => $image,
            ProductFields::REVIEWS_RATING => $product->getReviewsRating(),
            ProductFields::SHORT_DESCRIPTION => $product->getShortDescription(),
            ProductFields::DESCRIPTION => $product->getDescription(),
            ProductFields::PRICE => $product->getPrice(),
            ProductFields::ADD_TO_CART => $product->getAddToCartData(),
            ProductFields::URL => $product->getUrl()
        ];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function canAddToResult()
    {
        return in_array(
            AutocompleteFields::PRODUCT,
            $this->helperData->getAutocompleteFieldsAsArray()
        );
    }

    /**
     * Perform full text search and find IDs of matching products.
     *
     * @param $query
     *
     * @return array
     */
    protected function searchProductsFullText($query)
    {
        $searchCriteria = $this->fullTextSearchCriteriaFactory->create();
        /** To get list of available request names see Magento/CatalogSearch/etc/search_request.xml */
        $searchCriteria->setRequestName('quick_search_container');
        $filter      = $this->filterBuilder
            ->setField('search_term')
            ->setValue($query)
            ->setConditionType('like')
            ->create();
        $filterGroup = $this->searchFilterGroupBuilder->addFilter($filter)->create();
        $filterGroups = [$filterGroup];
        if ($catId = $this->request->getParam('cat')) {
            $category = $this->categoryCollectionFactory->create()
                ->addFieldToFilter('entity_id', $catId)
                ->addAttributeToSelect(['is_anchor'])
                ->getFirstItem();
            if ($category) {
                $cateFilter      = $this->filterBuilder
                    ->setField('category_ids')
                    ->setValue($category->getId())->setConditionType('eq')
                    ->create();
                $catFilterGroup = $this->searchFilterGroupBuilder
                    ->addFilter($cateFilter)
                    ->create();
                $filterGroups[] = $catFilterGroup;
            }
        }
        $searchCriteria->setFilterGroups($filterGroups);
        $searchResults = $this->fullTextSearchApi->search($searchCriteria);
        $productIds    = [];
        $products = [];
        /**
         * Full text search returns document IDs (in this case product IDs),
         * so to get products information we need to load them using filtration by these IDs
         */
        foreach ($searchResults->getItems() as $searchDocument) {
            $productId = $searchDocument->getId();
            $score = 0;
            $customAttributes = $searchDocument->getCustomAttributes();
            if ($customAttributes && isset($customAttributes['score'])) {
                $score = $customAttributes['score']->getValue();
            }
            $products[$productId] = $score;
        }
        arsort($products);
        $idx = 0;
        $productResultNumber = $this->helperData->getProductResultNumber();
        $listHelper = $this->listsFrontendProductHelper;
        /* @var $helper Epicor_Lists_Helper_Frontend_Product */
        $contractHelper = $this->listsFrontendContractHelper;
        /* @var $contractHelper Epicor_Lists_Helper_Frontend_Contract */
        if ($listHelper->hasFilterableLists() || $contractHelper->mustFilterByContract()) {
            $allowedProductIds = $listHelper->getActiveListsProductIds(true);
        } else {
            $allowedProductIds = array_keys($products);
        }

        foreach ($products as $productId => $score) {
            if (!in_array($productId, $allowedProductIds)) {
                continue;
            }
            $productIds[] = $productId;
            $idx += 1;
            if ($idx > $productResultNumber) {
                break;
            }
        }
        return [
            "product_ids" => $productIds,
            'size' => count($products)
        ];
    }
}
