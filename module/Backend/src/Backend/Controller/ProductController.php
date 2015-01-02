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

    public function testAction(){
        echo "Asdfa";exit;
    }


    public function addProductAction(){
        
        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');

        $request = $this->getRequest();
   
  
        if ($request->isPost()) {
            $posts = $request->getPost();
            $data  = $posts->toArray();
            $productModel = new Model\Products();
            $productModel->setDbAdapter($mongoDb);
            $result = $productModel->addProduct($data);

            if($result === TRUE){

                return new ViewModel(['success'     => TRUE, 
                                      'productName' => $data['itemname']]
                                    );
            }
            return new ViewModel(['inputFilter' => $result]);
        }



        return new ViewModel();

    }


}
