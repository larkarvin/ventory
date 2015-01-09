<?php

namespace Backend\Model;
use Zend\Validator;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Input;

class ProductVariants
{

    private $_collectionName = 'product_variants';
    private $_collection = NULL;
    private $_variantModel = NULL;

    public function setDbAdapter($dbAdapter)
    {
        $this->_collection = $dbAdapter->selectCollection($this->_collectionName);
    }

    public function insertVariants($productId, $variants){

        $batchData = [];
        foreach($variants as $variant){
            $batchData[] = [
                            'product_id'  => $productId,
                            'variant'     => $variant['variant'],
                            'sku'         => $variant['sku'],
                            'cost'        => (float) $variant['cost'],
                            'price'       => (float) $variant['price'],
                            'stock'       => (int) $variant['stock'],
                            'update_time' => new \MongoDate(),
                            'created'     => new \MongoDate(),
            ];
        }


        $result = $this->_collection->batchInsert($batchData);


        if(empty($result['err'])){
            return TRUE;
        }

        return FALSE;
    }

}