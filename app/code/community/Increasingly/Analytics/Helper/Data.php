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
 * Helper class provides api call functionality and building order details, track events etc.
 */
class Increasingly_Analytics_Helper_Data extends Mage_Core_Helper_Abstract
{   
  /**
   * Path to store config installation ID.
   */
  const XML_PATH_INSTALLATION_ID = 'increasingly_analytics/installation/id';

  /**
   * Path to store config Increasingly product image version.
   */
  const XML_PATH_IMAGE_VERSION = 'increasingly_analytics/image_options/image_version';

  /**
   * @var string the name of the cookie where the Increasingly ID can be found.
   */
  const COOKIE_NAME = '2c_cId';

  /**
   * @var string the name of the cookie where the Increasingly ID can be found.
   */
  const VISITOR_HASH_ALGO = 'sha256';
  /**
   * Get session instance
   *
   * @return Mage_Core_Model_Session
   */
  public function getSession()
  {
    return Mage::getSingleton('core/session');
  }

  /**
   * Check if increasingly module is enabled
   *
   * @return boolean
   */
  public function isEnabled()
  {
    return Mage::getStoreConfig('increasingly_analytics/settings/enable');
  }

  /**
   * Get API Token from configuration
   *
   * @return string
   */
  public function getApiToken()
  {
    return Mage::getStoreConfig('increasingly_analytics/settings/api_key');
  }

  /**
   * Get API Secret from configuration
   *
   * @return string
   */
  public function getApiSecret()
  {
    return Mage::getStoreConfig('increasingly_analytics/settings/api_secret');
  }

  /**
   * Add event for tracking
   *
   */
  public function addEvent($method, $type, $data)
  {        
    $event_data = array();

    if ($this->getSession()->getData(Increasingly_Analytics_Block_Track::DATA_KEY) != '') 
    {
        $event_data = (array)$this->getSession()->getData(Increasingly_Analytics_Block_Track::DATA_KEY);
    }
	
    $version = (string)Mage::getConfig()->getModuleConfig("Increasingly_Analytics")->version;
 	
    $currentEvent = array(
      'event_data' => $data,
      'event_type' => $type,
	    'method'     => $method,
      'platform'   => 'Magento ' . Mage::getEdition() . ' ' . Mage::getVersion(),
      'token'      => $this->getApiToken(),
      'version'    => $version
    );
      
    array_push($event_data, $currentEvent);
    $this->getSession()->setData(Increasingly_Analytics_Block_Track::DATA_KEY, $event_data);
  }			      

  /**
   * Get order details and sort them 
   * @param  Mage_Sales_Model_Order $order
   * @return array
   */
  public function buildOrderDetailsData($order)
  {   
    $priceFormatter = Mage::helper('increasingly_analytics/PriceFormatter');              
    $data = array(
        'order_id'            => $order->getIncrementId(),
        'order_status'        => $order->getStatus(),
        'order_amount'        => $priceFormatter->format($order->getGrandTotal()),
        'shipping_amount'     => $priceFormatter->format($order->getShippingAmount()),
        'tax_amount'          => $priceFormatter->format($order->getTaxAmount()),
        'items'               => array(),
        'shipping_method'     => $order->getShippingDescription(),
        'currency_code' 	  => $order->getOrderCurrencyCode(),
        'payment_method'      => $order->getPayment()->getMethodInstance()->getTitle()	    
    );

    if($order->getCustomerIsGuest()){
      $data['customer_email']        = $order->getCustomerEmail();
      $data['customer_first_name']   = $order->getCustomerFirstname();
      $data['customer_last_name']    = $order->getCustomerLastname();
      $data['customer_name']         = $order->getCustomerFirstname(). ' '. $order->getCustomerLastname();	       
    }
    else {
      $data['customer_email']        = $order->getCustomerEmail();
      $data['customer_first_name']   = $order->getBillingAddress()->getFirstname();
      $data['customer_last_name']    = $order->getBillingAddress()->getLastname();
      $data['customer_name']         = $order->getBillingAddress()->getName();     
    }
    if ($order->getDiscountAmount()) {
      $data['discount_amount'] = $priceFormatter->format($order->getDiscountAmount());
    }
    if ($order->getCouponCode()) {
      $data['coupons'] = $order->getCouponCode();
    }
    if($order->getRemoteIp()){
      $data['user_ip'] = $order->getRemoteIp();
    }
    if ($order->getCreatedAt()) {
      $data['order_time'] = $order->getCreatedAt();
    }
    foreach ($order->getAllItems() as $item) 
    {
      $dataItem = array(
        'product_id'    => $item->getProductId(),
        'product_price' => $priceFormatter->format($item->getPrice()) ? $priceFormatter->format($item->getPrice()) : 
		                            $priceFormatter->format($item->getProduct()->getFinalPrice()),
        'product_name'  => $item->getName(),
        'product_url'   => $item->getProduct()->getProductUrl(),
        'product_sku'   => $item->getSku(),
        'qty'  	   		  => (int)$item->getQtyOrdered(),
        'product_type'	=> $item->getProductType()
      );
      $data['items'][] = $dataItem;      
    }  

    $data['bundles'] = $this->formBundleJson();
    return $data;
  }

