<?php

namespace Backend\Model;
use Zend\Validator;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Input;

class Users
{


    private $_dbAdapter;

    public function setDbAdapter($dbAdapter)
    {
        $this->_dbAdapter = $dbAdapter;
    }

    public function changePassword($username, $password, $staticSalt)
    {

        $updateStatement = "UPDATE `users`
                            SET `password` = MD5(CONCAT('$staticSalt', ?))
                            WHERE `username`='$username';";


        $statement = $this->_dbAdapter->createStatement($updateStatement, array($password));
        return $statement->execute();

    }



}