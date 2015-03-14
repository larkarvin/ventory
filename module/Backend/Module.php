<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Backend;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Authentication\Storage;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable as DbTableAuthAdapter;
use Zend\Authentication\Storage\Session;

class Module
{
    // public function onBootstrap(MvcEvent $e)
    // {
    //     $eventManager        = $e->getApplication()->getEventManager();
    //     $moduleRouteListener = new ModuleRouteListener();
    //     $moduleRouteListener->attach($eventManager);
    // }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $e)
    {



        $eventManager = $e->getApplication()->getEventManager();        
        $checkACL = $eventManager->attach(MvcEvent::EVENT_DISPATCH, function($e) {
            // only implement ACL on non CLI application

            
                $controller = $e->getTarget();
                $auth = new AuthenticationService();
                $isLogin = $auth->hasIdentity();
                $params = $e->getApplication()->getMvcEvent()->getRouteMatch()->getParams();

                // var_dump($params['action'] !== "login");
                // var_dump($isLogin);
                // exit;

                if($isLogin  && $params['action'] == "login"){
                    return $controller->redirect()->toRoute('Dashboard');
                }

                if($isLogin == FALSE && $params['action'] !== "login"){
                    return $controller->redirect()->toRoute('Login');  
                }

                // check if action being accessed is "login"
                // there should only be one method called "login" across 
                // any controller or else this won't work
                  
                

        });




        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

    public function getServiceConfig() {

        return array(
            'factories' => array(
                'Mongo\Db' => function ($serviceManager) {

                    $mongoConfig = $serviceManager->get('Config')['Mongo'];
                    // check if Mongo Client Class Exists
                    $mongoClass  = class_exists('\MongoClient') ? '\MongoClient' : '\Mongo';
                    $connection  = new $mongoClass($mongoConfig['connectionString'],
                                                  $mongoConfig['connectOptions']);

                    $MongoDb = $connection->selectDb($mongoConfig['connectOptions']['db']);

                    return $MongoDb;
                },

                'AuthService' => function($sm) {
                
                    $dbAdapter           = $sm->get('Zend\Db\Adapter\Adapter');
                    $dbTableAuthAdapter  = new DbTableAuthAdapter($dbAdapter, 'users','username','password', 'MD5(?)');

                    $authService = new AuthenticationService();
                    $authService->setAdapter($dbTableAuthAdapter);
                    $authService->setStorage(new Session());

                    return $authService;
                },
                ),
        );
    }


    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
            
        );
    }
}
