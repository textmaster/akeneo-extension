<?php

namespace Pim\Bundle\TextmasterBundle\Command;

use Pim\Bundle\TextmasterBundle\Project\ProjectInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Textmaster\Model\DocumentInterface;

/**
 * Retrieve translations and update products
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class RetrieveTranslationsCommand extends ContainerAwareCommand
{
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

        $projects = $this->getProjects();
        foreach ($projects as $project) {
            $this->writeMessage(sprintf('Update products for project %s', $project->getCode()));
            $this->updateProducts($project);
        }
        $this->updateProjects($projects);
    }

    /**
     * @param ProjectInterface $project
     *
     * @return ProjectInterface
     */
    protected function updateProducts(ProjectInterface $project)
    {
        $pimLocaleCode = $project->getToLocale()->getCode();
        $webApiRepository = $this->getContainer()->get('pim_textmaster.repository.webapi');

        $filters = [
            'status' => [
                '$in' => [DocumentInterface::STATUS_IN_REVIEW, DocumentInterface::STATUS_COMPLETED],
            ],
            'archived' => false
        ];

        $updatedDate = $project->getUpdatedAt();
        if (null !== $updatedDate) {
            $updatedFilter = $updatedDate->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            $filters['updated_at'] = ['$gt' => $updatedFilter];
        }

        try {
            $documents = $webApiRepository->getDocuments($filters, $project->getCode());
            $project->setUpdatedAt();
            $updater = $this->getContainer()->get('pim_textmaster.document.updater');
            $products = [];
            foreach ($documents as $document) {
                $product = $updater->update($document, $pimLocaleCode);
                $this->writeMessage(sprintf('Updated document %s for locale %s', $document->getTitle(), $pimLocaleCode));
                $products[] = $product;
            }

            $saver = $this->getContainer()->get('pim_catalog.saver.product');
            $saver->saveAll($products);
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
    protected function updateProjects(array $projects)
    {
        $webApiRepository = $this->getContainer()->get('pim_textmaster.repository.webapi');

        $filters = [
            "status" => [
                '$nin' => [DocumentInterface::STATUS_CANCELED, DocumentInterface::STATUS_COMPLETED],
            ],
            'archived' => false,
        ];

        $textmasterCodes = $webApiRepository->getProjectCodes($filters);

        foreach ($projects as $project) {
            if (in_array($project->getCode(), $textmasterCodes)) {
                $saver = $this->getContainer()->get('pim_textmaster.saver.project');
                $saver->save($project);
                $this->writeMessage(sprintf('<info>Project %s was updated</info>', $project->getCode()));
            } else {
                $remover = $this->getContainer()->get('pim_textmaster.remover.project');
                $remover->remove($project);
                $this->writeMessage(sprintf('<info>Project %s was removed</info>', $project->getCode()));
            }
        }
    }

    /**
     * @return ProjectInterface[]
     */
    protected function getProjects()
    {
        $projectRepository = $this->getContainer()->get('pim_textmaster.repository.project');
        $projects = $projectRepository->findAll();

        return $projects;
    }

    /**
     * @param string $message
     */
    private function writeMessage($message)
    {
        $this->output->writeln(sprintf('%s - %s', date('Y-m-d H:i:s'), trim($message)));
    }
}
