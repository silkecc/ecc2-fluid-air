<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * CatalogRule data helper
 */
namespace Codazon\Shopbybrandpro\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_objectManager;
    protected $_scopeConfig;
    protected $_urlBuilder;
    protected $_imageHelper;
    protected $_brandFactory;
    protected $_storeManager;
    protected $_attributeCode;
    
    protected $_brandProducts = [];
    protected $_brandProductCount = [];
    protected $_blockFilter;
    
	public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Codazon\Shopbybrandpro\Helper\Image $imageHelper,
        \Codazon\Shopbybrandpro\Model\BrandFactory $brandFactory,
	    \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Product $productFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attributFactory,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        parent::__construct($context);
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->_imageHelper = $imageHelper;
        $this->_brandFactory = $brandFactory;
        $this->_storeManager = $storeManager;
	$this->_attributeCode = $this->_scopeConfig->getValue('codazon_shopbybrand/general/attribute_code');
	$this->eavConfig = $eavConfig;
        $this->attributFactory = $attributFactory;
        $this->_productCollectionFactory = $productFactory;
    }
    
    public function getStoreBrandCode() 
    {
        return $this->_attributeCode;
    }
    
    public function getUrl($path, $params = [])
    {
        return $this->_urlBuilder->getUrl($path, $params);
    }
    
    public function getBrandImage($brand, $type = 'brand_thumbnail', $options)
    {
        $brandThumb = $brand->getData($type);
        if ($type == 'brand_thumbnail') {
            if (!$brandThumb) {
                $brandThumb = 'codazon/brand/placeholder_thumbnail.jpg';
            }
        }
		if ($brandThumb) {
			if (isset($options['width']) || isset($options['height'])) {
				if(!isset($options['width'])) {
					$options['width'] = null;
                }
				if(!isset($options['height'])) {
					$options['height'] = null;
                }
				return $this->_imageHelper->init($brandThumb)
                    ->resize($options['width'], $options['height'])->__toString();
			} else {
				return $this->_mediaUrl.$brand->getData($type);
			}
		}else{
			return '';
		}
	}
    
    public function getBrandPageUrl($brandModel)
    {
		if ($brandModel->getData('brand_url_key')) {
            return $this->getUrl('brand', ['_nosid' => true]) . $brandModel->getData('brand_url_key');
        } else {
            return $this->getUrl('brand', ['_nosid' => true]) . urlencode(str_replace([' ',"'"],['-','-'],strtolower(trim($brandModel->getData('brand_label')))));
        }
	}
    
    public function getProductCount($attributeCode, $optionId)
    {
        $key = $attributeCode.'_'.$optionId;
        if (!isset($this->_brandProductCount[$key])) {
            $collection = $this->_objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory')->create();
            $collection->addAttributeToSelect([$attributeCode]);
            $collection->addAttributeToFilter($attributeCode, $optionId);
            $this->_brandProductCount[$key] = $collection->count();
        }
        return $this->_brandProductCount[$key];
    }
    
    public function getBrandByProduct($product, $attributeCode)
    {
        $attrValue = (int)$product->getData($attributeCode);
        
        if (!isset($this->_brandProducts[$attrValue])) {
            $brandModel = $this->_brandFactory->create()->setStoreId($this->_storeManager->getStore()->getId())
                ->setOptionId($attrValue)
                ->load(null);
            $brandModel->setUrl($this->getBrandPageUrl($brandModel));
            $brandModel->setThumbnail($this->getBrandImage($brandModel, 'brand_thumbnail', ['width' => 150]));
            $this->_brandProducts[$attrValue] = $brandModel;
        }
		return $this->_brandProducts[$attrValue];
	}
    
    public function getBlockFilter()
    {
        if ($this->_blockFilter === null) {
            $this->_blockFilter = $this->_objectManager->get('Magento\Cms\Model\Template\FilterProvider')->getBlockFilter();
        }
        return $this->_blockFilter;
    }
    
    public function htmlFilter($content)
    {
        return $this->getBlockFilter()->filter($content);
    }

    public function getProductCountByAttributeCode($attributeCode)
    {
        $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeCode);
        $visibilityAttribute = $this->eavConfig->getAttribute('catalog_product', 'visibility');
        $statusAttribute = $this->eavConfig->getAttribute('catalog_product', 'status');
        $currentStoreId = $this->_storeManager->getStore()->getId();
        $attributFactory = $this->attributFactory->create()->setStoreFilter(0, false);
        $itemCollection = $this->_productCollectionFactory;
        $attributFactory->getSelect()
            ->joinLeft(
                array('value_table' => $itemCollection->getTable('catalog_product_entity_int')),
                'main_table.option_id=value_table.value AND main_table.attribute_id=value_table.attribute_id and value_table.store_id = 0', 'entity_id'
            )
            ->joinLeft(
                array(
                    'visibility_admin_store_table' => $itemCollection->getTable('catalog_product_entity_int')
                ),
                'value_table.entity_id = visibility_admin_store_table.entity_id AND ' .
                'visibility_admin_store_table.attribute_id = ' . $visibilityAttribute->getId() . " AND " .
                'visibility_admin_store_table.store_id = 0' ,
                array(
                    "visibility_admin" => 'visibility_admin_store_table.value'
                )
            )
            ->joinLeft(
                array(
                    'visibility_current_store_table' => $itemCollection->getTable('catalog_product_entity_int')
                ),
                'value_table.entity_id = visibility_current_store_table.entity_id AND ' .
                'visibility_current_store_table.attribute_id = ' . $visibilityAttribute->getId() . " AND " .
                'visibility_current_store_table.store_id = ' . $currentStoreId,
                array(
                    "visibility_current" => 'visibility_current_store_table.value'
                )
            )
            ->joinLeft(
                array(
                    'status_admin_store_table' => $itemCollection->getTable('catalog_product_entity_int')
                ),
                'value_table.entity_id = status_admin_store_table.entity_id AND ' .
                'status_admin_store_table.attribute_id = ' . $statusAttribute->getId() . " AND " .
                'status_admin_store_table.store_id = 0',
                array(
                    "status_admin" => 'status_admin_store_table.value'
                )
            )
            ->joinLeft(
                array(
                    'status_current_store_table' => $itemCollection->getTable('catalog_product_entity_int')
                ),
                'value_table.entity_id = status_current_store_table.entity_id AND ' .
                'status_current_store_table.attribute_id = ' . $statusAttribute->getId() . " AND " .
                'status_current_store_table.store_id = ' . $currentStoreId,
                array(
                    "status_current" => 'status_current_store_table.value'
                )
            )
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(array('main_table.option_id',new \Zend_Db_Expr('COUNT(value_table.entity_id)')))
            ->where('main_table.attribute_id=:attribute_id')
            ->where('IFNULL(visibility_current_store_table.value,visibility_admin_store_table.value) in (2,4)')
            ->where('IFNULL(status_current_store_table.value,status_admin_store_table.value) in (1)')
            ->group('main_table.option_id');
        $result = $itemCollection->getConnection()->fetchPairs(
            $attributFactory->getSelect(),
            array('attribute_id' => $attribute->getId())
        );
        return $result;
    }
}
