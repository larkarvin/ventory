<?php

namespace Backend\Model;

class Sequence
{
    private $_collectionName = 'sequence';
    private $_collection = NULL;

    public function setDbAdapter($dbAdapter)
    {
        $this->_collection = $dbAdapter->selectCollection($this->_collectionName);
    }

    public function getNextSequence($sequenceName)
    {    
        $options = [ 'new'    => TRUE,
                     'upsert' => TRUE,
                   ];
        $criteria = ['_id' => $sequenceName];
        $update   = [ '$inc' => ['sequence' => 1]];

        $result = $this->_collection->findAndModify($criteria,$update,[],$options);
        return $result['sequence'] + 1;

    }

    public function calculateNext($sequenceName)
    {
        $criteria = ['_id' => $sequenceName];
        $result = $this->_collection->findOne($criteria);
        return $result['sequence'] + 1;
    }

}