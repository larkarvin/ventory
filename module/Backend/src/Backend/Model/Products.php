<?php

namespace Backend\Model;
use Zend\Validator;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Input;

class Products
{

    private $_collectionName = 'products';
    private $_collection = 'products';

    public function setDbAdapter($dbAdapter)
    {
        $this->_collection = $dbAdapter->selectCollection($this->_collectionName);
    }

    public function addProduct(Array $data)
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
                  ->attach(new Validator\NotEmpty());

        $price = new Input('price');
        $price->getValidatorChain()
              ->attach(new Validator\NotEmpty())
              ->attach(new \Zend\I18n\Validator\Float())
              ->attach(new Validator\GreaterThan(['min' => 0, 'inclusive' => true]));

        $stock = new Input('stock');
        $stock->getValidatorChain()
              ->attach(new Validator\NotEmpty())
              ->attach(new Validator\GreaterThan(['min' => 0, 'inclusive' => true]));

        $inputFilter = new InputFilter();
        $inputFilter->add($sku)
                    ->add($itemname)
                    ->add($coreprice)
                    ->add($price)
                    ->add($stock)
                    ->setData($data);

        if ($inputFilter->isValid()) {
            $insertData = ['sku'       => $data['sku'],
                           'itemname'  => $data['itemname'],
                           'coreprice' => (float) $data['coreprice'],
                           'price'     => (float) $data['price'],
                           'stock'     => (int) $data['price'],

            ];
            $criteria = ['sku' => $data['sku']];

            $result = $this->_collection->update($criteria, ['$set' => $insertData]);
 
            if(empty($result['err'])){
                return TRUE;
            }
        }

        return $inputFilter;

    }


}
