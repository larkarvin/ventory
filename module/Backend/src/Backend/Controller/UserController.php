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

    public function dashboardAction()
    {

        $this->layout()->pageTitle = 'Dashboard';
        // $this->layout()->pageDesc = 'Change the variant information here';

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');

        $sequenceModel = new Model\Sequence();

        $request = $this->getRequest();

        $salesOrderModel = new Model\SalesOrders();
        $salesOrderModel->setDbAdapter($mongoDb);

        // sales order today
        $todayTime = strtotime(Date('Y-m-d'));


        $criteria = ['created' => ['$gte' =>  new \MongoDate($todayTime)]];
        $result = $salesOrderModel->fetchAll($criteria);
        $dashboardData['salesordertoday'] = $result->count();
        unset($result);

        // last 10 orders
        $dashboardData['salesOrders'] = $salesOrderModel->fetchAll([], [], 10, 0, ['created' => -1]);



        // Last Purchase Order
        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $purchaseOrderModel = new Model\PurchaseOrders();
        $purchaseOrderModel->setDbAdapter($mongoDb);
        $dashboardData['purchaseOrders'] = $purchaseOrderModel->fetchAll([], [], 10, 0, ['created' => -1]);

        // accounts receivable
        $aggregate = [['$group' => ["_id" => null, "total" => ['$sum' => '$payment_left']]]];
        $result = $salesOrderModel->aggregate($aggregate);

        $dashboardData['totalAcctsReceivable'] = 0;
        if(isset($result['result'][0]['total']))
        $dashboardData['totalAcctsReceivable'] = $result['result'][0]['total'];

        // total sales last 7 days
        $oneweekago = strtotime("-7 day");

        $totalSalesAggregate = [
                            ['$match' => ['created' => ['$gte' => new \MongoDate($oneweekago)]]],
                            ['$group' => ["_id" => null, "totalsales" => ['$sum' => ['$subtract' => ['$total', '$payment_left']]]]],
                        ];



        $salesReport = [
                            ['$match' => ['created' => ['$gte' => new \MongoDate($oneweekago)]]],
                            ['$group' =>
                                [
                                    '_id' => [
                                            'year' => ['$year' => '$created'],
                                            'month' => ['$month' => '$created'],
                                            'day' => ['$dayOfMonth' => '$created'],
                                            ],
                                    'totalsales' => ['$sum' => '$total'],
                                    'totalsales_lessAR' => ['$sum' =>
                                                             ['$subtract' => ['$total', '$payment_left']]                              ],
                                    'total_cost' => ['$sum' => '$cost'],
                                ],
                            ],
                        ];

        $result = $salesOrderModel->aggregate($totalSalesAggregate);

        // print_r($result);exit;
        $dashboardData['totalsalesLast7days'] = 0;
        if(isset($result['result'][0]['totalsales']))
        $dashboardData['totalsalesLast7days'] = $result['result'][0]['totalsales'];

        return new ViewModel(['dashboardData' => $dashboardData]);


    }

    public function changepasswordAction()
    {

        $request = $this->getRequest();
        $formData['password'] = '';
        $formData['password2'] = '';
        $validationMessages = [];

        $auth = $this->getServiceLocator()->get('AuthService');

        $username = $auth->getIdentity();
        $success = FALSE;

        if ($request->isPost()) {
            $posts = $request->getPost();
            $data  = $posts->toArray();

            $validationPass = TRUE;
            if(isset($data['password'])
                && $data['password'] !== ''
            ){
                $formData['password'] = $data['password'];
            }


            if(isset($data['password2'])
                && $data['password2'] !== ''
            ){
                $formData['password2'] = $data['password2'];
            }

            // validate
            if($formData['password'] !== $formData['password2']){
                $validationPass = FALSE;
                $validationMessages[] = 'Password does not match';
            }

            if( !(strlen($formData['password']) >= 5) ){
                $validationPass = FALSE;
                $validationMessages[] = 'Password should be more five characters';
            }

            if($validationPass){

                $config = $this->getServiceLocator()->get('Config');
                $staticSalt = $config['staticSalt'];
                $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
                $userModel = new Model\Users;
                $userModel->setDbAdapter($dbAdapter);

                $result = $userModel->changePassword($username, $formData['password'], $staticSalt);
                if( $result->getAffectedRows() > 0 ){
                    $success = TRUE;
                }
            }

        }
        $this->layout()->pageTitle = 'Change Password for ' . $username;


        return new ViewModel(['formData' => $formData,
                              'validationMessages' => $validationMessages,
                              'success' => $success]);
    }

    public function loginAction()
    {

        $auth = $this->getServiceLocator()->get('AuthService');
        $this->layout()->pageTitle =  'Login';

        if ($auth->hasIdentity()) {
            return $this->redirect()->toRoute('User/dashboard', array(
                    'controller' => 'User',
                    'action'     => 'dashboard',
                ));
        }
        $request = $this->getRequest();

        if ($request->isPost()) {

            $posts = $request->getPost();
            $data  = $posts->toArray();
            // default value to NULL
            $username = $password = $rememberme = NULL;

            if(isset($data['username']))  { $username = $data['username']; }
            if(isset($data['password']))  { $password = $data['password']; }
            if(isset($data['rememberme'])){ $rememberme = $data['rememberme']; }

            $config = $this->getServiceLocator()->get('Config');
            if(isset($username) && isset($password)){

                $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
                $staticSalt = $config['staticSalt'];

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

                return $this->redirect()->toRoute('User/dashboard', array(
                    'controller' => 'User',
                    'action'     => 'dashboard',
                ));

            }
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
