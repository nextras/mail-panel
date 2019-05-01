<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();

define('TEMP_DIR', __DIR__ . '/tmp/' . getmypid());
@mkdir(dirname(TEMP_DIR)); // @ - directory may already exist
Tester\Helpers::purge(TEMP_DIR);
