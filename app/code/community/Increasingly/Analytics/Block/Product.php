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
 * @author    Increasingly Ltd
 * @copyright Copyright (c) 2015-2016 Increasingly Ltd (http://www.increasingly.co)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Product tagging block.
 * Adds meta-data to the HTML document for the currently viewed product.
 *
 */
class Increasingly_Analytics_Block_Product extends Mage_Catalog_Block_Product_Abstract
{  
  /**
   * @var Increasingly_Analytics_Model_Meta_Product runtime cache for the product meta.
   */
  protected $_product;

  /**
   * @var string runtime cache for the current category path string.
   */
  protected $_currentCategory;

  /**
   * Render product info as hidden meta data if the module is enabled for the
   * current store.
   * If it is a "bundle" product with fixed price type, then do not render.
   * These are not supported due to their child products not having prices
   * available.
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
   * Returns the product meta data to tag.
   *
   * @return Increasingly_Analytics_Model_Meta_Product the meta data.
   */
  public function getMetaProduct()
  {
    if ($this->_product === null) {
      /** @var Increasingly_Analytics_Model_Meta_Product $model */
      $model = Mage::getModel('increasingly_analytics/meta_product');
      $model->loadData($this->getProduct());
      $this->_product = $model;
    }        
    return $this->_product;
  }

  /**
   * Returns the current category under which the product is viewed.
   *
   * @return string the category path or empty if not found.
   */
  public function getCurrentCategory()
  {
    if (!$this->_currentCategory) {
      $category = Mage::registry('current_category');
      $this->_currentCategory = Mage::helper('increasingly_analytics')
      ->buildCategoryString($category);
    }
    return $this->_currentCategory;
  }
}
