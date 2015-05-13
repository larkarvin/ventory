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

class StockAdjustmentController extends AbstractActionController
{

     use PaginatorTrait;
    public function indexAction()
    {
        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
    }



    public function listAction()
    {

        $this->layout()->pageTitle = 'Stock Adjustments';
        $this->layout()->pageDesc = 'Increment and Decrement Stocks.';

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $salesOrderModel = new Model\StockAdjustments();
        $salesOrderModel->setDbAdapter($mongoDb);

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

        $data = $salesOrderModel->fetchAll($criteria, [], $perPage, $skip, $sort);

        $totalNumberRecords = $data->count();

        $pagination = $this->_determinePagination($totalNumberRecords, $perPage, $page, 6);

        return new ViewModel(['data' => $data,
                              'getParam' => $getData,
                              'pagination' => $pagination]);
    }




    public function newAction()
    {
        $this->layout()->pageTitle = 'Stock Adjustments';
        $this->layout()->pageDesc = 'Adjust your stocks, list returns or defective items';
        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        
        $sequenceModel = new Model\Sequence();
        $sequenceModel->setDbAdapter($mongoDb);
        $adjustmentNumber = $sequenceModel->calculateNext('stockadjustment');


        $request = $this->getRequest();
        if ($request->isPost()) {
            try{   
                $posts = $request->getPost();
                $data  = $posts->toArray();

                $stockAdjustmentModel = new Model\StockAdjustments();
                $stockAdjustmentModel->setDbAdapter($mongoDb);
            
        
                $productVariantModel = new Model\ProductVariants($mongoDb);
                $productVariantModel->setDbAdapter($mongoDb);

                $stockAdjustmentModel->setSequenceModel($sequenceModel);
                $stockAdjustmentModel->setVariantModel($productVariantModel);
                $stockAdjustId = $stockAdjustmentModel->adjustInventory($data);  
                return $this->redirect()->toRoute('StockAdjustment/details', array(
                    'controller' => 'StockAdjustment',
                    'action'     => 'details',
                    'orderid'    => $stockAdjustId,
                ));
            }catch(\Exception $ex){
                $result['error'] = $ex->getMessage();
            }
        }

        return new ViewModel(['adjustmentNumber' => $adjustmentNumber]); 

    }


    public function updateAction()
    {

        $this->layout()->pageTitle = 'Update Product';
        $this->layout()->pageDesc = 'Change Product information here';

        $request = $this->getRequest();
        $getData = $request->getQuery()->toArray();
        $productId = $getData['product_id'];

        if(empty($productId)){
            return new ViewModel();
        }

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $productModel = new Model\Products();
        $productModel->setDbAdapter($mongoDb);

        $viewModelData = [];
        if ($request->isPost()) {
            $posts = $request->getPost();
            $data  = $posts->toArray();

            $result = $productModel->updateProduct($data);

            if($result === TRUE){
                $resultData = ['success' => TRUE, 'productName' => $data['itemname']];
                $viewModelData = array_merge($viewModelData, $resultData);
            }else{
                $viewModelData = array_merge($viewModelData, ['inputFilter' => $result]);
            }
        }

        $criteria = ['_id' => new \MongoId($productId)];
        $productData['product'] = $productModel->fetchOne($criteria);

        $viewModelData = array_merge($viewModelData, $productData);

        return new ViewModel($viewModelData);

    }

 
    public function detailsAction()
    {
        $orderid = $this->getEvent()->getRouteMatch()->getParam('orderid');
        if(empty($orderid)){
            echo "error";
        }

        $request = $this->getRequest();
        $getData  = $request->getQuery()->toArray();

        if(isset($getData['print'])){
             $this->layout('layout/print');
        }

        $this->layout()->pageTitle = 'Stock Adjustment';
        $this->layout()->pageDesc = '';

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $stockAdjustmentModel = new Model\StockAdjustments();
        $stockAdjustmentModel->setDbAdapter($mongoDb);

        $criteria = ['_id' => (int)$orderid];
        $data['stockAdjustment'] = $stockAdjustmentModel->fetchOne($criteria);

        return new ViewModel($data);
    }


}
