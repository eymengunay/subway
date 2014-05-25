# Subway

[![Build Status](https://travis-ci.org/eymengunay/subway.svg)](https://travis-ci.org/eymengunay/subway)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/eymengunay/subway/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/eymengunay/subway/?branch=master)
[![Coverage Status](https://img.shields.io/coveralls/eymengunay/subway.svg)](https://coveralls.io/r/eymengunay/subway)
[![Total Downloads](https://poser.pugx.org/eo/subway/downloads.png)](https://packagist.org/packages/eo/subway)
[![Latest Stable Version](https://poser.pugx.org/eo/subway/v/stable.png)](https://packagist.org/packages/eo/subway)

Nuclear reactor powered background job processing for PHP with resque compatible db structure.

## Features
* Delayed jobs
* Repeating jobs
* Resque compatible db
* Configurable logging

## Requirements
* A POSIX-oriented operating system (No windows support due to pcntl dependency)
* [PHP](http://php.net) >= 5.4 (with [pcntl](http://php.net/manual/en/book.pcntl.php))
* [Redis](http://redis.io)

## Installation

Add Subway in your composer.json:
```
{
    "require": {
        "eo/subway": "dev-master"
    }
}
```

Now tell composer to download the bundle by running the command:
```
$ php composer.phar update eo/subway
```
Composer will install everything into your project's vendor directory.

## Usage

### Creating job classes

```
<?php

use Subway\Job;

class MyAwesomeJob extends Job
{
    public function perform($args)
    {
        // do something here
    }
}

```

### Queueing jobs

```
<?php

use Predis\Client;
use Subway\Factory;

$redis  = new Client();
$subway = new Factory($redis);

$message = new Message('default', 'Subway\Tests\Job\Md5Job', array('hello' => 'world'));
$id = $this->factory->enqueue($message);

echo "Job $id enqueued!";
```

### Executing jobs

To execute jobs you can either use the binary file distributed with this library (see `bin` directory) or download the latest .phar archive from: http://eymengunay.github.io/subway/downloads/subway.phar

Once you have located the binary or downloaded .phar archive start your worker using the following command:

```
php subway.phar worker
```

To see all available options and arguments see command help:

```
php subway.phar worker -h
```

## Reporting an issue or a feature request

Issues and feature requests related to this library are tracked in the Github issue tracker: https://github.com/eymengunay/subway/issues