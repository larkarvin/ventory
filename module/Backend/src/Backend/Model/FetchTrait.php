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

        $cursor->sort($sort);
        $cursor->limit($limit)->skip($skip);
        

        return $cursor;



    }
    public function aggregate(Array $aggregate)
    {

        $cursor = $this->_collection->aggregate($aggregate);
        

        return $cursor;



    }

    public function fetchOne(Array $criteria, $options = [])
    {

        $cursor = $this->_collection->findOne($criteria, $options);

        return $cursor;

    }

}