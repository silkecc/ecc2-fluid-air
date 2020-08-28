<?php

namespace Silk\ShopbyBrand\Controller\Index;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;

class View extends \Codazon\Shopbybrandpro\Controller\Index\View
{

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Catalog session
     *
     * @var \Magento\Catalog\Model\Session
     */
    protected $_catalogSession;

    /**
     * Catalog design
     *
     * @var \Magento\Catalog\Model\Design
     */
    protected $_catalogDesign;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator
     */
    protected $categoryUrlPathGenerator;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * Catalog Layer Resolver
     *
     * @var Resolver
     */
    private $layerResolver;

    /**
     *
     * @var \Epicor\Comm\Helper\Product
     */
    protected $commProductHelper;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Catalog\Model\Design $catalogDesign
     * @param \Magento\Catalog\Model\Session $catalogSession
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator $categoryUrlPathGenerator
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
     * @param Resolver $layerResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\Design $catalogDesign,
        //\Magento\Catalog\Model\Session $catalogSession,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator $categoryUrlPathGenerator,
        PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Codazon\Shopbybrandpro\Model\BrandFactory $brandFactory,
        \Codazon\Shopbybrandpro\Helper\Data $helper,
        \Codazon\AjaxLayeredNavPro\Helper\Data $ajaxLayeredNavHelper,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Epicor\Comm\Helper\Product $commProductHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->registry = $coreRegistry;
        $this->ajaxLayeredNavHelper = $ajaxLayeredNavHelper;
        $this->commProductHelper = $commProductHelper;
        $this->_logger = $logger;
        parent::__construct(
            $context,
            $catalogDesign,
            $coreRegistry,
            $storeManager,
            $categoryUrlPathGenerator,
            $resultPageFactory,
            $resultForwardFactory,
            $layerResolver,
            $categoryRepository,
            $brandFactory,
            $helper
        );
    }

    public function execute()
    {
        if ($this->getRequest()->isAjax() && $this->getRequest()->getParam('isStockUpdate')) {
            $resultJson = $this->jsonResultFactory->create();
            $responseData = ["success" => 0];
            $productIds = $this->getRequest()->getParam('productIds');
            try {
                // get Product collection with MSQ
                $collection = $this->commProductHelper->getProductCollectionByIds($productIds);

                $page = $this->resultPageFactory->create();
                $layout = $page->getLayout();
                $layout->getUpdate()->addHandle('catalog_category_view');
                $block = $layout->getBlock("price.loader.ajax")
                    ->setCollection($collection);

                foreach ($collection as $product) {

                    if ($this->registry->registry('current_product')) {
                        $this->registry->unregister('current_product');
                    }
                    $this->registry->register('current_product', $product);

                    $block->setProduct($product);

                    if (!$this->registry->registry('list_mode')) {
                        $block->setListMode($block->getMode());
                    }
                    $html = $block->toHtml();
                    $responseData["productList"][$product->getId()] = $html;
                }

                $responseData["success"] = 1;
                $resultJson->setData($responseData);
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                $responseData = ["success" => 0, "error" => $e->getMessage()];
                $resultJson->setData($responseData);
            }
            return $resultJson;
        } else {
            $page = parent::execute();
            if ($this->getRequest()->getParam('ajax_nav')) {
                $request = $this->getRequest();
                $layout = $page->getLayout();
                $result = [];
                if ($block = $layout->getBlock('category.products')) {
                    $result['category_products'] = rawurlencode($block->toHtml());
                }
                if ($block = $layout->getBlock('catalog.leftnav')) {
                    $result['catalog_leftnav'] = $block->toHtml();
                }
                if ($block = $layout->getBlock('page.main.title')) {
                    $result['page_main_title'] = $block->toHtml();
                }
                $filterManager = $this->ajaxLayeredNavHelper->getFilterManager();
                $queryValue = $request->getQueryValue();
                $newQueryValue = $queryValue;
                if ($block = $layout->getBlock('catalog.navigation.state')) {
                    $filters = $block->getActiveFilters();
                    $urlParams = [];
                    foreach ($filters as $filter) {
                        $filterModel = $filter->getFilter();
                        $code = $filterModel->getRequestVar();
                        if (isset($newQueryValue[$code])) {
                            $class = get_class($filterModel);
                            if ($class == 'Magento\CatalogSearch\Model\Layer\Filter\Attribute' || $class == 'Codazon\AjaxLayeredNavPro\Model\Layer\Filter\Attribute') {
                                $label = $filter->getLabel();
                                if (is_array($label)) {
                                    $newQueryValue[$code] = [];
                                    foreach ($label as $lb) {
                                        $newQueryValue[$code][] = $filterManager->translitUrl(htmlspecialchars_decode($lb)) ?: $lb;
                                    }
                                    $newQueryValue[$code] = trim(implode(',', $newQueryValue[$code]));
                                } else {
                                    $newQueryValue[$code] = $filterManager->translitUrl(htmlspecialchars_decode($label)) ?: $label;
                                }
                            } elseif ($class == 'Magento\CatalogSearch\Model\Layer\Filter\Category') {
                                $cat = $filterManager->translitUrl(htmlspecialchars_decode($filter->getLabel())) ?: $filter->getLabel();
                                $newQueryValue[$code] = $newQueryValue[$code] . '_' . $cat;
                            }
                        }
                    }

                    if (isset($newQueryValue['cat'])) {
                        if ($request->getParam('id') == $newQueryValue['cat']) {
                            $newQueryValue['cat'] = null;
                        }
                    }
                    $result['updated_url'] = $block->getUrl('*/*/*', [
                        '_current'      => true,
                        '_query'        => $newQueryValue,
                        '_use_rewrite'  => true,
                    ]);
                    $result['updated_url'] = str_replace('%2C', ',', $result['updated_url']);
                }
                $json = \Magento\Framework\App\ObjectManager::getInstance()->create('\Magento\Framework\Controller\Result\JsonFactory')->create();
                $json->setData($result);
                return $json;
            }
            return $page;
        }
    }
}

