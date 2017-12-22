<?php

require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('Europe/Prague');
Tester\Environment::setup();

define('TEMP_DIR', __DIR__ . '/_temp');

Tester\Helpers::purge(TEMP_DIR);
