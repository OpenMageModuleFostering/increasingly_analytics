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
* Returns product details to increasingly on API call
*/

require_once 'Mage/Checkout/controllers/CartController.php';
class Increasingly_Analytics_AddbundletocartController extends Mage_Core_Controller_Front_Action 
{
  public $cart;
  /***Index cart for add to cart
  *
  */
  public function indexAction ()
  { 
    try
    {
      $this->cart = Mage::getSingleton('checkout/cart');
      $data = $_POST["jsonData"];
      $data = json_decode($data,TRUE);
      $current_page = $data[0]["current_page"];
      $product = Mage::getModel('catalog/product');
      $inStockProducts = [];
      $outOfStockProducts = [];
      $productErrorStr = "";
      $productSuccessStr = "";
      if($current_page == "checkout_cart_index"){
        $quote = Mage::getModel('checkout/cart')->getQuote();

        //Get all Cart product Ids
        $cartProductIds =[];
        foreach ($quote->getAllItems() as $item) { 
          array_push($cartProductIds, $item->getProduct()->getId());
        }

        //Get all bundle Product Ids
        $previousCartProducts = [];
        for ($x = 0; $x < count($data[0]["params"]); $x++) {
          array_push($previousCartProducts,trim($data[0]["params"][$x]["product_id"]));
        } 

        //Get all Bundle product Ids that are in cart
        $result = array_intersect($cartProductIds, $previousCartProducts);
        $productsToAddToCart = array_diff($previousCartProducts,$result);

        //All bundle products are in cart,add all
        if(count($result) == count($cartProductIds) && count($result) == count($previousCartProducts) && count($cartProductIds) == count($previousCartProducts)){
           $this->addToCart($data);
        }else{

          //Not all bundled products are in the cart,add only products from the bundle to cart that doesn't exists in cart and dont add bundles to the database
          if(count($productsToAddToCart) >=  1){ 

            //Push the products into instock and out of stock arrays to handle the success and error messages at cart page
            foreach ($productsToAddToCart as $productId) {      
              $product = Mage::getModel('catalog/product');       
              $product->load($productId);

              //In Stock or Out of stock status of the product
              $inStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getIsInStock();
              if($inStock){

                //Push all the instock products to the inStockProducts array
                array_push($inStockProducts, $productId);
              }
              else{
                
                //Push all the out of stock products to the outOfStockProducts array
                array_push($outOfStockProducts, $productId);
                //Prepare the error message for the products out of stock
                $productErrorStr .= $product->getName().", ";
              }          
            }
            //Add all the instock products to the cart and prepare the success message
            foreach ($inStockProducts as $product_id) {
              $this->addTo($product_id,$data);         
              $productSuccessStr .= $product->getName().", ";
            }
            //Trim the success message
            $productSuccessStr = rtrim(trim($productSuccessStr),',');
            //Trim the error message
            $productErrorStr = rtrim(trim($productErrorStr),',');    
            //Save the cart        
            $this->cart->save();
            //Get teh quote
            $quote = Mage::getModel('checkout/cart')->getQuote();
            //Add success message
            if($productSuccessStr != "")
              Mage::getSingleton('core/session')->addSuccess($productSuccessStr.' added to your shopping cart');
            //Add error message
            if($productErrorStr != "")
              Mage::getSingleton('core/session')->addNotice($productErrorStr.' is out of stock');
            //Set cart was updated
            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
            //Redirect to cart
            $this->_redirect('checkout/cart'); 
          }
          if(count($productsToAddToCart)==0){
            //Add all bundled products to the cart when the cart is empty from the cart page and add bundles
            $this->addToCart($data);
          }
        }
       
      }else{
        //Add to bundle from all other pages except cart
        $this->addToCart($data);
      }
    }
    catch(Exception $e)
    {       
      $this->_redirect('checkout/cart');
    }   

    
  }
  /**Adds the product to the cart on click of Add to Basket button
  *
  */
  public function addTo($productId,$data){
    try{
      if($data[0]["bundle_id"] != null || $data[0]["bundle_id"] || ""){
        $bundle_id = $data[0]["bundle_id"];
        $productIds = [];
        $discountPrice = $data[0]["discountPrice"];
        $totalPrice = $data[0]["totalPrice"];
        $product = Mage::getModel('catalog/product');
        $product->load($productId);
        $this->cart->addProduct($product,array('qty' => 1));
      }
    }
    catch(Exception $e)
    {
      Mage::log("Increasingly AddTo cart controller - " . $e->getMessage(), null, 'Increasingly_Analytics.log');
    }   
  }

