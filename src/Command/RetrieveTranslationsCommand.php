<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Akeneo\Tool\Bundle\StorageUtilsBundle\Doctrine\Common\Remover\BaseRemover;
use Akeneo\Tool\Bundle\StorageUtilsBundle\Doctrine\Common\Saver\BaseSaver;
use Akeneo\Tool\Component\StorageUtils\Saver\BulkSaverInterface;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;
use Pim\Bundle\TextmasterBundle\Locale\LocaleFinder;
use Pim\Bundle\TextmasterBundle\Project\ProjectInterface;
use Pim\Bundle\TextmasterBundle\Updater\ProductModelUpdater;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Textmaster\Model\DocumentInterface;
use Pim\Bundle\TextmasterBundle\Updater\ProductUpdater;

/**
 * Retrieve translations and update products
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class RetrieveTranslationsCommand extends ContainerAwareCommand
{
    /** @var WebApiRepository */
    protected $webApiRepository;

    /** @var ProductModelUpdater */
    protected $productModelUpdater;

    /** @var ProductUpdater */
    protected $productUpdater;

    /** @var BulkSaverInterface */
    protected $productSaver;

    /** @var BulkSaverInterface */
    protected $productModelSaver;

    /** @var BaseSaver */
    protected $projectSaver;

    /** @var BaseRemover */
    protected $projectRemover;

    /** @var LocaleFinder */
    protected $localeFinder;

    /** @var array */
    protected $apiTemplates;

    /** @var OutputInterface */
    private $output;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pim:textmaster:retrieve-translations')
            ->setDescription('Fetch translations via TextMaster API call');
    }



    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        // Random delay to start to not overload TextMaster servers at the same time
        $sleepTime = rand(1, 300);
        $this->writeMessage(sprintf('Sleep for %d seconds', $sleepTime));
        sleep($sleepTime);

        $this->writeMessage('Check TextMaster projects');

        $pimProjects = $this->getPimProjects();

        foreach ($pimProjects as $project) {
            $this->writeMessage(sprintf('Update products for project %s', $project->getCode()));
            $this->updateProducts($project);
        }

        $this->updatePimProjects($pimProjects);
    }


    /**
     * @param ProjectInterface $project
     *
     * @return ProjectInterface
     */
    protected function updateProducts(ProjectInterface $project): ProjectInterface
    {
        $apiTemplate   = $this->getApiTemplate($project->getApiTemplateId());
        $pimLocaleCode = $this->getPimLocaleCode($apiTemplate['language_to']);

        $filters = [
            'status' => [
                '$in' => [DocumentInterface::STATUS_IN_REVIEW, DocumentInterface::STATUS_COMPLETED],
            ],
        ];

        $updatedDate = $project->getUpdatedAt();
        if (null !== $updatedDate) {
            $updatedFilter = $updatedDate->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            $filters['updated_at'] = ['$gt' => $updatedFilter];
        }

        try {
            $documents = $this->getWebApiRepository()->getAllDocuments($filters, $project->getCode());
            $project->setUpdatedAt();
            $products      = [];
            $productModels = [];

            /** @var DocumentInterface $document */
            foreach ($documents as $document) {
                $product = $this->getUpdater($document)
                    ->update($document, $pimLocaleCode);

                $this->writeMessage(
                    sprintf(
                        'Updated document %s for locale %s',
                        $document->getTitle(),
                        $pimLocaleCode
                    )
                );

                if ($product instanceof ProductInterface) {
                    $products[] = $product;
                } else {
                    $productModels[] = $product;
                }
            }

            if (!empty($products)) {
                $this->saveProducts($products);
            }

            if (!empty($productModels)) {
                $this->saveProductModels($productModels);
            }

        } catch (\Exception $e) {
            $this->writeMessage(
                sprintf(
                    '<error>Unable to update products for project %s</error> %s',
                    $project->getCode(),
                    $e->getMessage()
                )
            );
        }

        return $project;
    }


    /**
     * @param ProjectInterface[] $projects
     */
    protected function updatePimProjects(array $projects): void
    {
        $filters = [
            'status'   => [
                '$nin' => [DocumentInterface::STATUS_CANCELED, DocumentInterface::STATUS_COMPLETED],
            ],
            'archived' => false,
        ];

        $textmasterCodes = $this->getWebApiRepository()->getProjectCodes($filters);

        $this->writeMessage('Receive project codes from TextMaster API: ' . json_encode($textmasterCodes));

        foreach ($projects as $project) {
            if (\in_array($project->getCode(), $textmasterCodes)) {
                $this->saveProject($project);
            } else {
                $this->removeProject($project);
            }
        }
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
     * Retrieve product updater or product model updater using document title.
     *
     * @param DocumentInterface $document
     *
     * @return object|ProductModelUpdater|ProductUpdater
     */
    protected function getUpdater(DocumentInterface $document)
    {
        if (strrpos($document->getTitle(), 'product_model|') !== false) {
            return $this->getProductModelUpdater();
        }

        return $this->getProductUpdater();
    }

    /**
     * @return object|ProductModelUpdater
     */
    protected function getProductModelUpdater()
    {
        if (null === $this->productModelUpdater) {
            $this->productModelUpdater = $this->getContainer()->get('pim_textmaster.updater.document.product_model');
        }

        return $this->productModelUpdater;
    }

    /**
     * @return object|ProductUpdater
     */
    protected function getProductUpdater()
    {
        if (null === $this->productUpdater) {
            $this->productUpdater = $this->getContainer()->get('pim_textmaster.updater.document.product');
        }

        return $this->productUpdater;
    }

    /**
     * @param ProductInterface[] $products
     */
    protected function saveProducts(array $products): void
    {
        if (null === $this->productSaver) {
            $this->productSaver = $this->getContainer()->get('pim_catalog.saver.product');
        }

        $this->productSaver->saveAll($products);
    }

    /**
     * @param ProductModelInterface[] $productModels
     */
    protected function saveProductModels(array $productModels): void
    {
        if (null === $this->productModelSaver) {
            $this->productModelSaver = $this->getContainer()->get('pim_catalog.saver.product_model');
        }

        $this->productModelSaver->saveAll($productModels);
    }

    /**
     * @param ProjectInterface $project
     */
    protected function saveProject(ProjectInterface $project): void
    {
        if (null === $this->projectSaver) {
            $this->projectSaver = $this->getContainer()->get('pim_textmaster.saver.project');
        }

        $this->projectSaver->save($project);
        $this->writeMessage(sprintf('<info>Project %s was updated</info>', $project->getCode()));
    }

    /**
     * @param ProjectInterface $project
     */
    protected function removeProject(ProjectInterface $project): void
    {
        if (null === $this->projectRemover) {
            $this->projectRemover = $this->getContainer()->get('pim_textmaster.remover.project');
        }

        $this->projectRemover->remove($project);
        $this->writeMessage(sprintf('<info>Project %s was removed</info>', $project->getCode()));
    }

    /**
     * @param string $apiTemplateId
     *
     * @return array
     */
    protected function getApiTemplate(string $apiTemplateId): array
    {
        if (empty($this->apiTemplates)) {
            $this->apiTemplates = $this->getWebApiRepository()->getApiTemplates();
        }

        return $this->apiTemplates[$apiTemplateId];
    }

    /**
     * @param string $textmasterLocaleCode
     *
     * @return string
     */
    protected function getPimLocaleCode(string $textmasterLocaleCode): string
    {
        if (null === $this->localeFinder) {
            $this->localeFinder = $this->getContainer()->get('pim_textmaster.locale.finder');
        }

        return $this->localeFinder->getPimLocaleCode($textmasterLocaleCode);
    }

    /**
     * Retrieve PIM translation projects
     *
     * @return ProjectInterface[]
     */
    protected function getPimProjects(): array
    {
        return $this->getContainer()
            ->get('pim_textmaster.repository.project')
            ->findAll();
    }

    /**
     * @param string $message
     */
    protected function writeMessage($message): void
    {
        $this->output->writeln(sprintf('%s - %s', date('Y-m-d H:i:s'), trim($message)));
    }
}
