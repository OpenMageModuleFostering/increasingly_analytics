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
* Tracks events to send it to increasingly
*/
class Increasingly_Analytics_Block_Track extends Mage_Core_Block_Template
{
    /**
     * key in session storage
     */
    const DATA_KEY = "increasingly_events";

    /**
     * Get tracking events to track them to increasingly api
     * @return array
     */
    public function getTrackingEvents()
    {     
      $helper = Mage::helper('increasingly_analytics');
      $events = (array)Mage::getSingleton('core/session')->getData(self::DATA_KEY);
      
        // clear data from session 
      Mage::getSingleton('core/session')->setData(self::DATA_KEY,'');
      return array_filter($events);
    }

    /**
    * Render category string as hidden meta data if the module is enabled for
    * the current store.
    *
    * @return string
    */
    protected function _toHtml()
    {      
      $html = parent::_toHtml();
      if(Mage::helper('increasingly_analytics')->isEnabled())
        return $html;
    }
  }
