<?php

namespace Pim\Bundle\TextmasterBundle\Entity;

use DateTime;
use Pim\Bundle\TextmasterBundle\Model\DocumentInterface;

/**
 * Class Document.
 *
 * @package Pim\Bundle\TextmasterBundle\Entity
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class Document implements DocumentInterface
{
    /** @var int */
    protected $id;

    /** @var int */
    protected $projectId;

    /** @var string */
    protected $textmasterDocumentId;

    /** @var int */
    protected $productId;

    /** @var string */
    protected $productIdentifier;

    /** @var string */
    protected $productLabel;

    /** @var string */
    protected $dataToSend;

    /** @var string */
    protected $languageFrom;

    /** @var string */
    protected $languageTo;

    /** @var string */
    protected $status;

    /** @var DateTime */
    protected $createdAt;

    /** @var DateTime */
    protected $updatedAt;

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getProjectId(): int
    {
        return $this->projectId;
    }

    /**
     * @inheritdoc
     */
    public function setProjectId(int $projectId): DocumentInterface
    {
        $this->projectId = $projectId;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTextmasterDocumentId(): string
    {
        return $this->textmasterDocumentId;
    }

    /**
     * @inheritdoc
     */
    public function setTextmasterDocumentId(string $textmasterDocumentId): DocumentInterface
    {
        $this->textmasterDocumentId = $textmasterDocumentId;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getProductId(): int
    {
        return $this->productId;
    }

    /**
     * @inheritdoc
     */
    public function setProductId(int $productId): DocumentInterface
    {
        $this->productId = $productId;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getProductIdentifier(): string
    {
        return $this->productIdentifier;
    }

    /**
     * @inheritdoc
     */
    public function setProductIdentifier(string $productIdentifier): DocumentInterface
    {
        $this->productIdentifier = $productIdentifier;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getProductLabel(): string
    {
        return $this->productLabel;
    }

    /**
     * @inheritdoc
     */
    public function setProductLabel(string $productLabel): DocumentInterface
    {
        $this->productLabel = $productLabel;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDataToSend(): string
    {
        return $this->dataToSend;
    }

    /**
     * @inheritdoc
     */
    public function setDataToSend(string $dataToSend): DocumentInterface
    {
        $this->dataToSend = $dataToSend;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getLanguageFrom(): string
    {
        return $this->languageFrom;
    }

    /**
     * @inheritdoc
     */
    public function setLanguageFrom(string $languageFrom): DocumentInterface
    {
        $this->languageFrom = $languageFrom;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getLanguageTo(): string
    {
        return $this->languageTo;
    }

    /**
     * @inheritdoc
     */
    public function setLanguageTo(string $languageTo): DocumentInterface
    {
        $this->languageTo = $languageTo;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @inheritdoc
     */
    public function setStatus(string $status): DocumentInterface
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(?DateTime $createdAt): DocumentInterface
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(?DateTime $updatedAt): DocumentInterface
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
