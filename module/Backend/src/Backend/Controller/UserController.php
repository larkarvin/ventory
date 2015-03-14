<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Backend\Controller;

use Zend\Http\Request;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Backend\Model;
use Zend\Authentication\Adapter\DbTable as AuthAdapter;

class UserController extends AbstractActionController
{
    public function indexAction()
    {
        
        return new ViewModel();
    }

    public function loginAction()
    {

        $auth = $this->getServiceLocator()->get('AuthService'); 

        // var_dump($auth->hasIdentity());exit;

        if ($auth->hasIdentity()) {
            return $this->redirect()->toRoute('Dashboard');
        }
        $request = $this->getRequest();


        $username = $request->getPost('username');
        $password = $request->getPost('password');
        $rememberme = $request->getPost('rememberme');

        if(isset($username) && isset($password)){

            $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
            $staticSalt = "l4r!<@rB3n";
            $authAdapter = new AuthAdapter($dbAdapter,
                            'users', //method setTableName  for dbAdapter
                            'username', // a method for setIdentityColumn
                            'password', //  a method for setCredentialColumn
                            "MD5(CONCAT('$staticSalt', ?))" // setCredentialTreatment(parametrized string) 'MD5(?)'
            );

            $authAdapter
                    ->setIdentity($username)
                    ->setCredential($password);

            $result = $auth->authenticate($authAdapter);

            if ($rememberme) {
                $sessionManager = new \Zend\Session\SessionManager();
                $time = 1209600; // 14 days 1209600/3600 = 336 hours => 336/24 = 14 days
                $sessionManager->rememberMe($time);
            }
            return $this->redirect()->toRoute('dashboard');
        }
        $this->layout('layout/login');
    }

    public function logoutAction() {
        $auth = $this->getServiceLocator()->get('AuthService'); 

        if ($auth->hasIdentity()) {
            $identity = $auth->getIdentity();
        }

        $auth->clearIdentity();
        $sessionManager = new \Zend\Session\SessionManager();
        $sessionManager->forgetMe();
        $this->getServiceLocator()->get('AuthService')->getStorage()->clear();  


        return $this->redirect()->toRoute('Login');
    }

    public function profileAction() {
        echo "change profile";
    }

}
