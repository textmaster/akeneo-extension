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
}
