<?php

namespace Pim\Bundle\TextmasterBundle\Model;

/**
 * Interface DocumentInterface
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
     * @return string
     */
    public function getProjectIdentifier(): string;

    /**
     * @param string $projectIdentifier
     *
     * @return DocumentInterface
     */
    public function setProjectIdentifier(string $projectIdentifier): DocumentInterface;

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
    public function getLanguage(): string;

    /**
     * @param string $language
     *
     * @return DocumentInterface
     */
    public function setLanguage(string $language): DocumentInterface;

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
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime;

    /**
     * @param \DateTime $updatedAt
     *
     * @return DocumentInterface
     */
    public function setUpdatedAt($updatedAt): DocumentInterface;
}
