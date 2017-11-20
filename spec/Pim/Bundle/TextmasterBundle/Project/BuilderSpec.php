<?php

namespace spec\Pim\Bundle\TextmasterBundle\Project;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\TextmasterBundle\Project\BuilderInterface;
use Pim\Bundle\TextmasterBundle\Project\Exception\RuntimeException;
use Pim\Bundle\TextmasterBundle\Project\ProjectInterface;
use Pim\Component\Catalog\AttributeTypes;
use Pim\Component\Catalog\Model\AttributeInterface;
use Pim\Component\Catalog\Model\LocaleInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Model\ValueInterface;
use Psr\Log\LoggerInterface;
use Textmaster\Model\DocumentInterface;

/**
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class BuilderSpec extends ObjectBehavior
{
    function let(ConfigManager $configManager, LoggerInterface $logger)
    {
        $this->beConstructedWith($configManager, $logger, []);
    }

    function it_is_initializable()
    {
        $this->shouldImplement(BuilderInterface::class);
    }

    function it_creates_project_data(
        ProjectInterface $project,
        LocaleInterface $localeEn,
        LocaleInterface $localeFr
    )
    {
        $localeEn->getCode()->willReturn('en_US');
        $localeFr->getCode()->willReturn('fr_FR');

        $project->getName()->willReturn('fooname');
        $project->getFromLocale()->willReturn($localeEn);
        $project->getToLocale()->willReturn($localeFr);
        $project->getCategory()->willReturn('C019');
        $project->getBriefing()->willReturn('fooBriefing');

        $expected = [
            'name'                     => 'fooname',
            'ctype'                    => 'translation',
            'language_from'            => 'en-us',
            'language_to'              => 'fr-fr',
            'category'                 => 'C019',
            'vocabulary_type'          => 'technical',
            'project_briefing'         => 'fooBriefing',
            'project_briefing_is_rich' => true,
            'options'                  => [
                'language_level' => 'enterprise',
            ],
        ];

        $this->createProjectData($project)
            ->shouldReturnThisArray($expected);
    }


    function it_checks_configuration(
        ProductInterface $product,
        ValueInterface $productValue1,
        AttributeInterface $attribute1,
        $configManager
    )
    {
        $configManager->get('pim_textmaster.attributes')
            ->willReturn('');
        $localeCode = 'en_US';

        $productValue1->getLocale()->willReturn($localeCode);
        $productValue1->getScope()->willReturn('ecommerce');
        $productValue1->getData()->willReturn('lorem ipsum');
        $productValue1->getAttribute()->willReturn($attribute1);

        $attribute1->getCode()->willReturn('att1');
        $attribute1->getType()->willReturn(AttributeTypes::TEXT);
        $attribute1->isLocalizable()->willReturn(true);
        $attribute1->isScopable()->willReturn(true);
        $attribute1->isWysiwygEnabled()->willReturn(true);

        $product->getValues()->willReturn([
            $productValue1,
        ]);
        $product->getIdentifier()->willReturn('fooSku');

        $this->shouldThrow(new RuntimeException('No attributes configured for translation'))
            ->during('createDocumentData', array($product, $localeCode));
    }

    function it_creates_document_data(
        ProductInterface $product,
        ValueInterface $productValue1,
        AttributeInterface $attribute1,
        ValueInterface $productValue2,
        ValueInterface $productValue3,
        AttributeInterface $attribute3,
        ValueInterface $productValue4,
        AttributeInterface $attribute4,
        ValueInterface $productValue5,
        AttributeInterface $attribute5,
        $configManager
    )
    {
        $configManager->get('pim_textmaster.attributes')
            ->willReturn('att1,att2,att5');
        $localeCode = 'en_US';

        $productValue1->getLocale()->willReturn($localeCode);
        $productValue1->getScope()->willReturn('ecommerce');
        $productValue1->getData()->willReturn('lorem ipsum');
        $productValue1->getAttribute()->willReturn($attribute1);

        $attribute1->getCode()->willReturn('att1');
        $attribute1->getType()->willReturn(AttributeTypes::TEXT);
        $attribute1->isLocalizable()->willReturn(true);
        $attribute1->isScopable()->willReturn(true);
        $attribute1->isWysiwygEnabled()->willReturn(true);

        $productValue2->getLocale()->willReturn($localeCode);
        $productValue2->getScope()->willReturn('mobile');
        $productValue2->getData()->willReturn('foobar foobaz');
        $productValue2->getAttribute()->willReturn($attribute1);

        $productValue3->getLocale()->willReturn($localeCode);
        $productValue3->getAttribute()->willReturn($attribute3);

        $attribute3->getCode()->willReturn('att3');
        $attribute3->getType()->willReturn(AttributeTypes::BOOLEAN);
        $attribute3->isLocalizable()->willReturn(true);
        $attribute3->isScopable()->willReturn(true);
        $attribute3->isWysiwygEnabled()->willReturn(true);

        $productValue4->getLocale()->willReturn($localeCode);
        $productValue4->getAttribute()->willReturn($attribute4);

        $attribute4->getCode()->willReturn('att4');
        $attribute4->getType()->willReturn(AttributeTypes::TEXT);
        $attribute4->isLocalizable()->willReturn(false);
        $attribute4->isWysiwygEnabled()->willReturn(true);

        $productValue5->getLocale()->willReturn($localeCode);
        $productValue5->getAttribute()->willReturn($attribute5);
        $productValue5->getData()->willReturn('attribute5 data');

        $attribute5->getCode()->willReturn('att5');
        $attribute5->getType()->willReturn(AttributeTypes::TEXT);
        $attribute5->isLocalizable()->willReturn(true);
        $attribute5->isScopable()->willReturn(false);
        $attribute5->isWysiwygEnabled()->willReturn(true);

        $product->getValues()->willReturn([
            $productValue1,
            $productValue2,
            $productValue3,
            $productValue4,
            $productValue5,
        ]);
        $product->getIdentifier()->willReturn('fooSku');

        $expected = [
            'title'              => 'fooSku',
            'original_content'   => [
                'att1-ecommerce' => ['original_phrase' => 'lorem ipsum'],
                'att1-mobile'    => ['original_phrase' => 'foobar foobaz'],
                'att5'           => ['original_phrase' => 'attribute5 data'],
            ],
            'perform_word_count' => true,
            'type'               => DocumentInterface::TYPE_KEY_VALUE,
            'markup_in_content'  => true
        ];

        $this->createDocumentData($product, $localeCode)
            ->shouldReturnThisArray($expected);
    }

    function it_does_not_creates_document_data_with_empty_content(
        ProductInterface $product,
        ValueInterface $productValue1,
        AttributeInterface $attribute1,
        ValueInterface $productValue2,
        ValueInterface $productValue3,
        AttributeInterface $attribute3,
        $configManager
    )
    {
        $configManager->get('pim_textmaster.attributes')
            ->willReturn('att1,att2,att5');
        $localeCode = 'en_US';

        $productValue1->getLocale()->willReturn($localeCode);
        $productValue1->getScope()->willReturn('ecommerce');
        $productValue1->getData()->willReturn(null);
        $productValue1->getAttribute()->willReturn($attribute1);

        $attribute1->getCode()->willReturn('att1');
        $attribute1->getType()->willReturn(AttributeTypes::TEXT);
        $attribute1->isLocalizable()->willReturn(true);
        $attribute1->isScopable()->willReturn(true);
        $attribute1->isWysiwygEnabled()->willReturn(false);

        $productValue2->getLocale()->willReturn($localeCode);
        $productValue2->getScope()->willReturn('mobile');
        $productValue2->getData()->willReturn(null);
        $productValue2->getAttribute()->willReturn($attribute1);

        $productValue3->getLocale()->willReturn($localeCode);
        $productValue3->getAttribute()->willReturn($attribute3);

        $attribute3->getCode()->willReturn('att3');
        $attribute3->getType()->willReturn(AttributeTypes::BOOLEAN);
        $attribute3->isLocalizable()->willReturn(true);
        $attribute3->isScopable()->willReturn(true);


        $product->getValues()->willReturn([
            $productValue1,
            $productValue2,
            $productValue3,
        ]);
        $product->getIdentifier()->willReturn('fooSku');

        $this->createDocumentData($product, $localeCode)
            ->shouldReturn(null);
    }

    public function getMatchers()
    {
        return [
            'returnThisArray' => function ($subject, $expected) {
                return $subject == $expected;
            },
        ];
    }
}
