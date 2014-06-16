<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Bridge;

use Subway\Bridge;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Composer bridge
 */
class ComposerBridge extends Bridge
{
    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        require_once $this->getOptions()->get('autoload');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'autoload' => 'vendor/autoload.php'
        ));
    }
}