<?php

namespace Backend\Model;
use Zend\Validator;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Input;

class ProductVariants
{

    use FetchTrait;
    private $_collectionName = 'product_variants';
    private $_collection = NULL;
    private $_variantModel = NULL;

    public function setDbAdapter($dbAdapter)
    {
        $this->_collection = $dbAdapter->selectCollection($this->_collectionName);
    }


    public function remove($criteria)
    {
        $this->_collection->remove($criteria);
    }

    public function decrementVariantStock($id, $qty)
    {

        $criteria = ['_id' => new \MongoId($id)];
        $updateData = ['$inc' => ['stock' => -$qty]];
        $this->_collection->update($criteria, $updateData);         


    }
    public function incrementVariantStock($id, $qty)
    {

        $criteria = ['_id' => new \MongoId($id)];
        $updateData = ['$inc' => ['stock' => $qty]];
        $this->_collection->update($criteria, $updateData);         


    }

    public function updateVariant($variantId, $variant){


        $updateData = ['sku'         => $variant['sku'],
                       'variant'     => $variant['variant'],
                       'cost'        => (float) $variant['cost'],
                       'price'       => (float) $variant['price'],
                       // 'stock'       => (int) $variant['stock'],
                       'update_time' => new \MongoDate(),
        ];

        $criteria = ['_id' => new \MongoId($variantId)];
        $result = $this->_collection->update($criteria, ['$set' => $updateData]);

        if(empty($result['err'])){
            return TRUE;
        }
    }


    public function validateProductVariant(Array & $variant, $skipStockValidation = FALSE)
    {

        $stringLengthValidator = new Validator\StringLength();
        $stringLengthValidator->setMin(3);


        $variantName = new Input('variant');
        $variantName->getValidatorChain()
                    ->attach($stringLengthValidator)
                    ->attach(new Validator\NotEmpty());
  


        $sku = new Input('sku');
        $sku->getValidatorChain()
            ->attach($stringLengthValidator);


        $cost = new Input('cost');
        $cost->getValidatorChain()
             ->attach(new \Zend\I18n\Validator\Float())
             ->attach(new Validator\NotEmpty())
             ->attach(new Validator\GreaterThan(['min' => 0, 'inclusive' => true]));

        $price = new Input('price');
        $price->getValidatorChain()
              ->attach(new \Zend\I18n\Validator\Float())
              ->attach(new Validator\NotEmpty())
              ->attach(new Validator\GreaterThan(['min' => 0, 'inclusive' => true]));



        $inputFilter = new InputFilter();
        $inputFilter->add($sku)
                    ->add($variantName)
                    ->add($cost)
                    ->add($price);

        if($skipStockValidation == FALSE){

            $stock = new Input('stock');
            $stock->getValidatorChain()
                   ->attach(new Validator\NotEmpty())
                   ->attach(new Validator\Digits())
                   ->attach(new Validator\GreaterThan(['min' => 0, 'inclusive' => true]));
            $inputFilter->add($stock);
        }

        $inputFilter->setData($variant);

        return $inputFilter;

    }


    public function insertVariants($productId, $variants){

        $batchData = [];

        foreach($variants as $variant){
            $productId = new \MongoId($productId);

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


    public function getItemQuantities(&$items)
    {

        foreach($items as $key => $item){
            $itemCriteria = ["_id" => new \MongoId($item['id'])];
            $result = $this->fetchOne($itemCriteria);
            $items[$key]['stock'] = $result['stock'];
        }

    }


}