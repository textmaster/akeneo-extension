<?php

namespace Pim\Bundle\TextmasterBundle\Entity;

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

    /** @var string */
    protected $projectIdentifier;

    /** @var string */
    protected $productIdentifier;

    /** @var string */
    protected $productLabel;

    /** @var string */
    protected $language;

    /** @var string */
    protected $status;

    /** @var \DateTime */
    protected $updatedAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getProjectIdentifier(): string
    {
        return $this->projectIdentifier;
    }

    /**
     * @param string $projectIdentifier
     *
     * @return DocumentInterface
     */
    public function setProjectIdentifier(string $projectIdentifier): DocumentInterface
    {
        $this->projectIdentifier = $projectIdentifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getProductIdentifier(): string
    {
        return $this->productIdentifier;
    }

    /**
     * @param string $productIdentifier
     *
     * @return DocumentInterface
     */
    public function setProductIdentifier(string $productIdentifier): DocumentInterface
    {
        $this->productIdentifier = $productIdentifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getProductLabel(): string
    {
        return $this->productLabel;
    }

    /**
     * @param string $productLabel
     *
     * @return DocumentInterface
     */
    public function setProductLabel(string $productLabel): DocumentInterface
    {
        $this->productLabel = $productLabel;

        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     *
     * @return DocumentInterface
     */
    public function setLanguage(string $language): DocumentInterface
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return DocumentInterface
     */
    public function setStatus(string $status): DocumentInterface
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return DocumentInterface
     */
    public function setUpdatedAt($updatedAt): DocumentInterface
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
