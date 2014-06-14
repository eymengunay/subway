<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Tests\Job;

use Subway\Job;

/**
 * Failing job
 */
class FailingJob extends Job
{
    /**
     * {@inheritdoc}
     */
    public function perform()
    {
        throw new \Exception();
    }
}
