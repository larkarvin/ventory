<?php

namespace Backend\Model;
use Zend\Validator;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Input;

class Products
{

    private $_collectionName = 'products';
    private $_collection = NULL;
    private $_variantModel = NULL;

    public function setDbAdapter($dbAdapter)
    {
        $this->_collection = $dbAdapter->selectCollection($this->_collectionName);
    }


    public function deleteProduct($productId)
    {
        $criteria = ['_id' => new \MongoId($productId)];

        $result = $this->_collection->remove($criteria, ['justOne' => TRUE]);

        if(empty($result['err'])){
            return TRUE;
        }

        return FALSE;
    }


    public function updateProduct(Array $product)
    {
        $validationResult = $this->_validateProduct($product);
        if($validationResult  !== TRUE){
            return $validationResult;
        }

        $updateData = ['sku'        => $product['sku'],
                       'itemname'   => $product['itemname'],
                       'cost'  => (float) $product['cost'],
                       'price'      => (float) $product['price'],
                       'stock'      => (int) $product['stock'],
                       'update_time' => new \MongoDate(),
        ];

        $criteria = ['_id' => new \MongoId($product['_id'])];
        $result = $this->_collection->update($criteria, ['$set' => $updateData], ['upsert' => true]);

        if(empty($result['err'])){
            return TRUE;
        }
    }

    public function setVariantModel($variantModel){
        $this->_variantModel = $variantModel;
    }

    public function addProduct(Array $product)
    {
        $inputfilters = [];
        $zeroVariant = TRUE;
        $validProductVariants = [];
        $allValid = TRUE;

        $inputfilters['main'] = $this->_validateProduct($product);   
        // check main product if valid     
        if($inputfilters['main']->isValid() !== TRUE){
            $allValid = FALSE;
        }


        // determine if there is inputted variants if not send nodata error message
        $variantCounter = 1;

        if(isset($product['variants']) && count($product['variants']) > 0){
            $zeroVariant = FALSE;
            // check all variants
            // if all passed validation insert
            foreach($product['variants'] as $variant){
                $result = $this->_variantModel->validateProductVariant($variant);
                $inputfilters['variants'][$variantCounter] = $result;
                if($result->isValid() !== TRUE){
                    $allValid = FALSE;
                }
                
                $variantCounter++;
            }

        }else{
            $allValid = FALSE;
        }

           
        // if all are valid insert product and variants
        $insertResult = FALSE;
        if($allValid === TRUE){
            return $this->_insertProduct($product);
        }

        // return error message
        return ['inputfilters' => $inputfilters, 'zeroVariant' => $zeroVariant];

    }    

    private function _insertProduct($product)
    {

        $insertData =  [
                     'itemname'    => $product['itemname'],
                     'active'      => true,
                     'created'     => new \MongoDate(),
                     'update_time' => new \MongoDate(),
                     ];

        $productVariants = $product['variants'];
        unset($product['variants']);

        $result['product'] = $this->_collection->insert($insertData);
        $productId = $insertData['_id'];

        $result['variants'] = $this->_variantModel->insertVariants($productId, $productVariants);

        if(empty($result['product']['err']) && empty($result['variants']['err'])){
            return TRUE;
        }
    }
 


    private function _validateProduct(Array & $product)
    {

        $stringLengthValidator = new Validator\StringLength();
        $stringLengthValidator->setMin(5);
        $itemname = new Input('itemname');
        $itemname->getValidatorChain()
                 ->attach($stringLengthValidator);

        $inputFilter = new InputFilter();
        $inputFilter->add($itemname)->setData($product);

        return $inputFilter;
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

    public function fetchOne(Array $criteria, $options = [])
    {

        $cursor = $this->_collection->findOne($criteria, $options);

        return $cursor;

    }



}
