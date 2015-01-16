<?php

namespace Backend\Model;
use Zend\Validator;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Input;

class Orders
{

    private $_collectionName = 'sales_orders';
    private $_collection = NULL;
    private $_variantModel = NULL;

    public function setVariantModel($variantModel)
    {
        $this->_variantModel = $variantModel;
    }

    public function setDbAdapter($dbAdapter)
    {
        $this->_collection = $dbAdapter->selectCollection($this->_collectionName);
    }

    public function fetchOne(Array $criteria, $options = [])
    {

        $cursor = $this->_collection->findOne($criteria, $options);

        return $cursor;

    }

    public function salesOrder($order){
        unset($order['q']);

        $order['created'] = new \MongoDate();

        $total = 0;
        foreach($order['items'] as $key => $item){
            $order['items'][$key]['qty']      = (float) $item['qty'];
            $order['items'][$key]['price']    = (float) $item['price'];
            $order['items'][$key]['discount'] = (float) $item['discount'];
            $order['items'][$key]['subtotal'] = (float) $item['subtotal'];
            $total += (float)$item['subtotal'];
            $this->_variantModel->decrementVariantStock($item['id'], (float) $item['qty']);
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