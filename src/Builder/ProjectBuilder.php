<?php

namespace Pim\Bundle\TextmasterBundle\Builder;

use Akeneo\Pim\Structure\Component\Repository\AttributeRepositoryInterface;
use Akeneo\Tool\Component\StorageUtils\Detacher\ObjectDetacherInterface;
use DateTimeInterface;
use Exception;
use Pim\Bundle\TextmasterBundle\Doctrine\Repository\VersionRepository;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;
use Akeneo\Pim\Structure\Component\AttributeTypes;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\EntityWithValuesInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModel;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Textmaster\Model\DocumentInterface;

/**
 * TextMaster builder.
 * Can build project and document payload from PIM data
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProjectBuilder implements ProjectBuilderInterface
{
    /** @var array */
    protected $options = [];

    /** @var ObjectDetacherInterface */
    protected $objectDetacher;

    /** @var AttributeRepositoryInterface */
    protected $attributeRepository;

    /** @var VersionRepository */
    private $versionRepository;

    /** @var array */
    protected $attributes = [];

    /**
     * Builder constructor.
     *
     * @param VersionRepository $versionRepository
     * @param ObjectDetacherInterface $objectDetacher
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        VersionRepository $versionRepository,
        ObjectDetacherInterface $objectDetacher,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve([]);

        $this->objectDetacher      = $objectDetacher;
        $this->attributeRepository = $attributeRepository;
        $this->versionRepository = $versionRepository;
    }

    /**
     * @inheritdoc
     */
    public function createProjectData(ProjectInterface $project)
    {
        $data = [
            'name'            => $project->getName(),
            'api_template_id' => $project->getApiTemplateId(),
        ];

        return $data;
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function createDocumentData(
        $product,
        array $attributeCodes,
        $localeCode,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null
    ) {
        $wysiwyg = false;
        $originalContent = [];
        $productValues = $this->getDataToTranslate($product, $attributeCodes, $localeCode, $startDate, $endDate);

        foreach ($productValues as $productValue) {
            $attribute = $this->getAttributeByCode($productValue->getAttributeCode());
            $originalPhrase = trim($productValue->getData());

            if ($attribute->isWysiwygEnabled()) {
                $wysiwyg = true;
            }

            if (!empty($originalPhrase)) {
                $originalContent[$this->createProductValueKey($productValue)]['original_phrase'] = $originalPhrase;
            }
        }

        if (empty($originalContent)) {
            return null;
        }

        $documentData = [
            'title'              => $this->getDocumentTitle($product),
            'original_content'   => $originalContent,
            'perform_word_count' => true,
            'type'               => DocumentInterface::TYPE_KEY_VALUE,
            'markup_in_content'  => $wysiwyg,
        ];

        return $documentData;
    }

    /**
     * Retrieve document title from product or product model.
     *
     * @param EntityWithValuesInterface $product
     *
     * @return string
     *
     * @throws Exception
     */
    protected function getDocumentTitle(EntityWithValuesInterface $product): string
    {
        if ($product instanceof ProductInterface) {
            return $product->getIdentifier();
        } elseif ($product instanceof ProductModel) {
            return sprintf('product_model|%s', $product->getCode());
        }

        throw new Exception(
            sprintf(
                'Processed item must implement ProductInterface or Product Model, %s given',
                get_class($product)
            )
        );
    }

    /**
     * Retrieve productVal
     *
     * @param EntityWithValuesInterface $product
     * @param array $attributeCodes
     * @param string $localeCode
     * @param DateTimeInterface|null $startDate
     * @param DateTimeInterface|null $endDate
     *
     * @return ValueInterface[]
     */
    private function getDataToTranslate(EntityWithValuesInterface $product, array $attributeCodes, string $localeCode, ?DateTimeInterface $startDate, ?DateTimeInterface $endDate): array
    {
        $productValues = [];
        $attributesUpdatedInDateRange = null;

        if ($startDate && $endDate) {
            $attributesUpdatedInDateRange = array_flip($this->getAttributesUpdatedInDateRange($product, $startDate, $endDate));
        }

        /** @var ValueInterface $productValue */
        foreach ($product->getValues() as $productValue) {
            $attribute = $this->getAttributeByCode($productValue->getAttributeCode());

            if (false === $this->isValidForTranslation($attribute) || $productValue->getLocaleCode() !== $localeCode) {
                continue;
            }

            if (!in_array($productValue->getAttributeCode(), $attributeCodes)) {
                continue;
            }

            if (!$this->isValueUpdatedInDateRange($productValue, $attributesUpdatedInDateRange)) {
                continue;
            }

            $productValues[] = $productValue;
        }

        return $productValues;
    }

    /**
     * Create the document key for a product value
     *
     * @param ValueInterface $productValue
     *
     * @return string
     */
    public function createProductValueKey(ValueInterface $productValue): string
    {
        if ($productValue->isScopable()) {
            return sprintf('%s-%s', $productValue->getAttributeCode(), $productValue->getScopeCode());
        }

        return $productValue->getAttributeCode();
    }

    /**
     * Check if attribute is a text attribute.
     *
     * @param AttributeInterface $attribute
     *
     * @return bool
     */
    protected function isValidForTranslation(AttributeInterface $attribute): bool
    {
        $isText = AttributeTypes::TEXT === $attribute->getType() || AttributeTypes::TEXTAREA === $attribute->getType();

        return $isText && $attribute->isLocalizable();
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'ctype' => 'translation',
            ]
        );
    }

    /**
     * Retrieve attribute by its code.
     *
     * @param $code
     *
     * @return AttributeInterface|null
     */
    protected function getAttributeByCode($code): ?AttributeInterface
    {
        if (!array_key_exists($code, $this->attributes)) {
            $this->attributes[$code] = $this->attributeRepository->findOneByIdentifier($code);
        }

        return $this->attributes[$code];
    }

    /**
     * @param $product
     * @param DateTimeInterface $startDate
     * @param DateTimeInterface $endDate
     *
     * @return string[]
     */
    public function getAttributesUpdatedInDateRange($product, DateTimeInterface $startDate, DateTimeInterface $endDate)
    {
        $attributeCodes = [];
        $versions = $this->versionRepository->findLogEntriesInDateRange($startDate, $endDate, $product);

        foreach ($versions as $version) {
            $attributeCodes = array_merge($attributeCodes, array_keys($version->getChangeset()));

            $this->objectDetacher->detach($version);
        }

        return $attributeCodes;
    }

    public function isValueUpdatedInDateRange(ValueInterface $productValue, ?array $attributesUpdatedInDateRange = null)
    {
        if (is_null($attributesUpdatedInDateRange)) {
            return true;
        }

        $combinedCode = $productValue->getAttributeCode();

        if ($productValue->getLocaleCode()) {
            $combinedCode .= '-'.$productValue->getLocaleCode();
        }

        if ($productValue->getScopeCode()) {
            $combinedCode .= '-'.$productValue->getScopeCode();
        }

        return array_key_exists($combinedCode, $attributesUpdatedInDateRange);
    }
}
