<?php

namespace Pim\Bundle\TextmasterBundle;

use Pim\Bundle\TextmasterBundle\DependencyInjection\Compiler\OroConfigCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PimTextmasterBundle extends Bundle
{
    /** @var string */
    protected $parentBundle;

    /**
     * @param string $parentBundle
     * @deprecated The parentBundle parameter will be removed for PIM 1.6
     */
    public function __construct($parentBundle = 'PimEnrichBundle')
    {
        $this->parentBundle = $parentBundle;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->parentBundle;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new OroConfigCompilerPass());
    }
}
