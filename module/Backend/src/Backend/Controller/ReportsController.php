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


    public function salesAction()
    {



        $mongoDb = $this->getServiceLocator()->get('Mongo\Db');

        $viewModelData = [];
        $request = $this->getRequest();


        $getData = $request->getQuery();
        $data  = $getData->toArray();



        $formData['date_from'] = Date('Y-m-d');
        if(isset($data['date_from'])){
            $formData['date_from'] = $data['date_from'];
        }

        $formData['date_to'] = Date('Y-m-d');
        if(isset($data['date_to'])){
            $formData['date_to'] = $data['date_to'];
        }

        $formData['grouping'] = 'daily';
        if(isset($data['grouping'])){
            $formData['grouping'] = $data['grouping'];
        }

        $salesOrderModel = new Model\SalesOrders();
        $salesOrderModel->setDbAdapter($mongoDb);



        switch ($formData['grouping']) {
            default:
            case 'daily':
                    $groupId = [    'year' => ['$year' => '$created'],
                                    'month' => ['$month' => '$created'],
                                    'day' => ['$dayOfMonth' => '$created']
                                ];
                break;
            case 'weekly':
                    $groupId = [    'year' => ['$year' => '$created'],
                                    'month' => ['$month' => '$created'],
                                    'week' => ['$week' => '$created']
                                ];
                break;
            case 'monthly':
                    $groupId = [    'year' => ['$year' => '$created'],
                                    'month' => ['$month' => '$created'],
                                ];
                break;
        }


        // round date_to to 1 minute before midnight
        $formData['date_to'] = Date('Y-m-d 23:59:59', strtotime($formData['date_to']));

        $salesReport = [
                    ['$match' => ['created' =>
                                        [
                                            '$gte' => new \MongoDate(strtotime($formData['date_from'])),
                                            '$lte' => new \MongoDate(strtotime($formData['date_to']))
                                        ],
                                 ]
                    ],
                    ['$group' =>
                        [
                            '_id' => $groupId,
                            'totalsales' => ['$sum' => '$total'],
                            'totalsales_lessAR' => ['$sum' =>
                                                     ['$subtract' => ['$total', '$payment_left']]
                                            ],
                            'total_cost' => ['$sum' => '$cost'],
                        ],
                    ],

                ];

        $result = $salesOrderModel->aggregate($salesReport);

        $this->layout()->pageTitle = ucfirst($formData['grouping']) .' ' . 'Report';
        // $this->layout()->pageDesc = '';

        if(isset($getData['print'])){
             $this->layout('layout/print');
        }

        return new ViewModel(['formData' => $formData,
                              'result'   => $result,
                            ]);

    }



}
