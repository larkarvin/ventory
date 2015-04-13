<?php

namespace Backend\Model;
use Zend\Validator;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Input;

class SalesOrders
{

    private $_collectionName = 'sales_orders';
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

    public function fetchOne(Array $criteria, $options = [])
    {

        $cursor = $this->_collection->findOne($criteria, $options);

        return $cursor;

    }

    public function salesOrder($order){
        unset($order['q']);

        $salesOrder = ['created'       => new \MongoDate(),
                       '_id'           => $this->_sequenceModel->getNextSequence('salesorder'),
                       'shipment_date' => new \MongoDate(strtotime($order['shipment_date'])),
                       'paid'          => FALSE,
                       'shipped'       => FALSE,
                       'customer'      => $order['customer'],
                       'deliveryAddr'  => $order['deliveryAddr'],
                       'shipmentDate'  => $order['shipmentDate'],
                       'deliveryTime'  => $order['deliveryTime'],
                       'phone'         => $order['phone'],
                       'seller'        => $order['seller'],
                       'deliveryType'  => $order['deliveryType'],
                       'paymentType'   => $order['paymentType'],
                       'rider'         => isset($order['rider'])? $order['rider']: '',
                       'note'          => isset($order['note'])? $order['note']: '',
        ];
        $total = 0;
        foreach($order['items'] as $key => $item){
            $salesOrder['items'][$key]['qty']      = (float) $item['qty'];
            $salesOrder['items'][$key]['price']    = (float) $item['price'];
            $salesOrder['items'][$key]['discount'] = (float) $item['discount'];
            $salesOrder['items'][$key]['subtotal'] = (float) $item['subtotal'];
            $salesOrder['items'][$key]['sku']      = $item['sku'];
            $salesOrder['items'][$key]['id']       = $item['id'];
            $total += (float)$item['subtotal'];
            // $this->_variantModel->decrementVariantStock($item['id'], (float) $item['qty']);
        }
        $salesOrder['total'] = $total;
        $result = $this->_collection->insert($salesOrder);
        if(!empty($result['err'])){
            throw new \Exception($result['err']);
        }
        return $salesOrder['_id'];
    }

    public function markAsPaid($id)
    {
        $criteria = ['_id' => (int) $id];

        $updateDate = ['$set' => ['paid' => TRUE, 'paid_date' => new \MongoDate()]];
        return $this->_collection->update($criteria, $updateDate);
    }

    public function markAsDelivered($id)
    {

        $criteria = ['_id' => (int) $id];

        $data = $this->_collection->findOne($criteria);

        foreach($data['items'] as $item)
        {
           $this->_variantModel->decrementVariantStock($item['id'], (float) $item['qty']); 
        }

        $updateDate = ['$set' => ['shipped' => TRUE, 'shipped_date' => new \MongoDate()]];
        return $this->_collection->update($criteria, $updateDate);
    }

    public function purchaseOrder($order){
        unset($order['q']);

        $this->_collection = $dbAdapter->selectCollection('purchase_orders');

        $order['created'] = new \MongoDate();
        $order['stock_due'] = new \MongoDate(strtotime($order['stock_due']));
        $total = 0;
        foreach($order['items'] as $key => $item){
            $order['items'][$key]['qty']      = (float) $item['qty'];
            $order['items'][$key]['cost']     = (float) $item['cost'];
            $order['items'][$key]['sku']      = (float) $item['sku'];
            $order['items'][$key]['id']       = (float) $item['id'];
            $order['items'][$key]['subtotal'] = (float) $item['subtotal'];
            $order['items'][$key]['discount'] = (float) $item['discount'];
            $total += (float)$item['subtotal'];
            unset($order['items'][$key]['discount']);
            $this->_variantModel->incrementVariantStock($item['id'], (float) $item['qty']);
        }
        $order['total'] = $total;
        $this->_collection->insert($order);
    }


    public function fetchAll(Array $criteria = NULL, $options = [],  $limit = 20, $sort = ['update_time' => 1])
    {

        if(empty($criteria)){
            $cursor = $this->_collection->find(); //$criteria, $options);
        }else{
            $cursor = $this->_collection->find($criteria, $options);
        }

        $cursor->limit($limit);
        $cursor->sort($sort);

        $data = [];
        if($cursor->count() > 0) {
            foreach($cursor as $row) {
                $data[] = $row;
            }
        }

        return $data;


    }

}