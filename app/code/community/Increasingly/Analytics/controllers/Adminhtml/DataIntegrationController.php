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
 * controller for sending data to increasingly
 */
class Increasingly_Analytics_Adminhtml_DataIntegrationController extends Mage_Adminhtml_Controller_Action
{
  /**
   * load increasingly config UI on menu click
   */
  public function indexAction()
  {    
      $this->loadLayout()->_setActiveMenu('increasingly');
      $this->renderLayout();	
  }
 
  public function saveConfigDetailsAction()
  {    
    $session = Mage::getSingleton('core/session');
    try 
    {
      
      $isIncreasinglyEnabled = $this->getRequest()->getPost('increasingly_analytics_settings_enable');
      $apiKey                = $this->getRequest()->getPost('increasingly_analytics_settings_api_key');
      $apiSecret             = $this->getRequest()->getPost('increasingly_analytics_settings_api_secret');

       if ($isIncreasinglyEnabled == 1 && (empty($apiKey) || empty($apiSecret))) 
       {
            Mage::throwException($this->__('Invalid form data. The api key and secret are missing!'));
       }
        
       $helper = Mage::helper('increasingly_analytics');

       $data = array('is_api_enabled' => (boolean)$isIncreasinglyEnabled);

       $result = $helper->increasinglyApi($data,'validate_api','track',$apiKey,$apiSecret);
       
       if($result != null && $result != '' && $result->status == 'success' && json_decode($result->data)->isValidUser == 'true')
       {
         $configDetails = Mage::getModel('core/config');
         $configDetails->saveConfig('increasingly_analytics/settings/enable', $isIncreasinglyEnabled, 'default', 0);
         $configDetails->saveConfig('increasingly_analytics/settings/api_key', $apiKey, 'default', 0);
         $configDetails->saveConfig('increasingly_analytics/settings/api_secret', $apiSecret, 'default', 0);
         Mage::app()->getCacheInstance()->cleanType('config');
         $configDetails=Mage::getModel('core/config');

         $data['store_name'] = Mage::app()->getStore()->getName();
         $data['store_url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
         $helper->increasinglyApi($data,'save_api_enable_status','track',$apiKey,$apiSecret);

         $session->addSuccess('The configuration has been saved.');
       }
       else
       {
         Mage::log('Invalid user - '. $result, null, 'Increasingly_Analytics.log');
         $session->addWarning('An error occurred while saving this configuration:');
       }

    }
    catch (Exception $e) 
    {
        Mage::log($e->getMessage(), null, 'Increasingly_Analytics.log');
        $session->addException($e,'An error occurred while saving this configuration:');
    }

    $this->_redirect('*/*/index');
  }
   
  /**
   * Import previous order data
   * @return void
   */
  public function importOrderAction()
  {
      
    try 
    {
      $helper = Mage::helper('increasingly_analytics');
      $import = Mage::getSingleton('increasingly_analytics/DataIntegration');
      $chunkId = (int)$this->getRequest()->getParam('chunk_id');
    
      $orders = $import->getOrderDetails($chunkId);
      $orderDetailsForImport = array();

      foreach ($orders as $order) 
      {
          if ($order->getId()) 
          {
            $orderDetails = $helper->buildOrderDetailsData($order);
            array_push($orderDetailsForImport, $orderDetails);
          }
       }
     
      $result = $helper->increasinglyApi($orderDetailsForImport,'order','import',$helper->getApiToken(),$helper->getApiSecret());
        
    } 
    catch (Exception $e) 
    {
      Mage::log("Import previous order data - " . $e->getMessage(), null, 'Increasingly_Analytics.log');
    }        
  }
}
