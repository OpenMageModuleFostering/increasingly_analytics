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
 * Catch events and track them to increasingly api
 *
 */
class Increasingly_Analytics_Model_Observer
{
  /**
  * Identify customer after login
  *
  * @param  Varien_Event_Observer $observer
  * @return void
  */
  public function customerLogin(Varien_Event_Observer $observer)
  {        
    try
    {
      $helper = Mage::helper('increasingly_analytics');

  	  if ($helper->isEnabled())
  	  {
  	    $customer = $observer->getEvent()->getCustomer();
  	    $data = array(          
    		  'customer_email'      => $customer->getEmail(),
    		  'customer_first_name' => $customer->getFirstname(),         
    		  'customer_last_name'  => $customer->getLastname(),
    		  'customer_name'       => $customer->getName()         
    		  );
                
  	   $helper->addEvent('track', 'login',$data);
  	  }
    }
    catch(Exception $e)
    {
      Mage::log("Customer login tracking - " . $e->getMessage(), null, 'Increasingly_Analytics.log');
    }
  }

  /**
  * Track page views
  */
  public function trackPageView(Varien_Event_Observer $observer)
  {     
    try
    {
      $helper = Mage::helper('increasingly_analytics');
      $priceFormatter = Mage::helper('increasingly_analytics/PriceFormatter');

      if ($helper->isEnabled())
      {
        $action = (string)$observer->getEvent()->getAction()->getFullActionName();
        
        if ($this->_isRejected($action)) {
          return;
        }
        	
        // homepage page
        if ($action == 'cms_index_index' || $action == 'cms_page_view') {
          $title = Mage::getSingleton('cms/page')->getTitle();
                  $data = array('page_title' =>  $title);
          $helper->addEvent('track', 'home_page_visit', $data);
          return;
        }

        // category view page
    		if($action == 'catalog_category_view') {
  		    $category = Mage::registry('current_category');
  		    $data =  array(
  		        'category_id'    =>  $category->getId(),
  		        'category_name'  =>  $category->getName()
  		    );
  		    $helper->addEvent('track', 'category_page_visit', $data);
  		    return;
    		}
    		// product view page
    		if ($action == 'catalog_product_view') {
    	    $product = Mage::registry('current_product');
    	    $data =  array(
  	        'product_id'    => $product->getId(),
  	        'product_name'  => $product->getName(),
  	        'product_price' => $priceFormatter->format($product->getFinalPrice()),
  	        'product_url'   => $product->getProductUrl(),
            'product_sku'   => $product->getSku(),
    	    );
      	  
          if($product->getImage())
            $data['product_image_url'] = (string)Mage::helper('catalog/image')->init($product, 'image');

      	  if(count($product->getCategoryIds())) {
  	        $categories = array();
  	        $collection = $product->getCategoryCollection()->addAttributeToSelect('*');
  	        foreach ($collection as $category) {
  	            $categories[] = array(
  	                'category_id' => $category->getId(),
  	                'category_name' => $category->getName()
  	            );
  	        }
  	        $data['categories'] = $categories;
      	  }
    	    $helper->addEvent('track', 'product_page_visit', $data);
    	    return;
    		}
    	       
    		// Catalog search page
    		if ($action == 'catalogsearch_result_index') {
    	    $query = Mage::helper('catalogsearch')->getQuery();
    	    if ($text = $query->getQueryText()) {
  	        $resultCount = Mage::app()->getLayout()->getBlock('search.result')->getResultCount();
  	        $params = array(
  	            'query' => $text,
  	            'result_count' => $resultCount
  	        );
  	        $helper->addEvent('track', 'search_page', $params);
  	        return;
    	    }
    		}
      }
    }
    catch(Exception $e)
    {
      Mage::log("Page View tracking - " . $e->getMessage(), null, 'Increasingly_Analytics.log');
    }
  }


