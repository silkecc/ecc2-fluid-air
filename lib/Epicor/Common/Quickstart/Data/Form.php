<?php
namespace Epicor\Common\Quickstart\Data;

/**
 * Data form
 * This extends varien_data_form to include the attribute autocomplete in the getHtmlAttributes 
 * 
 * @category   Epicor
 * @package    Epicor_Common_Quickstart_Data_Form
 * @author     Sean Flynn
 */
class Form extends \Magento\Backend\Block\Widget\Form
{

    /**
     * Return allowed HTML form attributes
     * @return array
     */
    public function getHtmlAttributes()
    {
        return array('id', 'name', 'method', 'action', 'enctype', 'class', 'onsubmit', 'autocomplete');
    }   
}

