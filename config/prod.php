<?php
use Silex\Provider\MonologServiceProvider;

$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../logs/silex_prod.log',
    'monolog.level' => Monolog\Logger::ERROR,
));

// app config
$app['fu.upload_dir'] = __DIR__.'/../web/uploads/';
$app['fu.chunks_dir'] = __DIR__.'/../web/uploads/chunks/';
$app['fu.size_limit'] = 2 * 1024 * 1024;
$app['fu.input_name'] = 'qqfile';
$app['fu.allowed_ext'] = array("jpg", "jpeg", "png");//array('jpeg', 'jpg', 'png', 'gif');
$app['fu.final_width'] = 270;


$app['imagine.driver'] = 'Gd';
//$app['imagine.driver'] = 'Imagick';


