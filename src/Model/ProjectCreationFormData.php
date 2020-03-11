<?php

namespace Pim\Bundle\TextmasterBundle\Model;

use DateTime;
use DateTimeInterface;


/**
 * @author Huy Nguyen <khnguyen@clever-age.com>
 */
class ProjectCreationFormData
{
    const OPTION_PERSONALIZED_ATTRIBUTE = 'customSelection';
    const OPTION_DEFAULT_ATTRIBUTES = 'defaultAttributes';
    const OPTION_IN_DATE_RANGE = 'updatedInDateRange';

    /** @var string */
    protected $projectName = '';

    /** @var array */
    protected $apiTemplateIds = [];

    /** @var string */
    protected $username = '';

    /** @var string */
    protected $attributeOption = '';

    /** @var array */
    protected $selectedAttributes = [];

    /** @var DateTimeInterface|null */
    protected $dateRangeStartsAt;

    /** @var DateTimeInterface|null */
    protected $dateRangeEndsAt;

    public function __construct(array $data)
    {
        $this->projectName = $data['name'];
        $this->apiTemplateIds = explode(',', $data['apiTemplates']);
        $this->username = $data['username'];
        $this->attributeOption = $data['attributeOption'];

        if ($this->attributeOptionPersonalizedSelected() && !empty($data['personalizedAttributes'])) {
            $this->selectedAttributes = explode(',', $data['personalizedAttributes']);
        }

        if ($this->attributesInDateRangeSelected()) {
            $startDate = DateTime::createFromFormat('Y-m-d', $data['dateRangeStartsAt']);
            $endDate = DateTime::createFromFormat('Y-m-d', $data['dateRangeEndsAt']);

            $this->dateRangeStartsAt = $startDate ? $startDate : null;
            $this->dateRangeEndsAt = $endDate ? $endDate : null;
        }
    }

    /**
     * @return string
     */
    public function getProjectName(): string
    {
        return $this->projectName;
    }

    /**
     * @param string $projectName
     */
    public function setProjectName(string $projectName): void
    {
        $this->projectName = $projectName;
    }

    /**
     * @return array
     */
    public function getApiTemplateIds(): array
    {
        return $this->apiTemplateIds;
    }

    /**
     * @param array $apiTemplateIds
     */
    public function setApiTemplateIds(array $apiTemplateIds): void
    {
        $this->apiTemplateIds = $apiTemplateIds;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getAttributeOption(): string
    {
        return $this->attributeOption;
    }

    /**
     * @param string $attributeOption
     */
    public function setAttributeOption(string $attributeOption): void
    {
        $this->attributeOption = $attributeOption;
    }

    /**
     * @return array
     */
    public function getSelectedAttributes(): array
    {
        return $this->selectedAttributes;
    }

    /**
     * @param array $selectedAttributes
     */
    public function setSelectedAttributes(array $selectedAttributes): void
    {
        $this->selectedAttributes = $selectedAttributes;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getDateRangeStartsAt(): ?DateTimeInterface
    {
        return $this->dateRangeStartsAt;
    }

    /**
     * @param DateTimeInterface|null $dateRangeStartsAt
     */
    public function setDateRangeStartsAt(?DateTimeInterface $dateRangeStartsAt): void
    {
        $this->dateRangeStartsAt = $dateRangeStartsAt;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getDateRangeEndsAt(): ?DateTimeInterface
    {
        return $this->dateRangeEndsAt;
    }

    /**
     * @param DateTimeInterface|null $dateRangeEndsAt
     */
    public function setDateRangeEndsAt(?DateTimeInterface $dateRangeEndsAt): void
    {
        $this->dateRangeEndsAt = $dateRangeEndsAt;
    }

    public function attributeOptionDefaultSelected()
    {
        return $this->attributeOption === self::OPTION_DEFAULT_ATTRIBUTES;
    }

    public function attributeOptionPersonalizedSelected()
    {
        return $this->attributeOption === self::OPTION_PERSONALIZED_ATTRIBUTE;
    }

    public function attributesInDateRangeSelected()
    {
        return $this->attributeOption === self::OPTION_IN_DATE_RANGE;
    }
}
