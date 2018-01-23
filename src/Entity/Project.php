<?php

namespace Pim\Bundle\TextmasterBundle\Entity;

use Pim\Bundle\TextmasterBundle\Project\ProjectInterface;

/**
 * Project entity
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Project implements ProjectInterface
{
    /** @var int */
    private $id;

    /** @var string */
    private $code;

    /** @var string */
    private $name;

    /** @var \DateTime */
    private $updatedAt;

    /** @var string */
    private $username;

    /** @var string */
    private $apiTemplateId;

    /** @var array */
    private $documents;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt()
    {
        $this->updatedAt = new \DateTime();
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getApiTemplateId()
    {
        return $this->apiTemplateId;
    }

    public function setApiTemplateId($templateId)
    {
        $this->apiTemplateId = $templateId;
    }

    public function getDocuments()
    {
        return $this->documents;
    }

    public function setDocuments($documents)
    {
        $this->documents = $documents;
    }

    public function addDocument($document)
    {
        $this->documents[] = $document;
    }
}
