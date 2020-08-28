<?php
 
namespace Silk\Catalog\Helper;
 
class Product extends \Magento\Catalog\Helper\Product
{    
    
    /**
     * Retrieve base image url
     *
     * @param ModelProduct|\Magento\Framework\DataObject $product
     * @return string|bool
     */
    public function getImageUrl($product)
    {
        $url = false;
        $attribute = $product->getResource()->getAttribute('image');
        if ($product->getImage()=='no_selection') {
            $url = $this->_assetRepo->getUrl('Magento_Catalog::images/product/placeholder/image.jpg');
            return $url;
        } 

        if (!$product->getImage()) {
            $url = $this->_assetRepo->getUrl('Magento_Catalog::images/product/placeholder/image.jpg');
        } elseif ($attribute) {
            $url = $attribute->getFrontend()->getUrl($product);
        }
        return $url;
    }

}
?>