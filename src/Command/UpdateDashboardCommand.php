<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Akeneo\Bundle\StorageUtilsBundle\Doctrine\Common\Detacher\ObjectDetacher;
use Akeneo\Bundle\StorageUtilsBundle\Doctrine\Common\Saver\BaseSaver;
use Pim\Bundle\CatalogBundle\Doctrine\ORM\Repository\ProductRepository;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;
use Pim\Bundle\TextmasterBundle\Doctrine\Repository\DocumentRepository;
use Pim\Bundle\TextmasterBundle\Entity\Document;
use Pim\Bundle\TextmasterBundle\Locale\LocaleFinder;
use Pim\Bundle\TextmasterBundle\Model\DocumentInterface as PimDocumentInterface;
use Pim\Bundle\TextmasterBundle\Project\Model\Project;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Textmaster\Model\DocumentInterface;

/**
 * Class UpdateProjectsCommand.
 *
 * @package Pim\Bundle\TextmasterBundle\Command
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class UpdateDashboardCommand extends ContainerAwareCommand
{
    /** @var OutputInterface */
    private $output;

    /** @var DocumentRepository */
    protected $documentRepository;

    /** @var WebApiRepository */
    protected $webApiRepository;

    /** @var ProductRepository */
    protected $productRepository;

    /** @var LocaleFinder */
    protected $localeFinder;

    /** @var BaseSaver */
    protected $documentSaver;

    /** @var ObjectDetacher */
    protected $objectDetacher;

    /** @var LoggerInterface */
    protected $logger;

    /** @var array */
    protected $pimLocaleCodes;

    /** @var array */
    protected $productIdentifiers = [];

    /** @var array */
    protected $statusLabels = [];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:textmaster:update-dashboard')
            ->setDescription('Update dashboard data from textmaster');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        // Random delay to start to not overload TextMaster servers at the same time
        $sleepTime = rand(1, 150);
        $this->writeMessage(sprintf('Sleep for %d seconds', $sleepTime));
        sleep($sleepTime);

        $documents = $projectIds = [];

        /** @var Project $projectModel */
        foreach ($this->getProjectsFromApi() as $projectModel) {
            $documents = \array_merge($documents, $this->getProjectDocuments($projectModel));

            $projectIds[] = $projectModel->getId();
        }

        $this->saveDocuments($documents);
        $this->detachObjects($documents);
        $this->documentRepository->removeCompletedDocuments($projectIds);
    }

    /**
     * Retrieve project from API.
     *
     * @return \Pim\Bundle\TextmasterBundle\Project\Model\ProjectInterface[]
     */
    protected function getProjectsFromApi(): array
    {
        $filters = [
//            'archived' => false,
        ];

        return $this->getWebApiRepository()->getProjects($filters);
    }

    /**
     * @param Project $projectModel
     *
     * @return Document[]
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getProjectDocuments(Project $projectModel): array
    {
        $documents = [];
        $apiDocuments = $this->getWebApiRepository()->getDocuments(
            $this->getDocumentsFilters(), $projectModel->getId()
        );

        /** @var DocumentInterface $documentModel */
        foreach ($apiDocuments as $documentModel) {
            $productIdentifier = $documentModel->getTitle();

            if (false === $this->isProductExist($productIdentifier)) {
                continue;
            }

            $pimDocument = $this->findOrCreateDocument($projectModel->getId(), $documentModel->getTitle());
            $pimDocument->setProjectIdentifier($projectModel->getId());
            $pimDocument->setProductIdentifier($documentModel->getTitle());
            $pimDocument->setProductLabel(sprintf('[%s]', $documentModel->getTitle()));
            $pimDocument->setUpdatedAt($documentModel->getUpdatedAt());
            $pimDocument->setLanguage($projectModel->getLanguageTo());
            $pimDocument->setStatus($this->formatStatusLabel($documentModel->getStatus()));

            $documents[] = $pimDocument;
        }

        return $documents;
    }

    /**
     * @param string $message
     */
    protected function writeMessage($message)
    {
        $this->output->writeln(sprintf('%s - %s', date('Y-m-d H:i:s'), trim($message)));
    }

    /**
     * @return object|DocumentRepository
     */
    protected function getDocumentRepository()
    {
        if (null === $this->documentRepository) {
            $this->documentRepository = $this->getContainer()->get('pim_textmaster.repository.document');
        }

        return $this->documentRepository;
    }

    /**
     * @param string $projectIdentifier
     * @param string $productIdentifier
     *
     * @return PimDocumentInterface
     */
    protected function findOrCreateDocument(string $projectIdentifier, string $productIdentifier): PimDocumentInterface
    {
        $document = $this->getDocumentRepository()->findOneByProjectAndProductIdentifiers(
            $projectIdentifier, $productIdentifier
        );

        if (null === $document) {
            $document = new Document();
        }

        return $document;
    }

    /**
     * @return object|WebApiRepository
     */
    protected function getWebApiRepository()
    {
        if (null === $this->webApiRepository) {
            $this->webApiRepository = $this->getContainer()->get('pim_textmaster.repository.webapi');
        }

        return $this->webApiRepository;
    }

    /**
     * Check if product exists in pim.
     *
     * @param string $productIdentifier
     *
     * @return bool
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function isProductExist(string $productIdentifier): bool
    {
        if (\in_array($productIdentifier, $this->productIdentifiers)) {
            return true;
        }

        if (null === $this->productRepository) {
            $this->productRepository = $this->getContainer()->get('pim_catalog.repository.product');
        }

        $queryBuilder = $this->productRepository->createQueryBuilder('p');

        $countProduct = $queryBuilder->select('COUNT(p.id)')
            ->where($queryBuilder->expr()->eq('p.identifier', ':productIdentifier'))
            ->setParameter('productIdentifier', $productIdentifier)
            ->getQuery()
            ->getSingleScalarResult();

        if ($countProduct > 0) {
            $this->productIdentifiers[] = $productIdentifier;

            return true;
        }

        return false;
    }

    /**
     * @return LocaleFinder
     */
    protected function getLocaleFinder(): LocaleFinder
    {
        if (null === $this->localeFinder) {
            $this->localeFinder = $this->getContainer()->get('pim_textmaster.locale.finder');
        }

        return $this->localeFinder;
    }

    /**
     * @param array $documents
     */
    protected function saveDocuments(array $documents): void
    {
        if (null === $this->documentSaver) {
            $this->documentSaver = $this->getContainer()->get('pim_textmaster.saver.document');
        }

        $this->documentSaver->saveAll($documents);
    }

    /**
     * @param array $objects
     */
    protected function detachObjects(array $objects): void
    {
        if (null === $this->objectDetacher) {
            $this->objectDetacher = $this->getContainer()->get('akeneo_storage_utils.doctrine.object_detacher');
        }

        $this->objectDetacher->detachAll($objects);
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        if (null === $this->logger) {
            $this->logger = $this->getContainer()->get('monolog.logger.textmaster');
        }

        return $this->logger;
    }

    /**
     *
     * @return array
     */
    protected function getDocumentsFilters(): array
    {
        $filters = [
            'status' => [
                '$in' => [
                    DocumentInterface::STATUS_IN_CREATION,
                    DocumentInterface::STATUS_IN_PROGRESS,
                    DocumentInterface::STATUS_WAITING_ASSIGNMENT,
                    DocumentInterface::STATUS_IN_REVIEW,
                    DocumentInterface::STATUS_COMPLETED,
                    DocumentInterface::STATUS_INCOMPLETE,
                    DocumentInterface::STATUS_PAUSED,
                    DocumentInterface::STATUS_CANCELED,
                    DocumentInterface::STATUS_COPYSCAPE,
                    DocumentInterface::STATUS_COUNTING_WORDS,
                    DocumentInterface::STATUS_QUALITY,
                ],
            ],
        ];

        return $filters;
    }

    /**
     * @param string $statusCode
     *
     * @return string
     */
    protected function formatStatusLabel(string $statusCode): string
    {
        if (!\array_key_exists($statusCode, $this->statusLabels)) {
            $statusLabel = '';
            $explodedStatus = explode('_', $this->mergeStatus($statusCode));

            foreach ($explodedStatus as $partStatusCode) {
                $statusLabel .= sprintf(' %s', ucfirst($partStatusCode));
            }

            $this->statusLabels[$statusCode] = trim($statusLabel);
        }

        return $this->statusLabels[$statusCode];
    }

    /**
     * Merge status code for grid.
     * All status are not necessary for datagrid but need to be count.
     *
     * @param string $statusCode
     *
     * @return string
     */
    protected function mergeStatus(string $statusCode): string
    {
        switch ($statusCode) {
            case DocumentInterface::STATUS_INCOMPLETE:
            case DocumentInterface::STATUS_COPYSCAPE:
            case DocumentInterface::STATUS_COUNTING_WORDS:
            case DocumentInterface::STATUS_QUALITY:
                $statusCode = DocumentInterface::STATUS_IN_PROGRESS;
                break;
            default:
                break;
        }

        return $statusCode;
    }
}