  /**
  * List of events that we don't want to track
  *
  * @param string event
  */
  private function _isRejected($event)
  {
    return in_array(
      $event,
      array('catalogsearch_advanced_result', 'catalogsearch_advanced_index')
    );
  }

  /**
  * Adding to cart
  * "checkout_cart_product_add_after"
  */
  public function addToCart(Varien_Event_Observer $observer)
  {    
    try
    {
      /**
       * @var Mage_Sales_Model_Quote_Item
       */
      $item = $observer->getQuoteItem();
      $product = $item->getProduct();
      $cartProduct = $observer->getProduct();      
      if ($cartProduct->isGrouped()) {
        $options = Mage::app()->getRequest()->getParam('super_group');                   
        if (is_array($options)) {
          foreach ($options as $productId => $qty) {
            $this->_addToCart((int)$productId, $cartProduct, (int)$qty);
          }
        }
      } elseif($cartProduct->isConfigurable()) { 
        $this->_addToCart($product->getId(), $cartProduct, (int)$cartProduct->getCartQty());
      } else { 
        $this->_addToCart($cartProduct->getId(), $cartProduct, (int)$cartProduct->getCartQty());
      }
    }
    catch(Exception $e)
    {
      Mage::log("Add to cart tracking - " . $e->getMessage(), null, 'Increasingly_Analytics.log');
    }
  }

  /**
  * Add to cart 
  */
  private function _addToCart($productId, $cartProduct, $qty) {

    $helper = Mage::helper('increasingly_analytics');
    $priceFormatter = Mage::helper('increasingly_analytics/PriceFormatter');
    $product = Mage::getModel('catalog/product')->load($productId);
    if ($helper->isEnabled()){
      $data =  array(
        'product_id'        => $cartProduct->getId(),
        'product_name'      => $cartProduct->getName(),
        'product_url'       => $cartProduct->getProductUrl(),
          'product_sku'       => $cartProduct->getSku(),
        'product_type'      => $cartProduct->getTypeId(),
        'qty'               => $qty,
        'product_price'     => $priceFormatter->format($cartProduct->getFinalPrice()) 
      );

      if ($cartProduct->isGrouped() || $cartProduct->isConfigurable()) {
        $product = Mage::getModel('catalog/product')->load($productId);

        $data['product_price'] 	 = $priceFormatter->format($product->getFinalPrice()); 
        $data['option_product_id']    = $product->getId();
        $data['option_product_sku']   = $product->getSku();
        $data['option_product_name']  = $product->getName();
        $data['option_product_price'] = $priceFormatter->format($product->getFinalPrice());
      }
      
    	if(Mage::getSingleton('customer/session')->isLoggedIn()) {
        $data['is_logged_in'] = true;
      }
      else {
        $data['is_logged_in'] = false;
      }
      $helper->addEvent('track', 'add_to_cart', $data);
    }

  }

