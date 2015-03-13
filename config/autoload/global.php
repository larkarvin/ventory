<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return array(
    'Mongo' => array(
        'connectionString' => 'mongodb://127.0.0.1:27017',
        'connectOptions'   => array(
                                  "connect" => FALSE,
                                  "connectTimeoutMS" => 60000,
                                  "db" => "zaka_local",
                                  ),
    ),
    'db' => array(
        'driver' => 'Pdo',
        'dsn' => 'mysql:dbname=test;host=localhost',
        'driver_options' => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
        ),
        'username' => 'root',
        'password' => 'password',
    ),
);