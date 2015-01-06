<?php

namespace Backend\Model;
use Zend\Validator;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Input;

class Products
{

    private $_collectionName = 'products';
    private $_collection = NULL;

    public function setDbAdapter($dbAdapter)
    {
        $this->_collection = $dbAdapter->selectCollection($this->_collectionName);
    }

    public function addProduct(Array $product)
    {
        $validationResult = $this->_validateProduct($product);
        if($validationResult !== TRUE){
            return $validationResult;
        }

        $insertData = ['sku'       => $product['sku'],
                       'itemname'  => $product['itemname'],
                       'coreprice' => (float) $product['coreprice'],
                       'price'     => (float) $product['price'],
                       'stock'     => (int) $product['stock'],

        ];

        $criteria = ['sku' => $product['sku']];

        $result = $this->_collection->update($criteria, ['$set' => $insertData], ['upsert' => true]);

        if(empty($result['err'])){
            return TRUE;
        }
    }


    public function updateProduct(Array $product)
    {
        $validationResult = $this->_validateProduct($product);
        if($validationResult  !== TRUE){
            return $validationResult;
        }

        $updateData = ['sku'       => $product['sku'],
                       'itemname'  => $product['itemname'],
                       'coreprice' => (float) $product['coreprice'],
                       'price'     => (float) $product['price'],
                       'stock'     => (int) $product['stock'],

        ];

        $criteria = ['_id' => new \MongoId($product['_id'])];
        $result = $this->_collection->update($criteria, ['$set' => $updateData], ['upsert' => true]);

        if(empty($result['err'])){
            return TRUE;
        }
    }



    private function _validateProduct(Array $product)
    {
        $sku = new Input('sku');

        $stringLengthValidator = new Validator\StringLength();
        $stringLengthValidator->setMin(5);

        $sku->getValidatorChain()
        ->attach($stringLengthValidator);

        $itemname = new Input('itemname');
        $itemname->getValidatorChain()
                 ->attach($stringLengthValidator)
                 ->attach(new Validator\NotEmpty());

        $coreprice = new Input('coreprice');
        $coreprice->getValidatorChain()
                  ->attach(new \Zend\I18n\Validator\Float())
                  ->attach(new Validator\NotEmpty())
                  ->attach(new Validator\GreaterThan(['min' => 0, 'inclusive' => true]));

        $price = new Input('price');
        $price->getValidatorChain()
              ->attach(new \Zend\I18n\Validator\Float())
              ->attach(new Validator\NotEmpty())
              ->attach(new Validator\GreaterThan(['min' => 0, 'inclusive' => true]));



        $stock = new Input('stock');
        $stock->getValidatorChain()
               ->attach(new Validator\NotEmpty())
               ->attach(new Validator\Digits())
               ->attach(new Validator\GreaterThan(['min' => 0, 'inclusive' => true]));
               // ->attach(new \Zend\I18n\Validator\Int());

        $inputFilter = new InputFilter();
        $inputFilter->add($sku)
                    ->add($itemname)
                    ->add($coreprice)
                    ->add($price)
                    ->add($stock)
                    ->setData($product);

        if ($inputFilter->isValid()) {
            return TRUE;
        }
        return $inputFilter;

    }

    public function fetchAll(Array $criteria = NULL, $options = [],  $limit = 10)
    {

        if(empty($criteria)){
            $cursor = $this->_collection->find(); //$criteria, $options);
        }else{
            $cursor = $this->_collection->find($criteria, $options);
        }


        $cursor->limit($limit);
        $data = NULL;

        foreach($cursor as $row)
        {

        $data[] = $row;
        }

        return $data;


    }

    public function fetchOne(Array $criteria, $options = [],  $limit = 10)
    {

        $cursor = $this->_collection->findOne($criteria, $options);

        return $cursor;

    }



}
