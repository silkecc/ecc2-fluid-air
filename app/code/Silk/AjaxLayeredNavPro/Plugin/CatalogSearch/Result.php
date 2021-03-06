<?php

/**
 * Copyright © 2017 Codazon. All rights reserved.
 * See COPYING.txt for license details.
 */


namespace Silk\AjaxLayeredNavPro\Plugin\CatalogSearch;

class Result
{
    protected $helper;

    public function __construct(
        \Codazon\AjaxLayeredNavPro\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    public function afterExecute(\Epicor\Comm\Controller\Category\Search\Result $controller, $result)
    {
        // file_put_contents(BP . "/var/log/lazyload.log", "cfterfdsfasatalog search\n", FILE_APPEND);
        if ($controller->getRequest()->getParam('ajax_nav') && !$controller->getRequest()->getParam('isStockUpdate')) {
            $request = $controller->getRequest();
            $layout =  $this->helper->getLayout();
            $result = [];
            if ($block = $layout->getBlock('search.result')) {
                $result['category_products'] = rawurlencode($block->toHtml());
            }
            if ($block = $layout->getBlock('catalogsearch.leftnav')) {
                $result['catalog_leftnav'] = $block->toHtml();
            }
            if ($block = $layout->getBlock('page.main.title')) {
                $result['page_main_title'] = $block->toHtml();
            }
            $filterManager = $this->helper->getFilterManager();
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
        } else {
            return $result;
        }
    }
}
