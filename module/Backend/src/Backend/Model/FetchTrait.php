<?php

namespace Backend\Model;
use Zend\Validator;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Input;

trait FetchTrait
{

    public function fetchAll(Array $criteria = NULL, $options = [],  $limit = 20, $skip = 0, $sort = ['created' => 1])
    {

        if(empty($criteria)){
            $cursor = $this->_collection->find(); //$criteria, $options);
        }else{
            $cursor = $this->_collection->find($criteria, $options);
        }

        $cursor->limit($limit)->skip($skip);
        $cursor->sort($sort);

        $data = [];
        // echo $cursor->count();exit;
        if($cursor->count() > 0) {
            return $cursor;
        }

        return [];
    }

    public function fetchOne(Array $criteria, $options = [])
    {

        $cursor = $this->_collection->findOne($criteria, $options);

        return $cursor;

    }

}