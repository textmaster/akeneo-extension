<?php

namespace Pim\Bundle\TextmasterBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;

/**
 * Class Project.
 *
 * @package Pim\Bundle\TextmasterBundle\Entity
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class Project implements ProjectInterface
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $textmasterProjectId;

    /** @var string */
    protected $username;

    /** @var string */
    protected $name;

    /** @var string */
    protected $apiTemplateId;

    /** @var string */
    protected $textmasterStatus;

    /** @var string */
    protected $akeneoStatus;

    /** @var Collection */
    protected $documents;

    /** @var DateTime */
    protected $createdAt;

    /** @var DateTime */
    protected $updatedAt;

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getTextmasterProjectId(): string
    {
        return $this->textmasterProjectId;
    }

    /**
     * @inheritdoc
     */
    public function setTextmasterProjectId(string $textmasterProjectId): ProjectInterface
    {
        $this->textmasterProjectId = $textmasterProjectId;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @inheritdoc
     */
    public function setUsername(string $username): ProjectInterface
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name): ProjectInterface
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getApiTemplateId(): string
    {
        return $this->apiTemplateId;
    }

    /**
     * @inheritdoc
     */
    public function setApiTemplateId(string $apiTemplateId): ProjectInterface
    {
        $this->apiTemplateId = $apiTemplateId;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTextmasterStatus(): string
    {
        return $this->textmasterStatus;
    }

    /**
     * @inheritdoc
     */
    public function setTextmasterStatus(string $textmasterStatus): ProjectInterface
    {
        $this->textmasterStatus = $textmasterStatus;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAkeneoStatus(): string
    {
        return $this->akeneoStatus;
    }

    /**
     * @inheritdoc
     */
    public function setAkeneoStatus(string $akeneoStatus): ProjectInterface
    {
        $this->akeneoStatus = $akeneoStatus;

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
    public function setCreatedAt(?DateTime $createdAt): ProjectInterface
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
    public function setUpdatedAt(?DateTime $updatedAt): ProjectInterface
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
