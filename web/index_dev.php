<?php

require_once __DIR__.'/../vendor/autoload.php';

// instantiate app
$app = require __DIR__.'/../src/app.php';

// config
require __DIR__.'/../config/dev.php';

// fu controller
require __DIR__.'/../src/controllers/fineuploader.php';


$app->run();