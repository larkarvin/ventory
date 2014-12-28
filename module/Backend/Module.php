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

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
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
                    $connection->connect();
                    $MongoDb = $connection->selectDb($mongoConfig['connectOptions']['db']);
                    return $MongoDb;
                }),
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
