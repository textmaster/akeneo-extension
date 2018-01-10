<?php

namespace Pim\Bundle\TextmasterBundle\Entity;

use Pim\Bundle\TextmasterBundle\Project\ProjectInterface;
use Pim\Component\Catalog\Model\LocaleInterface;

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
    protected $apiTemplate;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt()
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getApiTemplate()
    {
        return $this->apiTemplate;
    }

    /**
     * @param string $templateId
     */
    public function setApiTemplate($templateId)
    {
        $this->apiTemplate = $templateId;
    }
}
