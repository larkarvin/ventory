<?php

namespace Backend\Model;
use Zend\Validator;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Input;

class PurchaseOrders
{

    use FetchTrait;

    private $_collectionName = 'purchase_orders';
    private $_collection = NULL;
    private $_variantModel = NULL;
    private $_sequenceModel = NULL;

    public function setVariantModel($variantModel)
    {
        $this->_variantModel = $variantModel;
    }

    public function setDbAdapter($dbAdapter)
    {
        $this->_collection = $dbAdapter->selectCollection($this->_collectionName);
    }

    public function setSequenceModel($sequenceModel)
    {
        $this->_sequenceModel = $sequenceModel;
    }

 
    public function purchaseOrder($order){
        unset($order['q']);

        $deliveryDate = strtotime($order['deliveryDate']);
        $salesOrder = [
                       '_id'           => $this->_sequenceModel->getNextSequence('salesorder'),
                       'created'       => new \MongoDate(),
                       'status'        => "new",
                       'delivered'     => FALSE,
                       'finalized'     => FALSE,
                       'supplier'      => $order['supplier'],
                       'note'          => $order['note'],
                       'deliveryDate'  => new \Mongodate($deliveryDate),
                       
        ];
        $total = 0;
        foreach($order['items'] as $key => $item){
            $salesOrder['items'][$key]['qty']      = (float) $item['qty'];
            $salesOrder['items'][$key]['cost']     = (float) $item['cost'];
            $salesOrder['items'][$key]['subtotal'] = (float) $item['subtotal'];
            $salesOrder['items'][$key]['sku']      = $item['sku'];
            $salesOrder['items'][$key]['id']       = $item['id'];
            $total += (float)$item['subtotal'];
            // $this->_variantModel->incrementVariantStock($item['id'], (float) $item['qty']);
        }
        $salesOrder['total'] = $total;
        $salesOrder['payment_left'] = $total;
        $result = $this->_collection->insert($salesOrder);
        if(!empty($result['err'])){
            throw new \Exception($result['err']);
        }
        return $salesOrder['_id'];
    }


    public function updatePurchaseOrder($orderId, $order){
        unset($order['q']);

        $salesOrder = ['updated'       => new \MongoDate(),
                       'supplier'      => $order['supplier'],
                       'deliveryDate'  => new \MongoDate(strtotime($order['deliveryDate'])),
                       'rider'         => isset($order['rider'])? $order['rider']: '',
                       'note'          => isset($order['note'])? $order['note']: '',
        ];
        $total = 0;
        foreach($order['items'] as $key => $item){
            $salesOrder['items'][$key]['qty']      = (float) $item['qty'];
            $salesOrder['items'][$key]['cost']    = (float) $item['cost'];
            $salesOrder['items'][$key]['subtotal'] = (float) $item['subtotal'];
            $salesOrder['items'][$key]['sku']      = $item['sku'];
            $salesOrder['items'][$key]['id']       = $item['id'];
            $total += (float)$item['subtotal'];
        }
        $salesOrder['total'] = $total;

        $criteria = ["_id" => (int) $orderId];
        $result = $this->_collection->update($criteria, ['$set' => $salesOrder]);
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