<?php

namespace Pim\Bundle\TextmasterBundle\Manager;

use Pim\Bundle\TextmasterBundle\Doctrine\Repository\DocumentRepository;

class DashboardManager
{
    /** @var DocumentRepository */
    protected $documentRepository;

    /**
     * DashboardManager constructor.
     *
     * @param DocumentRepository $documentRepository
     */
    public function __construct(
        DocumentRepository $documentRepository
    ) {
        $this->documentRepository = $documentRepository;
    }

    /**
     * Retrieve document statuses to generate dashboard percentage.
     *
     * @return array
     */
    public function getDocumentStatuses(): array
    {
        $allStatus = $this->documentRepository->findAllDocumentStatus();

        $statuses = $documentStatuses = [];

        foreach ($allStatus as $data) {
            $status = $data['status'];

            if (!isset($statuses[$status])) {
                $statuses[$status] = 1;

                continue;
            }

            $statuses[$status]++;
        }

        $statuses = $this->sortStatuses($statuses);
        $countStatuses = array_sum($statuses);

        foreach ($statuses as $statusName => $count) {
            $documentStatuses[] = [
                'name' => $statusName,
                'rate' => 0 !== $count && 0 !== $countStatuses ?
                    round($count / $countStatuses * 100, 1)
                    : 0
            ];
        }

        return $documentStatuses;
    }

    protected function sortStatuses(array $statuses)
    {
        $statusOrder = [
            'In Creation',
            'In Progress',
            'Waiting Assignment',
            'In Review',
            'Completed',
            'Incomplete',
            'Paused',
            'Copyscape',
            'Counting Words',
            'Quality',
        ];

        $sortedStatus = [];

        foreach ($statusOrder as $statusName) {
            if (isset($statuses[$statusName])) {
                $sortedStatus[$statusName] = $statuses[$statusName];
                unset($statuses[$statusName]);
            }
        }

        return array_merge($sortedStatus, $statuses);
    }
}
