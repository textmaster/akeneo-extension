<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Akeneo\Pim\Enrichment\Component\Product\Model\EntityWithValuesInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Tool\Component\StorageUtils\Detacher\ObjectDetacherInterface;
use Akeneo\Tool\Component\StorageUtils\Saver\BulkSaverInterface;
use DateTimeZone;
use LogicException;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepositoryInterface;
use Pim\Bundle\TextmasterBundle\Builder\ProjectBuilderInterface;
use Pim\Bundle\TextmasterBundle\Manager\DocumentManager;
use Pim\Bundle\TextmasterBundle\Manager\ProjectManager;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;
use Pim\Bundle\TextmasterBundle\Provider\LocaleProvider;
use Pim\Bundle\TextmasterBundle\Updater\ProductModelUpdater;
use Pim\Bundle\TextmasterBundle\Updater\ProductUpdater;
use Pim\Bundle\TextmasterBundle\Updater\UpdaterInterface;
use Symfony\Component\Console\Command\Command;
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
class UpdateProductsSubCommand extends Command
{
    use LockableTrait;
    use CommandTrait;

    public const PROJECT_ID_ARGUMENT = 'projectId';

    protected const PRODUCTS_BATCH_SIZE = 20;
    protected const DOCUMENTS_BATCH_SIZE = 50;

    /**
     * @var ProductModelUpdater
     */
    protected $productModelUpdater;

    /**
     * @var ProductUpdater
     */
    protected $productUpdater;

    /**
     * @var BulkSaverInterface
     */
    protected $productSaver;

    /**
     * @var BulkSaverInterface
     */
    protected $productModelSaver;

    /**
     * @var ObjectDetacherInterface
     */
    private $objectDetacher;

    /**
     * @var ProjectManager
     */
    private $projectManager;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var WebApiRepositoryInterface
     */
    private $webApiRepository;

    /**
     * @var ProjectBuilderInterface
     */
    private $projectBuilder;

    /**
     * @var LocaleProvider
     */
    private $localeProvider;

    /**
     * @var array EntityWithValueInterface[]
     */
    protected $products = [];

    /**
     * @var array EntityWithValueInterface[]
     */
    protected $productModels = [];

    /**
     * @var array DocumentInterface[]
     */
    protected $documents = [];

    public function __construct(
        ProductUpdater $productUpdater,
        ProductModelUpdater $productModelUpdater,
        BulkSaverInterface $productSaver,
        BulkSaverInterface $productModelSaver,
        ObjectDetacherInterface $objectDetacher,
        ProjectManager $projectManager,
        DocumentManager $documentManager,
        WebApiRepositoryInterface $webApiRepository,
        LocaleProvider $localeProvider,
        ProjectBuilderInterface $projectBuilder
    ) {
        parent::__construct();

        $this->productUpdater = $productUpdater;
        $this->productModelUpdater = $productModelUpdater;
        $this->productSaver = $productSaver;
        $this->productModelSaver = $productModelSaver;
        $this->objectDetacher = $objectDetacher;
        $this->projectManager = $projectManager;
        $this->documentManager = $documentManager;
        $this->webApiRepository = $webApiRepository;
        $this->projectBuilder = $projectBuilder;
        $this->localeProvider = $localeProvider;
    }

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
            $project = $this->projectManager->getProjectById($input->getArgument(self::PROJECT_ID_ARGUMENT));
            $this->writeMessage(
                sprintf(
                    'Update products for project %s with Textmaster id %s, Akeneo Status: %s, TM status: %s',
                    $project->getId(),
                    $project->getTextmasterProjectId(),
                    $project->getAkeneoStatus(),
                    $project->getTextmasterStatus()
                )
            );
            $this->updateProducts($project);
        } catch (Throwable $exception) {
            if ($exception instanceof LogicException && Response::HTTP_NOT_FOUND === $exception->getCode()) {
                $this->documentManager->deleteDocumentsByProjectIds([$project->getId()]);
                $this->projectManager->deleteProjectsByIds([$project->getId()]);

                $this->writeMessage(
                    sprintf(
                        'Project %s with Textmaster id %s not found in Textmaster. the project was deleted in Akeneo',
                        $project->getId(),
                        $project->getTextmasterProjectId()
                    )
                );

                $this->objectDetacher->detach($project);
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
        try {
            $apiTemplate   = $this->getApiTemplateById($project->getApiTemplateId());
            $this->writeMessage(
                sprintf(
                    'Getting pim locale code, language to: %s',
                    $apiTemplate['language_to']
                )
            );
            $pimLocaleCode = $this->localeProvider->getPimLocaleCode($apiTemplate['language_to']);

            $apiDocuments = $this->getApiDocumentsByProject($project, $this->getDocumentsFilters($project));

            /** @var ApiDocumentInterface $apiDocument */
            foreach ($apiDocuments as $apiDocument) {
                $document = $this->documentManager->getDocumentByTextmasterId($apiDocument->getId());

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
            $apiProject = $this->webApiRepository->getProject($project->getTextmasterProjectId());

            $project->setTextmasterStatus($apiProject->getStatus());
            $this->projectManager->saveProject($project);
            $this->objectDetacher->detach($project);
            unset($apiProject);
        } catch (\Exception $ex) {
            $this->writeMessage(
                sprintf(
                    "An error has been occured: %s \n %s",
                    $ex->getMessage(),
                    $ex->getTraceAsString()
                )
            );
        }

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
            $this->documentManager->saveDocuments($this->documents);
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
            return $this->productModelUpdater;
        }

        return $this->productUpdater;
    }

    /**
     * Save products
     *
     */
    protected function saveProducts(): void
    {
        $this->productSaver->saveAll($this->products);
        $this->objectDetacher->detachAll($this->products);
        $this->products = [];
    }

    /**
     * Save product models.
     *
     */
    protected function saveProductModels(): void
    {
        $this->productModelSaver->saveAll($this->productModels);
        $this->objectDetacher->detachAll($this->productModels);
        $this->productModels = [];
    }

    /**
     * Save documents
     */
    protected function saveDocuments(): void
    {
        $this->documentManager->saveDocuments($this->documents);
        $this->objectDetacher->detachAll($this->documents);
        $this->documents = [];
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
            $this->apiTemplates = $this->webApiRepository->getApiTemplates();
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
            ->webApiRepository
            ->getAllDocuments(
                $filters,
                $project->getTextmasterProjectId()
            );
    }
}
