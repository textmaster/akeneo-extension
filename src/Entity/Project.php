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

    /** @var LocaleInterface */
    private $fromLocale;

    /** @var LocaleInterface */
    private $toLocale;

    /** @var string */
    private $briefing;

    /** @var \DateTime */
    private $updatedAt;

    /** @var string */
    private $username;

    /** @var string */
    private $category;

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
     * @return LocaleInterface
     */
    public function getFromLocale()
    {
        return $this->fromLocale;
    }

    /**
     * @param LocaleInterface $fromLocale
     */
    public function setFromLocale(LocaleInterface $fromLocale)
    {
        $this->fromLocale = $fromLocale;
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
     * @return LocaleInterface
     */
    public function getToLocale()
    {
        return $this->toLocale;
    }

    /**
     * @param LocaleInterface $toLocale
     */
    public function setToLocale(LocaleInterface $toLocale)
    {
        $this->toLocale = $toLocale;
    }

    /**
     * @return string
     */
    public function getBriefing()
    {
        return $this->briefing;
    }

    /**
     * @param string $briefing
     */
    public function setBriefing($briefing)
    {
        $this->briefing = $briefing;
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
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $categoryCode
     */
    public function setCategory($categoryCode)
    {
        $this->category = $categoryCode;
    }
}