  /**Adds all the bundled products to the cart from all pages
  *
  */
  public function addToCart($data){
    try{
      $this->cart= Mage::getSingleton('checkout/cart');
      
      if($data[0]["bundle_id"] != null || $data[0]["bundle_id"] || ""){
        $bundle_id = $data[0]["bundle_id"];
        $productIds = [];
        $discountPrice = $data[0]["discountPrice"];
        $totalPrice = $data[0]["totalPrice"];
        $inStockProducts = [];
        $outOfStockProducts = [];
        $productErrorStr = "";
        $productSuccessStr = "";
        for ($x = 0; $x < count($data[0]["params"]); $x++) {
          $productIds[$x] = trim($data[0]["params"][$x]["product_id"]);
        } 
        //Push the products into instock and out of stock arrays to handle the success and error messages at cart page
        foreach ($productIds as $productId) { 
          $product = Mage::getModel('catalog/product');         
          $product->load($productId);
          $inStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getIsInStock();
          if($inStock){
            array_push($inStockProducts, $productId);
          }
          else{
            array_push($outOfStockProducts, $productId);
            //Prepare the error message for the products out of stock
            $productErrorStr .= $product->getName().", ";
          }          
        }
        //Add all the instock products to the cart and prepare the success message
        foreach ($inStockProducts as $product_id) {
          $product = Mage::getModel('catalog/product');
          $product->load($product_id);
          $this->cart->addProduct($product,array('qty' => 1));
          $productSuccessStr .= $product->getName().", ";
        }
        //Trim the success message
        $productSuccessStr = rtrim(trim($productSuccessStr),',');
        //Trim the error message
        $productErrorStr = rtrim(trim($productErrorStr),',');
        //Save all the products added to the cart
        $this->cart->save();
        //Add the bundles to the database if all the products are in stock
        if(count($outOfStockProducts) == 0){
          $productIdsStr = implode(',',$productIds);
          $cookieValue = Mage::getModel('core/cookie')->get('ivid');   
          $userBundleCollection = Mage::getModel('increasingly_analytics/bundle')->getCollection()
            ->addFieldToFilter('bundle_id', $bundle_id)
            ->addFieldToFilter('increasingly_visitor_id',$cookieValue);
          
          //Check if bundle already exists,add if not already present
          if(count($userBundleCollection) < 1){
            $userBundle = Mage::getModel('increasingly_analytics/bundle');
            $userBundle->setBundleId(intval($bundle_id));
            $userBundle->setProductIds($productIdsStr);
            $userBundle->setIncreasinglyVisitorId($cookieValue);
            $userBundle->setDiscountPrice($discountPrice);
            $userBundle->setTotalPrice($totalPrice);
            $userBundle->save();
          }
          //Set cart was updated flag
          Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
          
        }
        if($productSuccessStr != "")
          Mage::getSingleton('core/session')->addSuccess($productSuccessStr.' added to your shopping cart');
        //Error message for the out of stock products
        if($productErrorStr != "")
          Mage::getSingleton('core/session')->addNotice($productErrorStr.' is out of stock');
        //Redirect to the cart
        $this->_redirect('checkout/cart');
        
      }
    }
    catch(Exception $e)
    {
      Mage::log("Increasingly addToCart controller - " . $e->getMessage(), null, 'Increasingly_Analytics.log');
    }  

  }
}
