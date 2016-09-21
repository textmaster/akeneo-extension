<?php

namespace Pim\Bundle\TextmasterBundle\Project;

use Pim\Component\Catalog\Model\LocaleInterface;

/**
 * PIM Project entity interface
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
     * @return LocaleInterface
     */
    public function getFromLocale();

    /**
     * @param LocaleInterface $fromLocale
     */
    public function setFromLocale(LocaleInterface $fromLocale);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     */
    public function setName($name);

    /**
     * @return LocaleInterface
     */
    public function getToLocale();

    /**
     * @param LocaleInterface $toLocale
     */
    public function setToLocale(LocaleInterface $toLocale);

    /**
     * @return string
     */
    public function getBriefing();

    /**
     * @param string $briefing
     */
    public function setBriefing($briefing);

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
     * @return string
     */
    public function getCategory();

    /**
     * @param string $categoryCode
     */
    public function setCategory($categoryCode);
}
