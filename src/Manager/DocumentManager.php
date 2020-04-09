<?php

namespace Pim\Bundle\TextmasterBundle\Manager;

use Akeneo\Tool\Component\StorageUtils\Factory\SimpleFactoryInterface;
use Akeneo\Tool\Component\StorageUtils\Saver\BulkSaverInterface;
use Akeneo\Tool\Component\StorageUtils\Saver\SaverInterface;
use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Pim\Bundle\TextmasterBundle\Doctrine\Repository\DocumentRepository;
use Pim\Bundle\TextmasterBundle\Model\DocumentInterface;
use Textmaster\Model\DocumentInterface as ApiDocumentInterface;

/**
 * Class DocumentManager.
 *
 * @package Pim\Bundle\TextmasterBundle\Manager
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class DocumentManager
{
    /** @var SimpleFactoryInterface */
    protected $factory;

    /** @var ObjectRepository|DocumentRepository */
    protected $repository;

    /** @var SaverInterface|BulkSaverInterface */
    protected $saver;

    /**
     * DocumentManager constructor.
     *
     * @param SimpleFactoryInterface              $factory
     * @param ObjectRepository|DocumentRepository $repository
     * @param BulkSaverInterface                  $saver
     */
    public function __construct(
        SimpleFactoryInterface $factory,
        DocumentRepository $repository,
        BulkSaverInterface $saver
    ) {
        $this->factory    = $factory;
        $this->repository = $repository;
        $this->saver      = $saver;
    }

    /**
     * @param int    $projectId
     * @param int    $productId
     * @param string $productIdentifier
     * @param string $productLabel
     * @param string $dataToSend
     * @param string $languageFrom
     * @param string $languageTo
     *
     * @return DocumentInterface
     */
    public function createDocument(
        int $projectId,
        int $productId,
        string $productIdentifier,
        string $productLabel,
        string $dataToSend,
        string $languageFrom,
        string $languageTo
    ): DocumentInterface {
        /** @var DocumentInterface $document */
        $document = $this->factory->create();

        return $document->setProjectId($projectId)
            ->setProductId($productId)
            ->setProductIdentifier($productIdentifier)
            ->setProductLabel($productLabel)
            ->setDataToSend($dataToSend)
            ->setLanguageFrom($languageFrom)
            ->setLanguageTo($languageTo)
            ->setStatus(ApiDocumentInterface::STATUS_IN_CREATION)
            ->setCreatedAt(new DateTime())
            ->setUpdatedAt(new DateTime());
    }

    /**
     * Save document items provided.
     *
     * @param array DocumentInterface[]
     */
    public function saveDocuments(array $documents): void
    {
        /** @var DocumentInterface $document */
        foreach ($documents as $document) {
            $document->setUpdatedAt(new DateTime());
        }

        $this->saver->saveAll($documents);
    }

    /**
     * Delete document item using project identifiers.
     *
     * @param array $projectIds
     *
     * @return int
     */
    public function deleteDocumentsByProjectIds(array $projectIds): int
    {
        return $this->repository->deleteByProjectIds($projectIds);
    }

    /**
     * Retrieve all project identifiers where akeneo status set to "to_send".
     *
     * @param string $projectId
     *
     * @return array
     */
    public function getDocumentIdsByProjectId(string $projectId): array
    {
        $akeneoDocumentIds = [];

        foreach ($this->repository->findIdsByProjectId($projectId) as $documentData) {
            $akeneoDocumentIds[] = $documentData['id'];
        }

        return $akeneoDocumentIds;
    }

    /**
     * Retrieve all project identifiers where akeneo status set to "to_send".
     *
     * @param string $projectId
     *
     * @return array
     */
    public function getDocumentIdsByProjectIdOrderByProductIds(string $projectId): array
    {
        $akeneoDocumentIds = [];

        foreach ($this->repository->findIdsAndProductIdsByProjectId($projectId) as $documentData) {
            $akeneoDocumentIds[$documentData['productId']][] = $documentData['id'];
        }

        return $akeneoDocumentIds;
    }

    /**
     * Retrieve documents by their ids.
     *
     * @param array $documentIds
     *
     * @return DocumentInterface[]
     */
    public function getDocumentsbyIds(array $documentIds): array
    {
        return $this->repository->findByIds($documentIds);
    }

    /**
     * Retrieve document by its.
     *
     * @param string $textmasterId
     *
     * @return DocumentInterface|object|null
     */
    public function getDocumentByTextmasterId(string $textmasterId): ?DocumentInterface
    {
        return $this->repository->findOneBy(['textmasterDocumentId' => $textmasterId]);
    }
}
