<?php

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Imagine\Image\Point;
use Imagine\Image\Box;

$fu = $app['controllers_factory'];

$fu->get("/", function(Application $app) {
            return $app['twig']->render('fu.html.twig');
        });

$fu->get("/uber", function(Application $app) {
            return $app['twig']->render('uuc.html.twig');
        });

$fu->get("/cris", function(Application $app) {
            return $app['twig']->render('dataurl.html.twig');
        });
$fu->get("/cris2", function(Application $app) {
            return $app['twig']->render('dataurl2.html.twig');
        });

$fu->post("handleUploads", function(Application $app, Request $request) {

            $uploader = $app['fu'];

            // Specify the list of valid extensions, ex. array("jpeg", "xml", "bmp")
            $uploader->allowedExtensions = $app['fu.allowed_ext'];
            // Specify max file size in bytes.
            $uploader->sizeLimit = $app['fu.size_limit'];
            // Specify the input name set in the javascript.
            $uploader->inputName = $app['fu.input_name'];
            // If you want to use resume feature for uploader, specify the folder to save parts.
            $uploader->chunksFolder = $app['fu.chunks_dir'];
            // Call handleUpload() with the name of the folder, relative to PHP's getcwd()
            $result = $uploader->handleUpload($app['fu.upload_dir'], $request->get('dest_file_name'));

            // To save the upload with a specified name, set the second parameter.
            // $result = $uploader->handleUpload('uploads/', md5(mt_rand()).'_'.$uploader->getName());
            // To return a name used for uploaded file you can use the following line.
            $result['uploadName'] = $uploader->getUploadName();

            header("Content-Type: text/plain");
            return json_encode($result);
        });



$fu->post("upload-and-resize", function(Application $app, Request $request) {

            $uploader = $app['fu'];

            // Specify the list of valid extensions, ex. array("jpeg", "xml", "bmp")
            $uploader->allowedExtensions = $app['fu.allowed_ext'];
            // Specify max file size in bytes.
            $uploader->sizeLimit = $app['fu.size_limit'];
            // Specify the input name set in the javascript.
            $uploader->inputName = $app['fu.input_name'];
            // If you want to use resume feature for uploader, specify the folder to save parts.
            $uploader->chunksFolder = $app['fu.chunks_dir'];
            // get original name
            $original_name = $request->files->get($uploader->inputName)->getClientOriginalName();
            $original_ext = $request->files->get($uploader->inputName)->getClientOriginalExtension();
            // Call handleUpload() with the name of the folder, relative to PHP's getcwd()
            $result = $uploader->handleUpload($app['fu.upload_dir'], md5(uniqid()) . '.' . $original_ext);

            if ($result['success'] == 1) {
                // To save the upload with a specified name, set the second parameter.
                // $result = $uploader->handleUpload('uploads/', md5(mt_rand()).'_'.$uploader->getName());
                // To return a name used for uploaded file you can use the following line.
                $result['uploadName'] = $uploader->getUploadName();

                // step 1: make a copy of the original
                $filePath = $app['fu.upload_dir'] . $result['uploadName'];
                $parts = explode(".", $result['uploadName']);
                $suffix = array_pop($parts);
                $copyName = implode(".", $parts) . '_FULLSIZE' . "." . $suffix;
                copy($filePath, $app['fu.upload_dir'] . $copyName);

                // Step 2: Scale down or up this image so it fits in the browser nicely, lets say 500px is safe 
                $image = $app['imagine']->open($filePath);
                $image
                        ->resize($image->getSize()->widen(500))
                        ->save($filePath);
            }

            header("Content-Type: application/json");
            return json_encode($result);
        });


/*
 * 1) delete the resized image from upload, we will only be working with the full size
 * 2) compute new coordinates of full size image
 * 3) crop full size image
 */
