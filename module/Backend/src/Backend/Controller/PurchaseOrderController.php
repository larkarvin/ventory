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

class PurchaseOrderController extends AbstractActionController
{
    use PaginatorTrait;
    public function listAction()
    {

        $this->layout()->pageTitle = 'Purchase Orders';
        $this->layout()->pageDesc = 'Search and find your POs.';

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $purchaseOrderModel = new Model\PurchaseOrders();
        $purchaseOrderModel->setDbAdapter($mongoDb);

        $request = $this->getRequest();
        $getData = $request->getQuery()->toArray();
        $criteria = [];
        $page = 1;
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

        $currentPage = 1;
        if(!empty($getData['page']) && $getData['page'] > 1){
            $currentPage = $getData['page'];
        }

        $perPage = 20;                                //how many items to show per page
        $skip = ($currentPage -1) * $perPage;

        $data = $purchaseOrderModel->fetchAll($criteria, [], $perPage, $skip, $sort);

        // var_dump($data);exit;
        $totalNumberRecords = $data->count();

        $pagination = $this->_determinePagination($totalNumberRecords, $perPage, $page, 6);

        return new ViewModel(['data' => $data,
                              'getParam' => $getData,
                              'pagination' => $pagination]);
    }


    public function newAction()
    {

        $this->layout()->pageTitle = 'Purchase Order';
        $this->layout()->pageDesc = 'New Order';
        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');

        $sequenceModel = new Model\Sequence();
        $sequenceModel->setDbAdapter($mongoDb);
        $invoiceNumber = $sequenceModel->calculateNext('purchaseorder');


        $request = $this->getRequest();
        if ($request->isPost()) {
            $posts = $request->getPost();
            $data  = $posts->toArray();

            $purchaseOrderModel = new Model\PurchaseOrders();
            $purchaseOrderModel->setDbAdapter($mongoDb);
        

            $purchaseOrderModel->setSequenceModel($sequenceModel);

            try{
                $orderid = $purchaseOrderModel->purchaseOrder($data);  
                return $this->redirect()->toRoute('PurchaseOrder/details', array(
                    'controller' => 'PurchaseOrder',
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

        $this->layout()->pageTitle = 'Purchase Order';
        $this->layout()->pageDesc = '';

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $orderModel = new Model\PurchaseOrders();
        $orderModel->setDbAdapter($mongoDb);

        $criteria = ['_id' => (int)$orderid];
        $data['purchaseorderData'] = $orderModel->fetchOne($criteria);

        return new ViewModel($data);
    }


    public function editAction()
    {

        $orderid = $this->getEvent()->getRouteMatch()->getParam('orderid');
        if(empty($orderid)){
            echo "error";
        }

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $purchaseOrderModel = new Model\PurchaseOrders();
        $purchaseOrderModel->setDbAdapter($mongoDb);

        $updated = FALSE;
        $request = $this->getRequest();
        if ($request->isPost()) {
            $posts = $request->getPost();
            $data  = $posts->toArray();
            $updated = $purchaseOrderModel->updatePurchaseOrder($orderid, $data);
        }

        // product variants model to determine current stock count
        $productsVariantsModel = new Model\ProductVariants();
        $productsVariantsModel->setDbAdapter($mongoDb);


        $criteria = ['_id' => (int)$orderid];
        $purchaseOrder = $purchaseOrderModel->fetchOne($criteria);

        // determine stock count for the items in the sales order
        $productsVariantsModel->getItemQuantities($purchaseOrder['items']);

        $uri = $request->getUri();
        $homeUrl = sprintf('%s://%s', $uri->getScheme(), $uri->getHost());

        $this->layout()->pageTitle = 'Edit Invoice <a href="'.$homeUrl.'/purchaseorder/details/'.$orderid.'">#'. $orderid .'</a>';
        $this->layout()->pageDesc = '';

        return new ViewModel(['purchaseOrder' => $purchaseOrder, 'updated' => $updated]);



    }


    public function cancelAction()
    {
        // $orderid = $this->getEvent()->getRouteMatch()->getParam('orderid');
        $orderid = $this->getEvent()->getRouteMatch()->getParam('orderid');
        if(empty($orderid)){
            echo "error";
        }

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $purchaseOrderModel = new Model\PurchaseOrders();
        $purchaseOrderModel->setDbAdapter($mongoDb);
        $purchaseOrderModel->cancelOrder($orderid);


        return $this->redirect()->toRoute('PurchaseOrder/details', array(
            'controller' => 'PurchaseOrder',
            'action'     => 'details',
            'orderid'    => $orderid,
        ));

    }
    public function finalizeAction()
    {
        // $orderid = $this->getEvent()->getRouteMatch()->getParam('orderid');
        $orderid = $this->getEvent()->getRouteMatch()->getParam('orderid');
        if(empty($orderid)){
            echo "error";
        }

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $purchaseOrderModel = new Model\PurchaseOrders();
        $purchaseOrderModel->setDbAdapter($mongoDb);
        $purchaseOrderModel->finalize($orderid);


        return $this->redirect()->toRoute('Salesorder/details', array(
            'controller' => 'SalesOrder',
            'action'     => 'details',
            'orderid'    => $orderid,
        ));

    }

    public function paymentsAction()
    {
        // $orderid = $this->getEvent()->getRouteMatch()->getParam('orderid');
        $orderid = $this->getEvent()->getRouteMatch()->getParam('orderid');
        if(empty($orderid)){
            echo "error";
        }

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $purchaseOrderModel = new Model\PurchaseOrders();
        $purchaseOrderModel->setDbAdapter($mongoDb);
        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $posts = $request->getPost();
            $payment  = $posts->toArray();

            $purchaseOrderModel->pay($orderid, $payment);
        }


        return $this->redirect()->toRoute('Salesorder/details', array(
            'controller' => 'SalesOrder',
            'action'     => 'details',
            'orderid'    => $orderid,
        ));

    }

    public function markasdeliveredAction()
    {
        $orderid = $this->getEvent()->getRouteMatch()->getParam('orderid');
        if(empty($orderid)){
            echo "error";
        }

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $purchaseOrderModel = new Model\PurchaseOrders();
        $purchaseOrderModel->setDbAdapter($mongoDb);

        $productVariants = new Model\ProductVariants();
        $productVariants->setDbAdapter($mongoDb);

        $purchaseOrderModel->setVariantModel($productVariants);

        $purchaseOrderModel->markAsDelivered($orderid);

        return $this->redirect()->toRoute('PurchaseOrder/details', array(
            'controller' => 'PurchaseOrder',
            'action'     => 'details',
            'orderid'    => $orderid,
        ));

    }

}
