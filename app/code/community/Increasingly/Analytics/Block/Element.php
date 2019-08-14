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
 * Increasingly element block.
 * Used to render placeholder elements that display the product bundles.
 *
 * @method string getDivId() Return the id of the element (defined in layout).
 */
class Increasingly_Analytics_Block_Element extends Mage_Core_Block_Template
{
  /**
   * Default id assigned to the element if none is set in the layout xml.
   */
  const DEFAULT_ID = 'missingDivIdParameter';

  /**
   * Render HTML placeholder element for the product bundles if the
   * module is enabled for the current store.
   *
   * @return string
   */
  protected function _toHtml()
  {
    if (!Mage::helper('increasingly_analytics')->isEnabled()) {
        return '';
    }
    return parent::_toHtml();
  }

  /**
   * Return the id of the element. If none is defined in the layout xml,
   * then set a default one.
   *
   * @return string
   */
  public function getElementId()
  {
      $id = $this->getDivId();
      if ($id === null) {
          $id = self::DEFAULT_ID;
      }
      return $id;
  }
}
