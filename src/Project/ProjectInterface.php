<?php

namespace Pim\Bundle\TextmasterBundle\Project;

/**
 * PIM Project entity interface
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface ProjectInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $id
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getCode();

    /**
     * @param string $code
     */
    public function setCode($code);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getApiTemplateId();

    /**
     * @param string $template
     */
    public function setApiTemplateId($template);

    /**
     * @return \DateTime
     */
    public function getUpdatedAt();

    public function setUpdatedAt();

    /**
     * @return string
     */
    public function getUsername();

    /**
     * @param string $username
     */
    public function setUsername($username);

    /**
     * @return array
     */
    public function getDocuments();

    /**
     * @param array $documents
     */
    public function setDocuments($documents);

    /**
     * @param string[] $document
     */
    public function addDocument($document);
}
