<?php

$app['fu.upload_dir'] = __DIR__.'/../web/uploads/';
$app['fu.chunks_dir'] = __DIR__.'/../web/uploads/chunks/';
$app['fu.size_limit'] = 2 * 1024 * 1024;
$app['fu.input_name'] = 'qqfile';
$app['fu.allowed_ext'] = array();//array('jpeg', 'jpg', 'png', 'gif');