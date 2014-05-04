<?php

/*
 * This file is part of the Subway package.
 *
 * (c) 2014 Eymen Gunay <eymen@egunay.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$loader = require_once __DIR__ . "/../vendor/autoload.php";
$loader->add('Subway\\', '../src/Subway');
$loader->add('Subway\Tests\\', 'Subway/Tests');
