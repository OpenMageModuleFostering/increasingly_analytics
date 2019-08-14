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
 * Model which gets previous orders details from Magento for exporting it to Increasingly
 *
 */
class Increasingly_Analytics_Model_DataIntegration extends Mage_Core_Model_Abstract
{
  private $_totalOrders = 0;
  private $_totalDataChunks = 0;
  private $_chunkItems = 200;
  
  public function _construct()
  {         
    $this->_totalOrders = Mage::getModel('sales/order')->getCollection()->getSize();
    $this->_totalDataChunks = (int)ceil($this->_totalOrders / $this->_chunkItems);
  }

  /**
   * Get order details
   *
   * @param  int
   * @return Varien_Data_Collection
   */
  public function getOrderDetails($chunkId)
  {          
    return Mage::getModel('sales/order')
      ->getCollection()
      ->setPageSize($this->_chunkItems)
      ->setCurPage($chunkId + 1);
  }

  /**
   * Total data chunk size
   * @return int
   */
  public function getChunks()
  {  
    return $this->_totalDataChunks;
  }
}
