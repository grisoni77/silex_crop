<?php

use Silex\Application;
use FU\Service\FineUploader\qqFileUploader;

$fu = $app['controllers_factory'];

$fu->get("/", function(Application $app) {
            return $app['twig']->render('fu.html.twig');
        });

$fu->post("handleUploads", function(Application $app) {

    $uploader = $app['fu'];
    
    // Specify the list of valid extensions, ex. array("jpeg", "xml", "bmp")
    $uploader->allowedExtensions = array();

    // Specify max file size in bytes.
    $uploader->sizeLimit = 2 * 1024 * 1024;

    // Specify the input name set in the javascript.
    $uploader->inputName = 'qqfile';

    // If you want to use resume feature for uploader, specify the folder to save parts.
    $uploader->chunksFolder = 'chunks';

    // Call handleUpload() with the name of the folder, relative to PHP's getcwd()
    $result = $uploader->handleUpload('../cache');

    // To save the upload with a specified name, set the second parameter.
    // $result = $uploader->handleUpload('uploads/', md5(mt_rand()).'_'.$uploader->getName());
    // To return a name used for uploaded file you can use the following line.
    $result['uploadName'] = $uploader->getUploadName();

    header("Content-Type: text/plain");
    return json_encode($result);
    //return $app['twig']->render('fu.html.twig');
    //return 'TODO';
});


$app->mount('/fu', $fu);
