<?php


namespace Pim\Bundle\TextmasterBundle\Model;

use DateTime;

/**
 * Interface Document
 *
 * @package Pim\Bundle\TextmasterBundle\Model
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
interface DocumentInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return int
     */
    public function getProjectId(): int;

    /**
     * @param int $projectId
     *
     * @return DocumentInterface
     */
    public function setProjectId(int $projectId): DocumentInterface;

    /**
     * @return string
     */
    public function getTextmasterDocumentId(): string;

    /**
     * @param string $textmasterDocumentId
     *
     * @return DocumentInterface
     */
    public function setTextmasterDocumentId(string $textmasterDocumentId): DocumentInterface;

    /**
     * @return int
     */
    public function getProductId(): int;

    /**
     * @param int $productId
     *
     * @return DocumentInterface
     */
    public function setProductId(int $productId): DocumentInterface;

    /**
     * @return string
     */
    public function getProductIdentifier(): string;

    /**
     * @param string $productIdentifier
     *
     * @return DocumentInterface
     */
    public function setProductIdentifier(string $productIdentifier): DocumentInterface;

    /**
     * @return string
     */
    public function getProductLabel(): string;

    /**
     * @param string $productLabel
     *
     * @return DocumentInterface
     */
    public function setProductLabel(string $productLabel): DocumentInterface;

    /**
     * @return string
     */
    public function getDataToSend(): string;

    /**
     * @param string $dataToSend
     *
     * @return DocumentInterface
     */
    public function setDataToSend(string $dataToSend): DocumentInterface;

    /**
     * @return string
     */
    public function getLanguageFrom(): string;

    /**
     * @param string $languageFrom
     *
     * @return DocumentInterface
     */
    public function setLanguageFrom(string $languageFrom): DocumentInterface;

    /**
     * @return string
     */
    public function getLanguageTo(): string;

    /**
     * @param string $languageTo
     *
     * @return DocumentInterface
     */
    public function setLanguageTo(string $languageTo): DocumentInterface;

    /**
     * @return string
     */
    public function getStatus(): string;

    /**
     * @param string $status
     *
     * @return DocumentInterface
     */
    public function setStatus(string $status): DocumentInterface;

    /**
     * @return DateTime
     */
    public function getCreatedAt(): ?DateTime;

    /**
     * @param DateTime $createdAt
     *
     * @return DocumentInterface
     */
    public function setCreatedAt(?DateTime $createdAt): DocumentInterface;

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): ?DateTime;

    /**
     * @param DateTime $updatedAt
     *
     * @return DocumentInterface
     */
    public function setUpdatedAt(?DateTime $updatedAt): DocumentInterface;
}