  /**
  * Removing item from shopping cart
  *
  * @param  Varien_Event_Observer $observer
  * @return void
  */
  public function removeFromCart(Varien_Event_Observer $observer)
  {     
    try
    {  
      $helper = Mage::helper('increasingly_analytics');

      if ($helper->isEnabled())
      {
        $item = $observer->getQuoteItem();
        $product = $item->getProduct();

        $data = array(
          'product_id' => $product->getId()
        );

        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
          $data['is_logged_in'] = true;
        }
        else {
          $data['is_logged_in'] = false;
        }
        $helper->deleteBundleOnProductDeleteFromCart($product->getId());
        $helper->addEvent('track', 'remove_from_cart', $data);
      }
      
    }
    catch(Exception $e)
    {
      Mage::log("Remove from cart tracking - " . $e->getMessage(), null, 'Increasingly_Analytics.log');
    }       
  }

  /**
  * Empty cart handler to delete all products from bundle
  */
  public function empty_cart(Varien_Event_Observer $observer){
    $helper = Mage::helper('increasingly_analytics');

    $quote = $observer->getEvent()->getQuote();
    if($quote->getItemsCount() == 0) {
        $helper->deleteBundleOnEmptyCart($quote);
    }
  }
  /**
  * applyCartDiscount to shopping cart
  *
  * @param  Varien_Event_Observer $observer
  * @return void
  */
  public function applyCartDiscount(Varien_Event_Observer $observer)
  { 
    try
    {  
      $bundle_product_ids = [];
      $quote_product_ids = [];
      $cookieValue = Mage::getModel('core/cookie')->get('ivid');
      $userBundleCollection = Mage::getModel('increasingly_analytics/bundle')->getCollection()->addFieldToFilter('increasingly_visitor_id',$cookieValue);
      $items = $observer->getEvent()->getQuote()->getAllItems();
      $eligibleProducts = [];
      $discount = 0;
      foreach ($items as $item) {
        array_push($quote_product_ids, $item->getProductId());
      }
      foreach ($userBundleCollection as $bundle) {
        //First Bundle products
        $bundle_product_ids = explode(',', $bundle->getProductIds()); 
        $productsIds = array_intersect($quote_product_ids, $bundle_product_ids);
        if(count($productsIds) == count($bundle_product_ids) )
          $discount += $bundle->getDiscountPrice();
      }

      if($discount > 0){
        $quote=$observer->getEvent()->getQuote();
        $quoteid=$quote->getId();
        $discountAmount=$discount;
        if($quoteid) {    
          if($discountAmount>0) {
            $total=$quote->getBaseSubtotal();
            $quote->setSubtotal(0);
            $quote->setBaseSubtotal(0);

            $quote->setSubtotalWithDiscount(0);
            $quote->setBaseSubtotalWithDiscount(0);

            $quote->setGrandTotal(0);
            $quote->setBaseGrandTotal(0);
          

            $canAddItems = $quote->isVirtual()? ('billing') : ('shipping'); 
            foreach ($quote->getAllAddresses() as $address) {

              $address->setSubtotal(0);
              $address->setBaseSubtotal(0);

              $address->setGrandTotal(0);
              $address->setBaseGrandTotal(0);

              $address->collectTotals();

              $quote->setSubtotal((float) $quote->getSubtotal() + $address->getSubtotal());
              $quote->setBaseSubtotal((float) $quote->getBaseSubtotal() + $address->getBaseSubtotal());

              $quote->setSubtotalWithDiscount(
                  (float) $quote->getSubtotalWithDiscount() + $address->getSubtotalWithDiscount()
              );
              $quote->setBaseSubtotalWithDiscount(
                  (float) $quote->getBaseSubtotalWithDiscount() + $address->getBaseSubtotalWithDiscount()
              );

              $quote->setGrandTotal((float) $quote->getGrandTotal() + $address->getGrandTotal());
              $quote->setBaseGrandTotal((float) $quote->getBaseGrandTotal() + $address->getBaseGrandTotal());

              $quote ->save(); 

              $quote->setGrandTotal($quote->getBaseSubtotal()-$discountAmount)
              ->setBaseGrandTotal($quote->getBaseSubtotal()-$discountAmount)
              ->setSubtotalWithDiscount($quote->getBaseSubtotal()-$discountAmount)
              ->setBaseSubtotalWithDiscount($quote->getBaseSubtotal()-$discountAmount)
              ->save(); 


              if($address->getAddressType()==$canAddItems) {
              //echo $address->setDiscountAmount; exit;
               $address->setSubtotalWithDiscount((float) $address->getSubtotalWithDiscount()-$discountAmount);
               $address->setGrandTotal((float) $address->getGrandTotal()-$discountAmount);
               $address->setBaseSubtotalWithDiscount((float) $address->getBaseSubtotalWithDiscount()-$discountAmount);
               $address->setBaseGrandTotal((float) $address->getBaseGrandTotal()-$discountAmount);
               if($address->getDiscountDescription()){
               $address->setDiscountAmount(-($address->getDiscountAmount()-$discountAmount));
               $address->setDiscountDescription($address->getDiscountDescription().', Custom Discount');
               $address->setBaseDiscountAmount(-($address->getBaseDiscountAmount()-$discountAmount));
               }else {
               $address->setDiscountAmount(-($discountAmount));
               $address->setDiscountDescription('Custom Discount');
               $address->setBaseDiscountAmount(-($discountAmount));
               }
               $address->save();
              }//end: if
            } //end: foreach
            //echo $quote->getGrandTotal();

            foreach($quote->getAllItems() as $item){
              //We apply discount amount based on the ratio between the GrandTotal and the RowTotal
              $rat=$item->getPriceInclTax()/$total;
              $ratdisc=$discountAmount*$rat;
              $item->setDiscountAmount(($item->getDiscountAmount()+$ratdisc) * $item->getQty());
              $item->setBaseDiscountAmount(($item->getBaseDiscountAmount()+$ratdisc) * $item->getQty())->save();                      
            }
          }              
        }
      }
    }
    catch(Exception $e)
    {
      Mage::log("Remove from cart tracking - " . $e->getMessage(), null, 'Increasingly_Analytics.log');
    }

  }


