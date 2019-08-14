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
* Add import model and view for enabling increasingly
*
* 
*/
class Increasingly_Analytics_Block_Adminhtml_DataIntegration extends Mage_Adminhtml_Block_Widget_Form_Container
{
  /**
   * Set template for the data import from Magento to Increasingly API
   */
  protected function _construct()
  {       
    parent::_construct();
    $this->setTemplate('increasingly/dataintegration.phtml');
  } 

  /**
   * Get import model instance
   *
   * @return Increasingly_Analytics_Model_Import
   */
  public function getImport()
  {  
    return Mage::getSingleton('increasingly_analytics/DataIntegration');
  }

  /**
   * @return Increasingly Analytics Installation Status
  */
  public function renderIncreasinglyEnableStatus($selected=0)
  {     
    $options=array();
    $options[]=array('label'=>'Yes','value'=>1);
    $options[]=array('label'=>'No','value'=>0);    
    
    $html=$this->_getSelectHtml('increasingly_analytics_settings_enable',$options,$selected,'select','increasingly_analytics_settings_enable');
    return $html;
  }

  /**
   * @return Increasingly Analytics Admin Settings Select widget
  */
  private function _getSelectHtml($name,$options,$default=0,$class='',$id='')
  {       
    $select='<select name="'.$name.'" id="'.$id.'" onchange=loadHtmlFields(this.value) class="'.$class.'">';
    foreach ($options as $option) 
    {
      $selected='';
      if($option['value']==$default)
      {
        $selected='selected';  
      }
      $select.='<option value="'.$option['value'].'" '.$selected.'>'.$option['label'].'</option>';
    }

    $select.='</select>';

    return $select;
  }

  /**
   * Render script if the module is enabled for the current store.
   *
   * @return string
   */
  protected function _toHtml()
  {
    $html = parent::_toHtml();
    return $html;
  }

  /**
  * Return element html
  *
  * @param  Varien_Data_Form_Element_Abstract $element
  * @return string
  */
  protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
  {
    return $this->_toHtml();
  }

  /**
   * Render Increasingly analytics store admin UX for setting the configurations.
   *
   * @return string
   */
  public function getHtmlFields($selected=0)
  {
    $htmlContent = '';
    $display = 'display:display';
    $importButtonClass = 'scalable';
    $isEnabledOrDisabled = 'enabled';

    if($selected == 0)
    {
      $display = 'display:none';
      $importButtonClass = 'disabled';
      $isEnabledOrDisabled = 'disabled';        
    }
    
    $htmlContent ='<tr id="row_increasingly_analytics_settings_api_key" style="'.$display.'">';
    $htmlContent = $htmlContent . '<td class="label"><label for="increasingly_analytics_settings_api_key"> API Key</label></td>';
    $htmlContent = $htmlContent . '<td class="value"><input id="increasingly_analytics_settings_api_key"';
    $htmlContent = $htmlContent . 'name="increasingly_analytics_settings_api_key"'; 
    $htmlContent = $htmlContent . 'value="'.Mage::getStoreConfig('increasingly_analytics/settings/api_key').'"';
    $htmlContent = $htmlContent . 'class="input-text" type="text"/></td></tr>';

    $htmlContent = $htmlContent . '<tr id="row_increasingly_analytics_settings_api_secret" style="'.$display.'">';
    $htmlContent = $htmlContent . '<td class="label"><label for="increasingly_analytics_settings_api_secret"> API Secret</label></td>';
    $htmlContent = $htmlContent . '<td class="value"><input id="increasingly_analytics_settings_api_secret"';
    $htmlContent = $htmlContent . 'name="increasingly_analytics_settings_api_secret"';
    $htmlContent = $htmlContent . 'value="'.Mage::getStoreConfig('increasingly_analytics/settings/api_secret').'"';
    $htmlContent = $htmlContent . 'class="input-text" type="text"/></td></tr>';

    $htmlContent = $htmlContent . '<tr id="row_increasingly_analytics_settings_import" style="'.$display.'">';
    $htmlContent = $htmlContent . '<td class="label"><label for="increasingly_analytics_settings_import"> Import orders</label></td>';
    $htmlContent = $htmlContent . '<td class="value"><div style="float: left;">';

    if($selected == 0)
    {
      $htmlContent = $htmlContent . '<p>Please enable the extension to import order data to increasingly by providing API key and secret received while registering to increasingly.</p>';
    }
    else
    {
      $htmlContent = $htmlContent . '<h3>Importing your historical data</h3><p>';
      $htmlContent = $htmlContent . 'Increasingly will consider previous order data for analysis to recommend product bundles.';
      $htmlContent = $htmlContent . '</p></div><div style="clear:both"></div>';
     
      $htmlContent = $htmlContent . '<button  id="increasingly_button" title="Import data" type="button" class="'.$importButtonClass.'"';
      $htmlContent = $htmlContent . 'onclick="javascript:import_increasingly(); return false;" '.$isEnabledOrDisabled.'>';
      $htmlContent = $htmlContent . '<span><span><span>Import data</span></span></span></button>';
    }

    $htmlContent = $htmlContent . '<div style="clear:both"></div><div id="increasingly_import_status"></div>';
    $htmlContent = $htmlContent . '<div style="clear:both"></div></td></tr>';

    $html=$htmlContent;

    return $html;

  }

  /**
  * Return Import action url for button
  * @return string
  */
  public function getOrderImportUrl()
  {   
    return Mage::helper('adminhtml')->getUrl("*/*/importOrder", array('isAsync'=> true));
  }
}
