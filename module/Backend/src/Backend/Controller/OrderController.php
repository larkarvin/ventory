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

class OrderController extends AbstractActionController
{
    public function indexAction()
    {
        
        return new ViewModel();
    }



    public function salesAction()
    {

        $this->layout()->pageTitle = 'Sale Order';
        $this->layout()->pageDesc = 'Things you buy with the supplier';


        $request = $this->getRequest();
        if ($request->isPost()) {
            $posts = $request->getPost();
            $data  = $posts->toArray();

            $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
            $orderModel = new Model\Orders();
            $orderModel->setDbAdapter($mongoDb);

            $productVariants = new Model\ProductVariants();
            $productVariants->setDbAdapter($mongoDb);

            $orderModel->setVariantModel($productVariants);
            try{
                $orderId = $orderModel->salesOrder($data);  
                return $this->redirect()->toRoute('Salesorder', array(
                    'controller' => 'Order',
                    'action'     => 'salesorder',
                    'orderid'    => $orderId,
                ));
            }catch(\Exception $ex){
                $result['error'] = $ex->getMessage();
            }
        }

        return new ViewModel(); 
    }

    public function salesorderAction(){
        $orderid = $this->getEvent()->getRouteMatch()->getParam('orderid');
        if(empty($orderid)){
            echo "error";
        }
        $request = $this->getRequest();
        $getData   = $request->getQuery()->toArray();

        if(isset($getData['print'])){
             $this->layout('layout/print');
        }


        $this->layout()->pageTitle = 'Invoice';
        $this->layout()->pageDesc = '';


        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $orderModel = new Model\Orders();
        $orderModel->setDbAdapter($mongoDb);

        $criteria = ['_id' => new \MongoId($orderid)];
        $data['salesorderData'] = $orderModel->fetchOne($criteria);

        return new ViewModel($data);
    }


    public function invoicesAction(){

    }


    public function purchaseAction()
    {


        $this->layout()->pageTitle = 'Purchase Order';
        $this->layout()->pageDesc = '';


        $request = $this->getRequest();
        if ($request->isPost()) {
            $posts = $request->getPost();
            $data  = $posts->toArray();

            $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
            $orderModel = new Model\Orders();
            $orderModel->setDbAdapter($mongoDb);

            $productVariants = new Model\ProductVariants();
            $productVariants->setDbAdapter($mongoDb);

            $orderModel->setVariantModel($productVariants);
            $orderModel->purchaseOrder($data);  
        }

        return new ViewModel();
    }

    public function typeaheadAction()
    {

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');

        $variantModel = new Model\ProductVariants();
        $variantModel->setDbAdapter($mongoDb);

        $request = $this->getRequest();
        $q = '';
        $getData = $request->getQuery()->toArray();
        $q = $getData['q'];

        $criteria = ['$text' => ['$search' => $q]];
        $data = $variantModel->fetchAll($criteria);


        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