$fu->post("crop", function(Application $app, Request $request) {
            $images = $request->get('imgcrop');
            $result = array();
            foreach ($images as $img) {
                // 1)
                $name = $img['filename'];
                $parts = explode(".", $name);
                $ext = array_pop($parts);
                $resizedName = implode('.', $parts) . '_FULLSIZE' . '.' . $ext;
                $image = $app['imagine']->open($app['fu.upload_dir'] . $resizedName);
                $app['monolog']->addInfo(sprintf('orimga width %d', $image->getSize()->getWidth()));
                $percentChange = $image->getSize()->getWidth() / 500;

                unset($image);
                unlink($app['fu.upload_dir'] . $name);
                rename($app['fu.upload_dir'] . $resizedName, $app['fu.upload_dir'] . $name);
                // 2)   
                $image = $app['imagine']->open($app['fu.upload_dir'] . $name);
                $cropBox = new Box($img['w'], $img['h']);
                $app['monolog']->addInfo(sprintf('cropbox %d, %d', $cropBox->getWidth(), $cropBox->getHeight()));
                $cropBox = $cropBox->scale($percentChange);
                //$cropBox->widen($img['w'] * $percentChange);
                $app['monolog']->addInfo(sprintf('cropbox2 %d, %d', $cropBox->getWidth(), $cropBox->getHeight()));
                $cropPoint = new Point($img['x'] * $percentChange, $img['y'] * $percentChange);
                $app['monolog']->addInfo(sprintf('Percente change %f', $percentChange));
                // 3)
                //$app['imagine']
                //        ->open($app['fu.upload_dir'].$name)
                $app['monolog']->addInfo(sprintf('cropoint %d, %d', $cropPoint->getX(), $cropPoint->getY()));
                unlink($app['fu.upload_dir'] . $name);
                $image
                        ->crop($cropPoint, $cropBox)
                        ->save($app['fu.upload_dir'] . $name);

                // build result
                $result[] = array(
                    'filename' => 'crop_' . $img['filename']
                );
            }
            header("Content-Type: application/json");
            return json_encode($result);
        });


$fu->post("crop-image", function(Application $app, Request $request) {

            $uploader = $app['fu'];

            // Specify the list of valid extensions, ex. array("jpeg", "xml", "bmp")
            $uploader->allowedExtensions = $app['fu.allowed_ext'];
            // Specify max file size in bytes.
            $uploader->sizeLimit = $app['fu.size_limit'];
            // Specify the input name set in the javascript.
            $uploader->inputName = $app['fu.input_name'];
            // If you want to use resume feature for uploader, specify the folder to save parts.
            $uploader->chunksFolder = $app['fu.chunks_dir'];
            // get original name
            $original_name = $request->files->get($uploader->inputName)->getClientOriginalName();
            $original_ext = $request->files->get($uploader->inputName)->getClientOriginalExtension();
            // Call handleUpload() with the name of the folder, relative to PHP's getcwd()
            $result = $uploader->handleUpload($app['fu.upload_dir'], md5(uniqid()) . '.' . $original_ext);

            if ($result['success'] == 1) {
                // To save the upload with a specified name, set the second parameter.
                // $result = $uploader->handleUpload('uploads/', md5(mt_rand()).'_'.$uploader->getName());
                // To return a name used for uploaded file you can use the following line.
                $result['uploadName'] = $uploader->getUploadName();

                // get crop data
                $cd = array(
                    "x" => $request->get('x'),
                    "y" => $request->get('y'),
                    "w" => $request->get('w'),
                    "h" => $request->get('h'),
                    "rw" => $request->get('rw'),
                    "wf" => $request->get('wf'),
                    "hf" => $request->get('hf'),
                );
                $app['monolog']->addDebug(sprintf('crop data %s', print_r($cd, true)));

                $image = $app['imagine']->open($app['fu.upload_dir'] . $result['uploadName']);
                $app['monolog']->addDebug(sprintf('orimga width %d', $image->getSize()->getWidth()));
                // calculate scale ratio
                $percentChange = $image->getSize()->getWidth() / $cd['rw'];
                $app['monolog']->addDebug(sprintf('Percente change %f', $percentChange));
                
                // build box and point for crop
                $cropBox = new Box($cd['w'], $cd['h']);
                $app['monolog']->addDebug(sprintf('cropbox %d, %d', $cropBox->getWidth(), $cropBox->getHeight()));
                $cropBox = $cropBox->scale($percentChange);
                $app['monolog']->addDebug(sprintf('cropbox2 %d, %d', $cropBox->getWidth(), $cropBox->getHeight()));
                
                // if cropbox is different from real size
                if ($cropBox->getWidth() != $image->getSize()->getWidth() || $cropBox->getHeight() != $image->getSize()->getHeight()) {
                    $cropPoint = new Point($cd['x'] * $percentChange, $cd['y'] * $percentChange);
                    $app['monolog']->addDebug(sprintf('croppoint %d, %d', $cropPoint->getX(), $cropPoint->getY()));
                    // finally crop
                    $image->crop($cropPoint, $cropBox);
                }
                
                // finally resize to desired dimension
                if (isset($app['fu.final_width'])) { 
                    $image->resize($image->getSize()->widen($app['fu.final_width']));
                }
                
                // save image
                $image->save($app['fu.upload_dir'] . $result['uploadName']);
            }
            
            header("Content-Type: application/json");
            return json_encode($result);
        });

$app->mount('/fu', $fu);
