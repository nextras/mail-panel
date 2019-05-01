<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

const TEMP_DIR = __DIR__ . '/../temp';

Tester\Environment::setup();
Tester\Helpers::purge(TEMP_DIR);
