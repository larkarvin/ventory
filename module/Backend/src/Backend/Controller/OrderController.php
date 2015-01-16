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
            $orderModel->salesOrder($data);  
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
