<?php

namespace Pim\Bundle\TextmasterBundle\Builder;

use Akeneo\Pim\Enrichment\Component\Product\Model\EntityWithFamilyVariantInterface;
use Akeneo\Pim\Structure\Component\Repository\AttributeRepositoryInterface;
use Akeneo\Tool\Component\StorageUtils\Detacher\ObjectDetacherInterface;
use Exception;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Pim\Bundle\TextmasterBundle\Model\ProjectInterface;
use Pim\Bundle\TextmasterBundle\Project\Exception\RuntimeException;
use Akeneo\Pim\Structure\Component\AttributeTypes;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\EntityWithValuesInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModel;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Textmaster\Model\DocumentInterface;
use Doctrine\Common\Util\ClassUtils;

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

    /** @var ConfigManager */
    protected $configManager;

    /** @var ObjectDetacherInterface */
    protected $objectDetacher;

    /** @var AttributeRepositoryInterface */
    protected $attributeRepository;

    /**@var array */
    protected $textmasterAttributes;

    /** @var array */
    protected $availableAttributes = [];

    /** @var array */
    protected $attributes = [];

    /**
     * Builder constructor.
     *
     * @param ConfigManager                $configManager
     * @param ObjectDetacherInterface      $objectDetacher
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        ConfigManager $configManager,
        ObjectDetacherInterface $objectDetacher,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve([]);

        $this->configManager       = $configManager;
        $this->objectDetacher      = $objectDetacher;
        $this->attributeRepository = $attributeRepository;
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
     */
    public function createDocumentData($product, $localeCode)
    {
        $wysiwyg         = false;
        $originalContent = [];

        /** @var ValueInterface $productValue */
        foreach ($this->getDataToTranslate($product) as $productValue) {
            $attribute = $this->getAttributeByCode($productValue->getAttributeCode());

            if (false === $this->isValidForTranslation($attribute) || $productValue->getLocaleCode() !== $localeCode) {
                continue;
            }

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
                ClassUtils::getClass($product)
            )
        );
    }


    /**
     * Retrieve productVal
     *
     * @param EntityWithValuesInterface $product
     *
     * @return array
     */
    private function getDataToTranslate(EntityWithValuesInterface $product): array
    {
        $availableAttributes = $this->getAvailableAttributes($product);
        $productValues       = [];

        /** @var ValueInterface $productValue */
        foreach ($product->getValues() as $productValue) {
            if (in_array($productValue->getAttributeCode(), $availableAttributes)) {
                $productValues[] = $productValue;
            }
        }

        return $productValues;
    }

    /**
     * Retrieve available attribute codes.
     *
     * @param EntityWithValuesInterface $product
     *
     * @return array
     */
    protected function getAvailableAttributes(EntityWithValuesInterface $product): array
    {
        $availableAttributes = $this->getAvailableAttributesFromProduct($product);

        if ($product instanceof ProductModelInterface) {
            $familyVariantCode = $product->getFamilyVariant()->getCode();

            if (EntityWithFamilyVariantInterface::ROOT_VARIATION_LEVEL === $product->getLevel()) {
                $this->availableAttributes[$familyVariantCode] = $product->getUsedAttributeCodes();
            } else {
                if (!isset($this->availableAttributes[$familyVariantCode])) {
                    $this->availableAttributes[$familyVariantCode] = $this->getAvailableAttributes(
                        $product->getParent()
                    );

                    $this->objectDetacher->detach($product->getParent());
                }

                $availableAttributes = array_diff(
                    $availableAttributes,
                    $this->availableAttributes[$familyVariantCode]
                );
            }
        }

        return $availableAttributes;
    }

    /**
     * Retrieve available attributes from product given.
     *
     * @param EntityWithValuesInterface $product
     *
     * @return array
     */
    protected function getAvailableAttributesFromProduct(EntityWithValuesInterface $product)
    {
        return array_intersect($this->getTextmasterAttributes(), $product->getUsedAttributeCodes());
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
     * Retrieve textmaster's attributes to translate.
     *
     * @return string[]
     */
    protected function getTextmasterAttributes(): array
    {
        if (null === $this->textmasterAttributes) {
            $this->textmasterAttributes = explode(',', $this->configManager->get('pim_textmaster.attributes'));

            if (empty($this->textmasterAttributes)) {
                throw new RuntimeException('No attributes configured for translation');
            }
        }

        return $this->textmasterAttributes;
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
        if (!in_array($attribute->getCode(), $this->getTextmasterAttributes())) {
            return false;
        }

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
}
