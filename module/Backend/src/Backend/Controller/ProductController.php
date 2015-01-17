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

class ProductController extends AbstractActionController
{
    public function indexAction()
    {
        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
    }


    public function variantAction()
    {

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');

        $viewModelData = [];
        $request = $this->getRequest();

        $variantModel = new Model\ProductVariants();
        $variantModel->setDbAdapter($mongoDb);

        $getData   = $request->getQuery()->toArray();
        $variantId = $getData['variant_id'];

        if ($request->isPost()) {
            $posts = $request->getPost();
            $data  = $posts->toArray();

            $variantId = $data['variant_id'];
            unset($data['variant_id']);

            $validationResult = $variantModel->validateProductVariant($data);

            if($validationResult->isValid()){
                $result = $variantModel->updateVariant($variantId, $data);
                if($result === TRUE){
                    $resultData = ['success' => TRUE];
                    $viewModelData = array_merge($viewModelData, $resultData);
                }
            }else{
                $viewModelData = array_merge($viewModelData, ['inputFilter' => $validationResult ]);
            }
        }

        $criteria = ['_id' => new \MongoId($variantId)];
        $variantData = $variantModel->fetchOne($criteria);

        $viewModelData = array_merge($viewModelData, ['variantData' => $variantData]);
        return new ViewModel($viewModelData);

    }

    public function listAction()
    {
        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $productModel = new Model\Products();
        $productModel->setDbAdapter($mongoDb);

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

        $data = $productModel->fetchAll($criteria, [], 20, $sort);


        return new ViewModel(['data' => $data, 'searchParams' => $getData]);
    }

    public function lowStockAction()
    {

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $productVariantModel = new Model\ProductVariants();
        $productVariantModel->setDbAdapter($mongoDb);

        $request = $this->getRequest();
        $getData = $request->getQuery()->toArray();
        $criteria = [];
        if(!empty($getData['q'])){
            $criteria = [ '$text' => [ '$search' => $getData['q'] ]];
        }
        $sort = ['stock' => 1];

        $orderby = -1;
        if(!empty($getData['order']) && $getData['order'] == 'ASC')
            $orderby = 1;
        
        if(!empty($getData['sort'])){
            $keyName = $getData['sort'];
            $sort = [ $keyName => $orderby];
        }

        $data = $productVariantModel->fetchAll($criteria, [], 50, $sort);


        return new ViewModel(['data' => $data, 'searchParams' => $getData]);
    }


    public function deleteAction()
    {
        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $request = $this->getRequest();
        $productModel =  new Model\Products();
        $productModel->setDbAdapter($mongoDb);

        $getData   = $request->getQuery()->toArray();
        $productId = $getData['product_id'];

        $criteria    = ['_id' => new \MongoId($productId)];
        $productData = $productModel->fetchOne($criteria);

        $result = $productModel->deleteProduct($productId);

        return new ViewModel(['product' => $productData]);
    }

    public function updateAction()
    {
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

    public function removeVariantAction()
    {

        $request = $this->getRequest();
        $getData = $request->getQuery()->toArray();
        $productVariantModel = new Model\ProductVariants();
        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $productVariantModel->setDbAdapter($mongoDb);

        $criteria['_id'] = new \MongoId($getData['variant_id']);
        $productVariantModel->remove($criteria);


        $newURL = 'http://backend.localhost/product/details?product_id=' . $getData['product_id'];
        header('Location: '.$newURL);
        exit;

    }


    public function addProductAction()
    {
        
        $request = $this->getRequest();

        if ($request->isPost()) {
            $posts = $request->getPost();
            $data  = $posts->toArray();

            $productModel        = new Model\Products();
            $productVariantModel = new Model\ProductVariants();

            $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
            $productModel->setDbAdapter($mongoDb);

            $productVariantModel->setDbAdapter($mongoDb);
            $productModel->setVariantModel($productVariantModel);

            $result = $productModel->addProduct($data);
            if($result === TRUE){

                return new ViewModel(['success'     => TRUE, 
                                      'productName' => $data['itemname']]
                                    );

            }
            return new ViewModel(['result' => $result]);
        }
        return new ViewModel();

    }


    public function detailsAction()
    {
        $request = $this->getRequest();
        $getData = $request->getQuery()->toArray();
        $productId = $getData['product_id'];

       if(empty($productId)){
            return new ViewModel();
        }

        $viewModelData = [];
        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
        $productModel = new Model\Products();
        $productModel->setDbAdapter($mongoDb);

        $variantModel = new Model\ProductVariants();
        $variantModel->setDbAdapter($mongoDb);

        // post means they are adding a variant
        if ($request->isPost()) {
            $posts = $request->getPost();
            $data  = $posts->toArray();

            $productId = new \MongoId($data['product_id']);
            unset($data['product_id']);
            $result = $variantModel->validateProductVariant($data);

            if($result->isValid()){
                // multi insert thats why you need to create an array with a single value
                $insertData[0] = $data;
                $insertResult = $variantModel->insertVariants($productId, $insertData);

                $viewModelData = array_merge($viewModelData, ['success' => $insertResult]);
                
            }else{
                $viewModelData = array_merge($viewModelData, ['inputFilter' => $result]);
            }
        }

        // fetch product data
        $criteria = ['_id' => new \MongoId($productId)];
        $productData = $productModel->fetchOne($criteria);

        // fetch all variants data
        $criteria = ['product_id' => new \MongoId($productId)];
        $sort = ['variant' => 1];
        $variantData = $variantModel->fetchAll($criteria,[], NULL, $sort);


        $viewModelData['productData'] = $productData;
        $viewModelData['variantData'] = $variantData;


        return new ViewModel($viewModelData);
    }


}