  /**Form json data for sending the bundle details to the Increasingly API
  **
  */
  public function formBundleJson(){
    try {
      $bundleData = [];
      $cookieValue = Mage::getModel('core/cookie')->get('ivid');  
      $userBundleCollection = Mage::getModel('increasingly_analytics/bundle')->getCollection()
        ->addFieldToFilter('increasingly_visitor_id',$cookieValue);
      foreach($userBundleCollection as $userBundle){
          $userBundle->getBundleId();
          $userBundle->getDiscountPrice();
          $userBundle->getTotalPrice();
          array_push($bundleData, 
            array('id' => $userBundle->getBundleId(),
              'discountPrice' => $userBundle->getDiscountPrice(),
              'totalPrice' => $userBundle->getTotalPrice()
            ));       
      }
    }
    catch (Exception $e) 
    {
      Mage::log("Increasingly form bundle json- " . $e->getMessage(), null, 'Increasingly_Analytics.log');
    }  
    return $bundleData;
  }

  /**Delete bundle when the products in the cart are deleted
  **
  */
  public function deleteBundleOnProductDeleteFromCart($product_id){
    try { 
      $cookieValue = Mage::getModel('core/cookie')->get('ivid'); 
      $userBundleCollection = Mage::getModel('increasingly_analytics/bundle')->getCollection()->addFieldToFilter('increasingly_visitor_id',$cookieValue);
      if(count($userBundleCollection) >= 1){
        $userBundle = Mage::getModel('increasingly_analytics/bundle');
        $userBundle->setId($userBundleCollection->getFirstItem()->getId())->delete();
        $userBundle->save($cookieValue);
      }
    } 
    catch (Exception $e) 
    {
      Mage::log("Increasingly delete bundle on delete from cart- " . $e->getMessage(), null, 'Increasingly_Analytics.log');
    }  

  }

  /**Delete bundle when all the items in the cart are deleted
  **
  */
  public function deleteBundleOnEmptyCart($quote){
    try { 
      $cookieValue = Mage::getModel('core/cookie')->get('ivid'); 
      $userBundleCollection = Mage::getModel('increasingly_analytics/bundle')->getCollection()->addFieldToFilter('increasingly_visitor_id',$cookieValue);
      $productIds = explode(',', $userBundle->getProductIds);
      if($quote->getItemsCount() == 0 && count($userBundleCollection) >= 0) {
         foreach ($userBundleCollection as $userBundle) {
           $userBundle->delete();
           
         }
      }
    } 
    catch (Exception $e) 
    {
      Mage::log("Increasingly delete bundle on emptyCart- " . $e->getMessage(), null, 'Increasingly_Analytics.log');
    }  
    
  }

  /**
   * Sends event data to increasingly through API
   */
  public function increasinglyApi($importData,$event_type,$method,$api_token,$api_secret)
  {    
    $result = '';
    try {         
      $version = (string)Mage::getConfig()->getModuleConfig("Increasingly_Analytics")->version;  	    
      $data = array(
        'event_data'    => $importData,
        'event_type'=> $event_type,
        'method'    => $method,
        'platform'  => 'Magento ' . Mage::getEdition() . ' ' . Mage::getVersion(),
        'token'     => $api_token,
        'version'   => $version
      );
      // sort data
      ksort($data);     
      $encodedData = base64_encode(Mage::helper('core')->jsonEncode($data));
      $signature = md5($encodedData.$api_secret);
      $url = 'http://optimizedby.increasingly.co/ImportData';
      $client = new Varien_Http_Client($url);
       
      $postData = array(
        'signature'   => $signature,
        'eventData'  => $encodedData
      );      
      $jsonData = json_encode($postData);        
      $client->setRawData($jsonData, 'application/json');
      $response = $client->request('POST');      
      $result = json_decode($response->getBody());    
      if ($response->isError()) {
          Mage::log($response->getBody(), null, 'Increasingly_Analytics.log');
      }  

    } 
    catch (Exception $e) 
    {
      Mage::log("Increasingly api call- " . $e->getMessage(), null, 'Increasingly_Analytics.log');
    }    
    return $result;
  }

  /**
   * Builds a tagging string of the given category including all its parent
   * categories.
   * The categories are sorted by their position in the category tree path.
   *
   * @param Mage_Catalog_Model_Category $category the category model.
   *
   * @return string
   */
  public function buildCategoryString($category)
  {
    $data = array();        
    if ($category instanceof Mage_Catalog_Model_Category) {
      /** @var $categories Mage_Catalog_Model_Category[] */
      $categories = $category->getParentCategories();
      $path = $category->getPathInStore();
      $ids = array_reverse(explode(',', $path));
      foreach ($ids as $id) {
        if (isset($categories[$id]) && $categories[$id]->getName()) {
          $data[] = $categories[$id]->getId();
        }
      }
    }    
    if (!empty($data)) {
      return DS . implode(DS, $data);
    } else {
      return '';
    }
  }

  /**
   * Return the product image version to include in product tagging.
   *
   * @param Mage_Core_Model_Store|null $store the store model or null.
   *
   * @return string
   */
  public function getProductImageVersion($store = null)
  {
    return Mage::getStoreConfig(self::XML_PATH_IMAGE_VERSION, $store);
  }

   
}
