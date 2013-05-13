<?php

namespace FU\Service\Provider;

use Silex\ServiceProviderInterface;
use FU\Service\FineUploader\qqFileUploader;

class FineUploaderProvider implements ServiceProviderInterface
{
    public function boot(\Silex\Application $app) {
        
    }

    public function register(\Silex\Application $app) 
    {
        $app['fu'] = function() {
            $uploader = new qqFileUploader();
            return $uploader;
        };
    }    
}
