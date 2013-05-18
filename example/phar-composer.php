<?php

use Clue\PharComposer\PharComposer;

require __DIR__ . '/../vendor/autoload.php';

$pharcomposer = new PharComposer(__DIR__ . '/../composer.json');
$pharcomposer->build();
