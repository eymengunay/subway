# Subway

Background job processing for PHP with resque compatible db structure.

## Features
* Delayed jobs
* Repeating jobs
* Resque compatible db
* Configurable logging

## Requirements
* A POSIX-oriented operating system (No windows support due to pcntl dependency)
* [PHP](http://php.net) >= 5.3 (with [pcntl](http://php.net/manual/en/book.pcntl.php))
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

// $id contains enqueued unique job id
$id = $subway->enqueue('queue-name', 'MyAwesomeJob', array('arg1' => 'hello', 'arg2' => 'world'));

```

## Reporting an issue or a feature request

Issues and feature requests related to this library are tracked in the Github issue tracker: https://github.com/eymengunay/subway/issues