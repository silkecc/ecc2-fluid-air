<?php
/**
* Copyright © 2018 Codazon. All rights reserved.
* See COPYING.txt for license details.
*/

namespace Codazon\ProductLabel\Controller\Adminhtml;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Controller\ResultFactory;

class AbstractMassStatus extends \Magento\Backend\App\Action
{
	const ID_FIELD = 'entity_id';	
	
    const REDIRECT_URL = '*/*/';
	
    protected $collection = 'Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection';
	
    protected $model = 'Magento\Framework\Model\AbstractModel';
	
    protected $status = true;
    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Codazon_ProductLabel::save');
    }
    
	public function execute()
    {
        $selected = $this->getRequest()->getParam('selected');
        $excluded = $this->getRequest()->getParam('excluded');		
        try {
            if (isset($excluded)) {
                if (!empty($excluded)) {
					if(!is_array($excluded)){
						$excluded = [$excluded];
					}
                    $this->excludedSetStatus($excluded);
                } else {
                    $this->setStatusAll();
                }
            } elseif (!empty($selected)) {
				if(!is_array($selected)){
					$selected = [$selected];
				}
                $this->selectedSetStatus($selected);
            } else {
                $this->messageManager->addError(__('Please select item(s).'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath(static::REDIRECT_URL);
    }
	
    protected function setStatusAll()
    {
        /** @var AbstractCollection $collection */
        $collection = $this->_objectManager->get($this->collection);
        $this->setStatus($collection);
    }
	
    protected function excludedSetStatus(array $excluded)
    {
        /** @var AbstractCollection $collection */
        $collection = $this->_objectManager->get($this->collection);
        $collection->addFieldToFilter(static::ID_FIELD, ['nin' => $excluded]);
        $this->setStatus($collection);
    }
    
	protected function selectedSetStatus(array $selected)
    {
        /** @var AbstractCollection $collection */
        $collection = $this->_objectManager->get($this->collection);
        $collection->addFieldToFilter(static::ID_FIELD, ['in' => $selected]);
        $this->setStatus($collection);
    }
	
    protected function setStatus($collection)
    {
        foreach ($collection->getAllIds() as $id) {
            /** @var \Magento\Framework\Model\AbstractModel $model */
            $model = $this->_objectManager->get($this->model);
            $model->load($id);
            $model->setIsActive($this->status);
            $model->save();
        }
    }
}
