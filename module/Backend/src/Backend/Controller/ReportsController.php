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

class ReportsController extends AbstractActionController
{

    use PaginatorTrait;
    public function indexAction()
    {
        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');
    }


    public function dailyAction()
    {

        $this->layout()->pageTitle = 'Daily Report';
        $this->layout()->pageDesc = 'Change the variant information here';

        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');

        $viewModelData = [];
        $request = $this->getRequest();

        $variantModel = new Model\ProductVariants();
        $variantModel->setDbAdapter($mongoDb);

        $getData   = $request->getQuery()->toArray();

        return new ViewModel();

    }



}
