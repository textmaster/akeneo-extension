<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Akeneo\Pim\Enrichment\Component\Product\Model\EntityWithValuesInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Tool\Component\StorageUtils\Saver\BulkSaverInterface;
use DateTimeZone;
use LogicException;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;
use Pim\Bundle\TextmasterBundle\Updater\ProductModelUpdater;
use Pim\Bundle\TextmasterBundle\Updater\ProductUpdater;
use Pim\Bundle\TextmasterBundle\Updater\UpdaterInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;
use Textmaster\Model\DocumentInterface as ApiDocumentInterface;
use Throwable;

/**
 * Class UpdateProductsCommand.
 *
 * @package Pim\Bundle\TextmasterBundle\Command
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class UpdateProductsSubCommand extends ContainerAwareCommand
{
    use LockableTrait;
    use CommandTrait;

    public const PROJECT_ID_ARGUMENT = 'projectId';

    protected const PRODUCTS_BATCH_SIZE = 20;
    protected const DOCUMENTS_BATCH_SIZE = 50;

    /** @var ProductModelUpdater */
    protected $productModelUpdater;

    /** @var ProductUpdater */
    protected $productUpdater;

    /** @var BulkSaverInterface */
    protected $productSaver;

    /** @var BulkSaverInterface */
    protected $productModelSaver;

    /** @var array EntityWithValueInterface[] */
    protected $products = [];
    /** @var array EntityWithValueInterface[] */
    protected $productModels = [];
    /** @var array DocumentInterface[] */
    protected $documents = [];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:textmaster:update-products-sub')
            ->setDescription('Finalize project into textmaster.')
            ->addArgument(
                self::PROJECT_ID_ARGUMENT,
                InputArgument::REQUIRED,
                'The project id to update product'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        if (false === $this->lock()) {
            $this->writeMessage(
                sprintf('The command "%s" is still running in another process.', self::$defaultName)
            );

            return;
        }

        try {
            /** @var ProjectInterface $project */
            $project = $this->getProjectManager()->getProjectById($input->getArgument(self::PROJECT_ID_ARGUMENT));
            $this->writeMessage(
                sprintf(
                    'Update products for project %s with Textmaster id %s.',
                    $project->getId(),
                    $project->getTextmasterProjectId()
                )
            );
            $this->updateProducts($project);
        } catch (Throwable $exception) {
            if ($exception instanceof LogicException && Response::HTTP_NOT_FOUND === $exception->getCode()) {
                $this->getDocumentManager()->deleteDocumentsByProjectIds([$project->getId()]);
                $this->getProjectManager()->deleteProjectsByIds([$project->getId()]);

                $this->writeMessage(
                    sprintf(
                        'Project %s with Textmaster id %s not found in Textmaster. the project was deleted in Akeneo',
                        $project->getId(),
                        $project->getTextmasterProjectId()
                    )
                );

                $this->getObjectDetacher()->detach($project);
            } else {
                $this->release();
                throw $exception;
            }
        }

        $this->release();
    }

    /**
     * @param ProjectInterface $project
     *
     * @return ProjectInterface
     */
    protected function updateProducts(ProjectInterface $project): ProjectInterface
    {
        $apiTemplate   = $this->getApiTemplateById($project->getApiTemplateId());
        $pimLocaleCode = $this->getLocaleProvider()->getPimLocaleCode($apiTemplate['language_to']);

        $apiDocuments = $this->getApiDocumentsByProject($project, $this->getDocumentsFilters($project));

        /** @var ApiDocumentInterface $apiDocument */
        foreach ($apiDocuments as $apiDocument) {
            $document = $this->getDocumentManager()->getDocumentByTextmasterId($apiDocument->getId());

            /** @var EntityWithValuesInterface $product */
            $product = $this->getUpdater($apiDocument)->update($apiDocument, $pimLocaleCode);

            $this->writeMessage(
                sprintf(
                    'Updated document %s for locale %s',
                    $apiDocument->getTitle(),
                    $pimLocaleCode
                )
            );

            if ($product instanceof ProductInterface) {
                $this->products[] = $product;
            } else {
                $this->productModels[] = $product;
            }

            $document->setStatus($apiDocument->getStatus());
            $this->documents[] = $document;

            unset($apiDocument);
        }

        $this->saveData(true);

        /** @var \Pim\Bundle\TextmasterBundle\Project\Model\ProjectInterface $apiProject */
        $apiProject = $this->getWebApiRepository()->getProject($project->getTextmasterProjectId());

        $project->setTextmasterStatus($apiProject->getStatus());
        $this->getProjectManager()->saveProject($project);
        $this->getObjectDetacher()->detach($project);
        unset($apiProject);

        return $project;
    }

    /**
     * Save products, product models or documents per bundle.
     *
     * @param bool $forceSave
     */
    protected function saveData(bool $forceSave = false): void
    {
        if (true === $forceSave || count($this->products) >= self::PRODUCTS_BATCH_SIZE) {
            $this->saveProducts();
        }

        if (true === $forceSave || count($this->productModels) >= self::PRODUCTS_BATCH_SIZE) {
            $this->saveProductModels();
        }

        if (true === $forceSave || count($this->documents) >= self::DOCUMENTS_BATCH_SIZE) {
            $this->getDocumentManager()->saveDocuments($this->documents);
        }
    }

    /**
     * Retrieve document filter.
     *
     * @param ProjectInterface $project
     *
     * @return array
     */
    protected function getDocumentsFilters(ProjectInterface $project): array
    {
        $filters = [
            'status' => [
                '$in' => [
                    ApiDocumentInterface::STATUS_IN_CREATION,
                    ApiDocumentInterface::STATUS_IN_PROGRESS,
                    ApiDocumentInterface::STATUS_WAITING_ASSIGNMENT,
                    ApiDocumentInterface::STATUS_IN_REVIEW,
                    ApiDocumentInterface::STATUS_COMPLETED,
                ],
            ],
        ];

        $updatedDate = $project->getUpdatedAt();
        if (null !== $updatedDate) {
            $updatedFilter         = $updatedDate->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            $filters['updated_at'] = ['$gt' => $updatedFilter];
        }

        return $filters;
    }

    /**
     * Retrieve product updater or product model updater using document title.
     *
     * @param ApiDocumentInterface $document
     *
     * @return UpdaterInterface
     */
    protected function getUpdater(ApiDocumentInterface $document): UpdaterInterface
    {
        if (strrpos($document->getTitle(), 'product_model|') !== false) {
            return $this->getProductModelUpdater();
        }

        return $this->getProductUpdater();
    }

    /**
     * @return UpdaterInterface
     */
    protected function getProductModelUpdater(): UpdaterInterface
    {
        if (null === $this->productModelUpdater) {
            $this->productModelUpdater = $this->getContainer()->get('pim_textmaster.updater.document.product_model');
        }

        return $this->productModelUpdater;
    }

    /**
     * @return UpdaterInterface
     */
    protected function getProductUpdater(): UpdaterInterface
    {
        if (null === $this->productUpdater) {
            $this->productUpdater = $this->getContainer()->get('pim_textmaster.updater.document.product');
        }

        return $this->productUpdater;
    }

    /**
     * Save products
     *
     */
    protected function saveProducts(): void
    {
        if (null === $this->productSaver) {
            $this->productSaver = $this->getContainer()->get('pim_catalog.saver.product');
        }

        $this->productSaver->saveAll($this->products);
        $this->getObjectDetacher()->detachAll($this->products);
        $this->products = [];
    }

    /**
     * Save product models.
     *
     */
    protected function saveProductModels(): void
    {
        if (null === $this->productModelSaver) {
            $this->productModelSaver = $this->getContainer()->get('pim_catalog.saver.product_model');
        }

        $this->productModelSaver->saveAll($this->productModels);
        $this->getObjectDetacher()->detachAll($this->productModels);
        $this->productModels = [];
    }

    /**
     * Save documents
     */
    protected function saveDocuments(): void
    {
        $this->getDocumentManager()->saveDocuments($this->documents);
        $this->getObjectDetacher()->detachAll($this->documents);
        $this->documents = [];
    }
}
