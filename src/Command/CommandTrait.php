<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Akeneo\Tool\Component\StorageUtils\Detacher\BulkObjectDetacherInterface;
use Akeneo\Tool\Component\StorageUtils\Detacher\ObjectDetacherInterface;
use Exception;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;
use Pim\Bundle\TextmasterBundle\Manager\DocumentManager;
use Pim\Bundle\TextmasterBundle\Manager\ProjectManager;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;
use Pim\Bundle\TextmasterBundle\Builder\ProjectBuilderInterface;
use Pim\Bundle\TextmasterBundle\Provider\LocaleProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Textmaster\Model\DocumentInterface as ApiDocumentInterface;

/**
 * Trait CommandTrait
 *
 * @package Pim\Bundle\TextmasterBundle\Command
 */
trait CommandTrait
{
    /** @var WebApiRepository */
    protected $webApiRepository;

    /** @var ProjectManager */
    protected $projectManager;

    /** @var DocumentManager */
    protected $documentManager;

    /** @var ObjectDetacherInterface|BulkObjectDetacherInterface */
    protected $objectDetacher;

    /** @var ProjectBuilderInterface */
    protected $builder;

    /** @var LocaleProvider */
    protected $localeProvider;

    /** @var array */
    protected $apiTemplates;

    /** @var OutputInterface */
    protected $output;

    /**
     * Run Command method.
     *
     * @param InputInterface $input
     * @param string         $command
     * @param array          $arguments
     */
    protected function runCommand(InputInterface $input, string $command, array $arguments = []): void
    {
        $command   = $this->getApplication()->find($command);

        $input = new ArrayInput(array_merge(['--env' => $input->getOption('env')], $arguments));
        $command->run($input, $this->output);
    }

    /**
     * @return WebApiRepository
     */
    protected function getWebApiRepository(): WebApiRepository
    {
        if (null === $this->webApiRepository) {
            $this->webApiRepository = $this->getContainer()->get('pim_textmaster.repository.webapi');
        }

        return $this->webApiRepository;
    }

    /**
     * @return ProjectManager
     */
    protected function getProjectManager(): ProjectManager
    {
        if (null === $this->projectManager) {
            $this->projectManager = $this->getContainer()->get('pim_textmaster.manager.project');
        }

        return $this->projectManager;
    }

    /**
     * @return DocumentManager
     */
    protected function getDocumentManager(): DocumentManager
    {
        if (null === $this->documentManager) {
            $this->documentManager = $this->getContainer()->get('pim_textmaster.manager.document');
        }

        return $this->documentManager;
    }

    /**
     * @return BulkObjectDetacherInterface|ObjectDetacherInterface
     */
    public function getObjectDetacher()
    {
        if (null === $this->objectDetacher) {
            $this->objectDetacher = $this->getContainer()->get('akeneo_storage_utils.doctrine.object_detacher');
        }

        return $this->objectDetacher;
    }

    /**
     * @return ProjectBuilderInterface
     */
    protected function getBuilder(): ProjectBuilderInterface
    {
        if (null === $this->builder) {
            $this->builder = $this->getContainer()->get('pim_textmaster.builder.project');
        }

        return $this->builder;
    }

    /**
     * @return LocaleProvider
     */
    public function getLocaleProvider(): LocaleProvider
    {
        if (null === $this->localeProvider) {
            $this->localeProvider = $this->getContainer()->get('pim_textmaster.provider.locale');
        }

        return $this->localeProvider;
    }

    /**
     * Retrieve api template by id.
     *
     * @param string $apiTemplateId
     *
     * @return array|null
     */
    protected function getApiTemplateById(string $apiTemplateId): ?array
    {
        if (null === $this->apiTemplates) {
            $this->apiTemplates = $this->getWebApiRepository()->getApiTemplates();
        }

        return isset($this->apiTemplates[$apiTemplateId]) ? $this->apiTemplates[$apiTemplateId] : null;
    }

    /**
     * Retrieve api document related to project.
     *
     * @param ProjectInterface $project
     * @param array            $filters
     *
     * @return ApiDocumentInterface[]
     */
    protected function getApiDocumentsByProject(ProjectInterface $project, array $filters): array
    {
        return $this
            ->getWebApiRepository()
            ->getAllDocuments(
                $filters,
                $project->getTextmasterProjectId()
            );
    }

    /**
     * @param string $message
     */
    protected function writeMessage($message): void
    {
        $this->output->writeln(sprintf('%s - %s', date('Y-m-d H:i:s'), trim($message)));
    }
}