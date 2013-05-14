<?php

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Symfony\Component\HttpFoundation\Response;
use FU\Service\Provider\FineUploaderProvider;
use Neutron\Silex\Provider\ImagineServiceProvider;
    
$app = new Application();

// necessary for silex web profiler
$app->register(new ServiceControllerServiceProvider());
// register twig service provider
$app->register(new TwigServiceProvider(), array(
    'twig.path'    => array(__DIR__.'/../tpl'),
    'twig.options' => array('cache' => __DIR__.'/../cache/twig'),
));
/*
$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    // add custom globals, filters, tags, ...

    return $twig;
}));
*/

$app->register(new FineUploaderProvider());
$app->register(new ImagineServiceProvider());



$app->get("/", function() use ($app) {
    return $app['twig']->render('index.html.twig', array());
});

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    //$page = 404 == $code ? '404.html' : '500.html';

    return new Response($e->getMessage().$e->getTraceAsString(), $code);
});





return $app;