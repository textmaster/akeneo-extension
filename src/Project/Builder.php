<?php

namespace Pim\Bundle\TextmasterBundle\Project;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Pim\Bundle\CatalogBundle\AttributeType\AttributeTypes;
use Pim\Bundle\TextmasterBundle\Project\Exception\RuntimeException;
use Pim\Component\Catalog\Model\AttributeInterface;
use Pim\Component\Catalog\Model\LocaleInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Model\ProductValueInterface;
use Psr\Log\LoggerInterface;
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
class Builder implements BuilderInterface
{
    /** @var array */
    protected $options;

    /** @var ConfigManager */
    protected $configManager;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ConfigManager   $configManager
     * @param LoggerInterface $logger
     * @param string[]        $options
     */
    public function __construct(ConfigManager $configManager, LoggerInterface $logger, array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
        $this->configManager = $configManager;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function createProjectData(ProjectInterface $project)
    {
        $data = [
            'name'                     => $project->getName(),
            'ctype'                    => $this->options['ctype'],
            'language_from'            => $this->localeCodeForTextmaster($project->getFromLocale()),
            'language_to'              => $this->localeCodeForTextmaster($project->getToLocale()),
            'category'                 => $project->getCategory(),
            'vocabulary_type'          => $this->options['vocabulary_type'],
            'project_briefing'         => $project->getBriefing(),
            'project_briefing_is_rich' => true,
            'options'                  => [
                'language_level' => $this->options['language_level'],
            ],
        ];

        $this->logger->debug(sprintf('Create project data: %s', json_encode($data)));

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function createDocumentData(ProductInterface $product, $localeCode)
    {
        $productValues = $product->getValues();
        $originalContent = [];
        $wysiwyg = false;
        foreach ($productValues as $productValue) {
            /** @var ProductValueInterface $productValue */
            if ($this->isValidForTranslation($productValue->getAttribute()) && $localeCode === $productValue->getLocale()) {
                $key = $this->createProductValueKey($productValue);
                $originalPhrase = trim($productValue->getData());
                if ($productValue->getAttribute()->isWysiwygEnabled()) {
                    $wysiwyg = true;
                }
                if (!empty($originalPhrase)) {
                    $originalContent[$key]['original_phrase'] = $originalPhrase;
                }
            }
        }

        if (empty($originalContent)) {
            return null;
        }

        $documentData = [
            'title'              => $product->getIdentifier()->getVarchar(),
            'original_content'   => $originalContent,
            'perform_word_count' => true,
            'type'               => DocumentInterface::TYPE_KEY_VALUE,
            'markup_in_content'  => $wysiwyg,
        ];

        $this->logger->debug(sprintf('Create document data: %s', json_encode($documentData)));

        return $documentData;
    }

    /**
     * Create the document key for a product value
     * 
     * @param ProductValueInterface $productValue
     *
     * @return string
     */
    public function createProductValueKey(ProductValueInterface $productValue)
    {
        $attribute = $productValue->getAttribute();
        $key = $attribute->getCode();

        if ($attribute->isScopable()) {
            $key = sprintf('%s-%s', $attribute->getCode(), $productValue->getScope());
        }

        return $key;
    }

    /**
     * @param AttributeInterface $attribute
     *
     * @return bool
     */
    protected function isValidForTranslation(AttributeInterface $attribute)
    {
        $attributesSetting = $this->configManager->get('pim_textmaster.attributes');
        if (empty($attributesSetting)) {
            throw new RuntimeException('No attributes configured for translation');
        }

        $attributeCodes = explode(',', $attributesSetting);

        if (!in_array($attribute->getCode(), $attributeCodes)) {
            return false;
        }

        $isText = AttributeTypes::TEXT === $attribute->getAttributeType() ||
            AttributeTypes::TEXTAREA === $attribute->getAttributeType();

        return $isText && $attribute->isLocalizable();
    }

    /**
     * @param LocaleInterface $locale
     *
     * @return string
     */
    protected function localeCodeForTextmaster(LocaleInterface $locale)
    {
        return strtolower(str_replace('_', '-', $locale->getCode()));
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'ctype'           => 'translation',
            'category'        => 'C019',
            'language_level'  => 'enterprise',
            'vocabulary_type' => 'technical',
        ]);
    }
}
