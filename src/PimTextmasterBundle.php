<?php

namespace Pim\Bundle\TextmasterBundle;

use Pim\Bundle\TextmasterBundle\DependencyInjection\Compiler\OroConfigCompilerPass;
use Pim\Bundle\TextmasterBundle\DependencyInjection\Compiler\ResolveDoctrineTargetModelPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PimTextmasterBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container
            ->addCompilerPass(new OroConfigCompilerPass())
            ->addCompilerPass(new ResolveDoctrineTargetModelPass());
    }
}
