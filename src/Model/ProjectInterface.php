<?php

namespace Pim\Bundle\TextmasterBundle\Model;

use DateTime;

/**
 * Interface ProjectInterface
 *
 * @package Pim\Bundle\TextmasterBundle\Model
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
interface ProjectInterface
{
    /**
     * @return int
     */
    public function getId(): ?int;

    /**
     * @return string
     */
    public function getTextmasterProjectId(): string;

    /**
     * @param string $textmasterProjectId
     *
     * @return ProjectInterface
     */
    public function setTextmasterProjectId(string $textmasterProjectId): ProjectInterface;

    /**
     * @return string
     */
    public function getUsername(): string;

    /**
     * @param string $username
     *
     * @return ProjectInterface
     */
    public function setUsername(string $username): ProjectInterface;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     *
     * @return ProjectInterface
     */
    public function setName(string $name): ProjectInterface;

    /**
     * @return string
     */
    public function getApiTemplateId(): string;

    /**
     * @param string $apiTemplateId
     *
     * @return ProjectInterface
     */
    public function setApiTemplateId(string $apiTemplateId): ProjectInterface;

    /**
     * @return string
     */
    public function getTextmasterStatus(): string;

    /**
     * @param string $textmasterStatus
     *
     * @return ProjectInterface
     */
    public function setTextmasterStatus(string $textmasterStatus): ProjectInterface;

    /**
     * @return string
     */
    public function getAkeneoStatus(): string;

    /**
     * @param string $akeneoStatus
     *
     * @return ProjectInterface
     */
    public function setAkeneoStatus(string $akeneoStatus): ProjectInterface;

    /**
     * @return DateTime
     */
    public function getCreatedAt(): ?DateTime;

    /**
     * @param DateTime $createdAt
     *
     * @return ProjectInterface
     */
    public function setCreatedAt(?DateTime $createdAt): ProjectInterface;

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): ?DateTime;

    /**
     * @param DateTime $updatedAt
     *
     * @return ProjectInterface
     */
    public function setUpdatedAt(?DateTime $updatedAt): ProjectInterface;
}