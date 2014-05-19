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
 * Md5 job
 */
class Md5Job extends Job
{
    /**
     * {@inheritdoc}
     */
    public function perform($args)
    {
        for ($i = 0; $i < 200000; $i++) {
            md5($i);
        }
    }
}
