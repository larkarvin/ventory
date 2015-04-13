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

class SalesOrderController extends AbstractActionController
{
    public function listAction()
    {

        $this->layout()->pageTitle = 'Sales Orders';
        $this->layout()->pageDesc = 'Search and find your Invoices.';

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $salesOrderModel = new Model\SalesOrders();
        $salesOrderModel->setDbAdapter($mongoDb);

        $request = $this->getRequest();
        $getData = $request->getQuery()->toArray();
        $criteria = [];
        if(!empty($getData['q'])){
            $criteria = [ '$text' => [ '$search' => $getData['q'] ]];
        }
        $sort = ['created' => -1];

        $orderby = -1;
        if(!empty($getData['order']) && $getData['order'] == 'ASC')
            $orderby = 1;
        
        if(!empty($getData['sort'])){
            $keyName = $getData['sort'];
            $sort = [ $keyName => $orderby];
        }

        $data = $salesOrderModel->fetchAll($criteria, [], 20, $sort);


        return new ViewModel(['data' => $data, 'searchParams' => $getData]);
    }



    public function newAction()
    {

        $this->layout()->pageTitle = 'Sale Invoice';
        $this->layout()->pageDesc = 'New Sales Order';
        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');

        $sequenceModel = new Model\Sequence();
        $sequenceModel->setDbAdapter($mongoDb);
        $invoiceNumber = $sequenceModel->calculateNext('salesorder');


        $request = $this->getRequest();
        if ($request->isPost()) {
            $posts = $request->getPost();
            $data  = $posts->toArray();

            $salesOrderModel = new Model\SalesOrders();
            $salesOrderModel->setDbAdapter($mongoDb);
        

            $salesOrderModel->setSequenceModel($sequenceModel);

            try{
                $orderid = $salesOrderModel->salesOrder($data);  
                return $this->redirect()->toRoute('Salesorder/details', array(
                    'controller' => 'SalesOrder',
                    'action'     => 'details',
                    'orderid'    => $orderid,
                ));
            }catch(\Exception $ex){
                $result['error'] = $ex->getMessage();
            }
        }

        return new ViewModel(['invoiceNumber' => $invoiceNumber]); 
    }

    public function detailsAction()
    {
        // $orderid = $this->getEvent()->getRouteMatch()->getParam('orderid');
        $orderid = $this->getEvent()->getRouteMatch()->getParam('orderid');
        if(empty($orderid)){
            echo "error";
        }

        $request = $this->getRequest();
        $getData  = $request->getQuery()->toArray();

        if(isset($getData['print'])){
             $this->layout('layout/print');
        }



        $this->layout()->pageTitle = 'Invoice';
        $this->layout()->pageDesc = '';

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $orderModel = new Model\SalesOrders();
        $orderModel->setDbAdapter($mongoDb);

        $criteria = ['_id' => (int)$orderid];
        $data['salesorderData'] = $orderModel->fetchOne($criteria);

        return new ViewModel($data);
    }


    public function markaspaidAction()
    {
        // $orderid = $this->getEvent()->getRouteMatch()->getParam('orderid');
        $orderid = $this->getEvent()->getRouteMatch()->getParam('orderid');
        if(empty($orderid)){
            echo "error";
        }

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $salesOrderModel = new Model\SalesOrders();
        $salesOrderModel->setDbAdapter($mongoDb);
        $salesOrderModel->markAsPaid($orderid);
                return $this->redirect()->toRoute('Salesorder/details', array(
                    'controller' => 'SalesOrder',
                    'action'     => 'details',
                    'orderid'    => $orderid,
                ));

    }

    public function markasdeliveredAction()
    {
        // $orderid = $this->getEvent()->getRouteMatch()->getParam('orderid');
        $orderid = $this->getEvent()->getRouteMatch()->getParam('orderid');
        if(empty($orderid)){
            echo "error";
        }

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $salesOrderModel = new Model\SalesOrders();
        $salesOrderModel->setDbAdapter($mongoDb);

        $productVariants = new Model\ProductVariants();
        $productVariants->setDbAdapter($mongoDb);

        $salesOrderModel->setVariantModel($productVariants);

        $salesOrderModel->markAsDelivered($orderid);
        // exit;
                return $this->redirect()->toRoute('Salesorder/details', array(
                    'controller' => 'SalesOrder',
                    'action'     => 'details',
                    'orderid'    => $orderid,
                ));

    }

}
