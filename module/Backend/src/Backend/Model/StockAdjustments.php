<?php

namespace Backend\Model;
use Zend\Validator;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Input;

class StockAdjustments
{

    use FetchTrait;

    private $_collectionName = 'stock_adjustments';
    private $_collection = NULL;
    private $_variantModel = NULL;
    private $_sequenceModel = NULL;


    public function setDbAdapter($dbAdapter)
    {
        $this->_collection = $dbAdapter->selectCollection($this->_collectionName);
    }

    public function setVariantModel($variantModel)
    {
        $this->_variantModel = $variantModel;
    }

    public function setSequenceModel($sequenceModel)
    {
        $this->_sequenceModel = $sequenceModel;
    }

 
    public function adjustInventory($adjustment){
        unset($adjustment['q']);

        $date = strtotime($adjustment['dateAdjusted']);
        $adjustmentOrder = [
                            '_id'            => $this->_sequenceModel->getNextSequence('stockadjustment'),
                            'created'        => new \MongoDate(),
                            'status'         => "new",
                            'reason'         => $adjustment['reason'],
                            'date_adjusted'  => new \Mongodate($date),
                            'note'           => $adjustment['note'],
                       
        ];
        $totalUnits = 0;
        foreach($adjustment['items'] as $key => $item){
            $adjustmentOrder['items'][$key]['qty']        = $item['qty'];
            $adjustmentOrder['items'][$key]['sku']        = $item['sku'];
            $adjustmentOrder['items'][$key]['id']         = $item['id'];
            $adjustmentOrder['items'][$key]['from_stock'] = $item['from_stock'];
            $adjustmentOrder['items'][$key]['to_stock']   = $item['to_stock'];
            $totalUnits += $item['qty'];
            $this->_variantModel->incrementVariantStock($item['id'], (float) $item['qty']);
        }

        $adjustmentOrder['total_units'] = $totalUnits;
        $result = $this->_collection->insert($adjustmentOrder);

        if(!empty($result['err'])){
            throw new \Exception($result['err']);
        }

        return $adjustmentOrder['_id'];
    }


    public function updatePurchaseOrder($adjustmentId, $adjustment){
        unset($adjustment['q']);

        $adjustmentOrder = ['updated'       => new \MongoDate(),
                       'supplier'      => $adjustment['supplier'],
                       'deliveryDate'  => new \MongoDate(strtotime($adjustment['deliveryDate'])),
                       'rider'         => isset($adjustment['rider'])? $adjustment['rider']: '',
                       'note'          => isset($adjustment['note'])? $adjustment['note']: '',
        ];
        $total = 0;
        foreach($adjustment['items'] as $key => $item){
            $adjustmentOrder['items'][$key]['qty']      = (float) $item['qty'];
            $adjustmentOrder['items'][$key]['cost']    = (float) $item['cost'];
            $adjustmentOrder['items'][$key]['subtotal'] = (float) $item['subtotal'];
            $adjustmentOrder['items'][$key]['sku']      = $item['sku'];
            $adjustmentOrder['items'][$key]['id']       = $item['id'];
            $total += (float)$item['subtotal'];
        }
        $adjustmentOrder['total'] = $total;

        $criteria = ["_id" => (int) $adjustmentId];
        $result = $this->_collection->update($criteria, ['$set' => $adjustmentOrder]);
        if(!empty($result['err'])){
            throw new \Exception($result['err']);
        }
        return TRUE;
    }

    public function markAsPaid($id)
    {
        $criteria = ['_id' => (int) $id];

        $updateDate = ['$set' => ['paid' => TRUE, 'paid_date' => new \MongoDate()]];
        return $this->_collection->update($criteria, $updateDate);
    }

    public function cancelOrder($id)
    {
        $criteria = ['_id' => (int) $id];

        $updateDate = ['$set' => ['status' => 'cancelled']];
        return $this->_collection->update($criteria, $updateDate);
    }


    public function finalize($id)
    {
        $criteria = ['_id' => (int) $id];

        $updateDate = ['$set' => ['finalized' => TRUE, 'finalized_date' => new \MongoDate()]];
        return $this->_collection->update($criteria, $updateDate);
    }

    public function markAsDelivered($id)
    {

        $criteria = ['_id' => (int) $id];

        $data = $this->_collection->findOne($criteria);

        foreach($data['items'] as $item)
        {
           $this->_variantModel->incrementVariantStock($item['id'], (float) $item['qty']); 
        }

        $updateDate = ['$set' => ['shipped' => TRUE,
                                  'delivery_date' => new \MongoDate(),
                                  'status' => 'closed'
                                  ]];
        return $this->_collection->update($criteria, $updateDate);
    }


}