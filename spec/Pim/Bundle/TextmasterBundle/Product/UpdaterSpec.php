<?php

namespace spec\Pim\Bundle\TextmasterBundle\Product;

use PhpSpec\ObjectBehavior;
use Pim\Bundle\CatalogBundle\Repository\ProductRepositoryInterface;
use Pim\Bundle\TextmasterBundle\Product\UpdaterInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Updater\ProductUpdater;
use Prophecy\Argument;
use Textmaster\Model\DocumentInterface;

/**
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class UpdaterSpec extends ObjectBehavior
{
    function let(ProductRepositoryInterface $productRepository, ProductUpdater $productUpdater)
    {
        $this->beConstructedWith($productRepository, $productUpdater);
    }

    function it_is_initializable()
    {
        $this->shouldImplement(UpdaterInterface::class);
    }

    function it_updates_a_document(
        DocumentInterface $document,
        ProductInterface $product,
        $productRepository,
        $productUpdater
    )
    {
        $localeCode = 'en_US';

        $document->getTitle()->willReturn('product-sku');
        $productRepository->findOneByIdentifier('product-sku')->willReturn($product);

        $document->getTranslatedContent()->willReturn([
            'foo-ecommerce' => 'foo ecommerce translation',
            'foo-mobile'    => 'foo mobile translation',
            'bar-ecommerce' => 'bar ecommerce translation',
        ]);

        $data = [
            'foo' => [
                ['locale' => 'en_US', 'scope' => 'ecommerce', 'data' => 'foo ecommerce translation'],
                ['locale' => 'en_US', 'scope' => 'mobile', 'data' => 'foo mobile translation'],
            ],
            'bar' => [
                ['locale' => 'en_US', 'scope' => 'ecommerce', 'data' => 'bar ecommerce translation'],
            ],
        ];

        $productUpdater->update($product, $data)->shouldBeCalled();
        $this->update($document, $localeCode);
    }
}
