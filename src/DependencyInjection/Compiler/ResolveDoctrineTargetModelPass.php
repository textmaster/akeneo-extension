<?php

declare(strict_types=1);

namespace Pim\Bundle\TextmasterBundle\DependencyInjection\Compiler;

use Akeneo\Tool\Bundle\StorageUtilsBundle\DependencyInjection\Compiler\AbstractResolveDoctrineTargetModelPass;
use Pim\Bundle\TextmasterBundle\Model\DocumentInterface;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;

/**
 * Class ResolveDoctrineTargetModelPass.
 *
 * @package Pim\Bundle\TextmasterBundle\DependencyInjection\Compiler
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class ResolveDoctrineTargetModelPass extends AbstractResolveDoctrineTargetModelPass
{
    /**
     * {@inheritdoc}
     */
    protected function getParametersMapping(): array
    {
        return [
            DocumentInterface::class => 'pim_textmaster.entity.document.class',
            ProjectInterface::class  => 'pim_textmaster.entity.project.class',
        ];
    }
}