/**
* Track new order 
*
*/
public function trackNewOrder(Varien_Event_Observer $observer)
{
  try 
  {
    
    $helper = Mage::helper('increasingly_analytics');
    
    if ($helper->isEnabled())
	  {
	    $data = array();
	    $order = $observer->getOrder();
	   
      if ($order->getId()) 
      {
	      $data = $helper->buildOrderDetailsData($order);
	      $helper->addEvent('track', 'order', $data);
	    }
    }
	} 
  catch(Exception $e)
  {
    Mage::log("New order tracking - " . $e->getMessage(), null, 'Increasingly_Analytics.log');
  }
}


  /**
  * Send order update information 
  *
  */
  public function updateOrder(Varien_Event_Observer $observer)
  {
    try 
    {
      $helper = Mage::helper('increasingly_analytics');

      if ($helper->isEnabled())
      {
        $order = $observer->getOrder();
        $orderDetails = $helper->buildOrderDetailsData($order);
        $helper->increasinglyApi($orderDetails,'order','track',$helper->getApiToken(),$helper->getApiSecret());
      }

    }
    catch(Exception $e)
    {
      Mage::log("Update order tracking - " . $e->getMessage(), null, 'Increasingly_Analytics.log');
    }
  }

  /**
  * Send product update information 
  *
  */
  public function productAddOrUpdate(Varien_Event_Observer $observer)
  {
    try 
    {
      $helper = Mage::helper('increasingly_analytics');

      if ($helper->isEnabled())
      {
        $productId = $observer->getEvent()->getProduct()->getId();
        $product = Mage::getModel('catalog/product')->load($productId);

        $productFormatHelper = Mage::helper('increasingly_analytics/ProductFormatter'); 
           
        $formattedProductInfo = $productFormatHelper->formatProductInfo($product);
        $helper->increasinglyApi($formattedProductInfo,'product_add_or_update','track',$helper->getApiToken(),$helper->getApiSecret());
      }

    }
    catch(Exception $e)
    {
      Mage::log("Product Add or Update tracking - " . $e->getMessage(), null, 'Increasingly_Analytics.log');
    }
  }

  /**
  * Send product delete information 
  *
  */
  public function productDelete(Varien_Event_Observer $observer)
  {
    try 
    {
      $helper = Mage::helper('increasingly_analytics');

      if ($helper->isEnabled()){
      $productId = $observer->getEvent()->getProduct()->getId();

      $productData = array('product_id' =>  $productId);
      $helper->deleteBundleOnProductDeleteFromCart($productId);
      $helper->increasinglyApi($productData,'product_delete','track',$helper->getApiToken(),$helper->getApiSecret());
      }

    }
    catch(Exception $e)
    {
      Mage::log("Product delete tracking - " . $e->getMessage(), null, 'Increasingly_Analytics.log');
    }
  }
}
