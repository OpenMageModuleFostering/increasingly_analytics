<?php
/**
* Magento
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@magentocommerce.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade Magento to newer
* versions in the future. If you wish to customize Magento for your
* needs please refer to http://www.magentocommerce.com for more information.
*
* @category  Increasingly
* @package   Increasingly_Analytics
* @author    Increasingly Pvt Ltd
* @copyright Copyright (c) 2015-2016 Increasingly Ltd (http://www.increasingly.co)
* @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/
/**
* Formats product details to be sent to increasingly
*/
class Increasingly_Analytics_Helper_ProductFormatter extends Mage_Core_Helper_Abstract
{

  public function formatProductInfo($product) 
  {
    $productData = null;

    try 
    {
      $priceFormatter = Mage::helper('increasingly_analytics/PriceFormatter');
      $dateFormatter = Mage::helper('increasingly_analytics/DateFormatter');

      $productData = array(
        'product_id'      =>  $product->getId(),
        'product_sku'     =>  $product->getSku(),
        'product_name'    =>  $product->getName(),
        'categories'      =>  array(),
        //'currency'      =>  Mage::app()->getStore()->getDefaultCurrencyCode(),
        'product_price'   =>  $priceFormatter->format($product->getPrice()),
        'special_price'   =>  $priceFormatter->format($product->getSpecialPrice()),                
        'product_url'     =>  $product->getProductUrl(),
        'description'     =>  $product->getShortDescription(),
        'status'        =>  (int)$product->getStatus(),
        'product_type'  =>  $product->getTypeId(), 
        'created_at'    =>  $dateFormatter->getFormattedDate($product->getCreatedAt()),
        'updated_at'    =>  $dateFormatter->getFormattedDate($product->getUpdatedAt()) 
        );

        $productDefaultImage = $product->getData('image');
        if(!empty($productDefaultImage) && $productDefaultImage !== 'no_selection')
        {
          //$productData['image_url'] =  $product->getImageUrl();
           $productData['image_url'] =  Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getImage());
        }
        else
        {
          $productData['image_url'] = '';
        }
        $manufacturer = $product->getAttributeText('manufacturer');            
        if(strlen($manufacturer) > 0 && $manufacturer != false) 
        {
          $productData['manufacturer'] = $manufacturer;
        }

        $color =  $product->getAttributeText('color');
        if(strlen($color) > 0 && $color != false) 
        {
         $productData['color'] = $color;
       }

       $weight = $product->getWeight();
       if(strlen($weight) > 0 && $weight != false) 
       {
         $productData['weight'] = $weight;
       }

      $size = $product->getAttributeText('size');
      if(strlen($size) > 0 && $size != false) 
      {
        $productData['size'] = $size;
      }

      if($productData['product_type'] == "configurable") 
      {
        $configurableProducts = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($productData['product_id']);
        $configurable_items = array();
        foreach($configurableProducts as $key=>$configurableProductList) 
        {
          foreach($configurableProductList as $itemValue) 
          {
            $configurable_items[] = $itemValue;
          }
        }
        $productData['associated_products'] = $configurable_items;
      }

      if($productData['product_type'] == "grouped") 
      {

        $groupedProducts = Mage::getModel('catalog/product_type_grouped')->getChildrenIds($productData['product_id']);
        $grouped_items = array();

        foreach($groupedProducts as $key=>$groupedProductList) 
        {    		 
          foreach($groupedProductList as $itemValue) 
          {
            $grouped_items[] = $itemValue;
          }
        }
        $productData['associated_products'] = $grouped_items;
      }

      if($productData['product_type'] == "bundle")
      {
        $selectionCollection = $product->getTypeInstance(true)->getSelectionsCollection(
          $product->getTypeInstance(true)->getOptionsIds($product), $product);

        $bundled_items = array();
        foreach($selectionCollection as $option) 
        {
          $bundled_items[] = $option->getId();
        }
        $productData['associated_products'] = $bundled_items;
      }

      $relatedProducts = $product->getRelatedProductIds();
      if(count($relatedProducts) > 0)
      {
        $productData['related_products'] = $relatedProducts;
      }

      $upSellProducts = $product->getUpSellProductIds();
      if(count($upSellProducts) > 0)
      {
        $productData['up_sell_products'] = $upSellProducts;
      }

      $crossSellProducts = $product->getCrossSellProductIds();
      if(count($crossSellProducts) > 0)
      {
        $productData['cross_sell_products'] = $crossSellProducts;
      } 

      // get stock info
      $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
      $productData['qty'] = (int)$stock->getQty();

      $categories = $product->getCategoryCollection()
      ->addAttributeToSelect('id')
      ->addAttributeToSelect('name');

      foreach($categories as $category) 
      {
        $categoryInfo = array();
        $categoryInfo['id'] = $category->getId();
        $categoryInfo['name'] = $category->getName();
        $productData['categories'][] = $categoryInfo;
      }

      $otherImages = $product->getMediaGalleryImages();

      if($otherImages == '' || count($otherImages) == 0)
      {
        $productImage_Data = Mage::getModel('catalog/product')->load($product->getId());
        $otherImages = $productImage_Data->getMediaGalleryImages();
      }            

      if(count($otherImages) > 1)
      {
       foreach($otherImages as $img)
       {
         if($img->getFile() != $productDefaultImage)
         {
           $productData['other_image_list'][] = $img->getUrl();
         }
       }
      }
    } 
    catch(Exception $e)
    {
      Mage::log($e, null, 'Increasingly_Analytics.log');
    }

    return $productData;
  }  

}


