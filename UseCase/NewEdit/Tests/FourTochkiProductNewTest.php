<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\FourTochki\Products\UseCase\NewEdit\Tests;

use BaksDev\FourTochki\Products\Entity\FourTochkiProduct;
use BaksDev\FourTochki\Products\Type\Id\FourTochkiProductUid;
use BaksDev\FourTochki\Products\UseCase\NewEdit\Code\FourTochkiProductCodeDTO;
use BaksDev\FourTochki\Products\UseCase\NewEdit\FourTochkiProductDTO;
use BaksDev\FourTochki\Products\UseCase\NewEdit\FourTochkiProductHandler;
use BaksDev\FourTochki\Products\UseCase\NewEdit\Price\FourTochkiProductPriceDTO;
use BaksDev\FourTochki\Products\UseCase\NewEdit\Refresh\FourTochkiProductRefreshDTO;
use BaksDev\FourTochki\UseCase\Admin\NewEdit\Tests\FourTochkiAuthNewTest;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Tests\ProductsProductNewAdminUseCaseTest;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('four-tochki-products')]
#[Group('four-tochki-products-controller')]
#[Group('four-tochki-products-repository')]
#[Group('four-tochki-products-usecase')]
final class FourTochkiProductNewTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        $container = self::getContainer();

        /** @var EntityManagerInterface $EntityManager */
        $EntityManager = $container->get(EntityManagerInterface::class);

        $fourTochkiProduct = $EntityManager
            ->getRepository(FourTochkiProduct::class)
            ->find(FourTochkiProductUid::TEST);

        if($fourTochkiProduct)
        {
            $EntityManager->remove($fourTochkiProduct);
        }

        $EntityManager->flush();
        $EntityManager->clear();

        /** Создаем тестовый продукт */
        ProductsProductNewAdminUseCaseTest::setUpBeforeClass();
        new ProductsProductNewAdminUseCaseTest('')->testUseCase();

        /** Создаем тестовые данные авторизации FourTochki */
        FourTochkiAuthNewTest::setUpBeforeClass();
        new FourTochkiAuthNewTest('')->testNew();
    }

    public function testNew(): void
    {
        $fourTochkiProductDTO = new FourTochkiProductDTO();

        $fourTochkiProductDTO->setProduct(new ProductUid(ProductUid::TEST));
        self::assertTrue($fourTochkiProductDTO
            ->getProduct()
            ->equals(ProductUid::TEST),
        );

        $fourTochkiProductDTO->setOffer(new ProductOfferConst(ProductOfferConst::TEST));
        self::assertTrue($fourTochkiProductDTO
            ->getOffer()
            ->equals(ProductOfferConst::TEST),
        );

        $fourTochkiProductDTO->setVariation(new ProductVariationConst(ProductVariationConst::TEST));
        self::assertTrue($fourTochkiProductDTO
            ->getVariation()
            ->equals(ProductVariationConst::TEST),
        );

        $fourTochkiProductDTO->setModification(new ProductModificationConst(ProductModificationConst::TEST));
        self::assertTrue($fourTochkiProductDTO
            ->getModification()
            ->equals(ProductModificationConst::TEST),
        );

        $fourTochkiProductDTO->setCode(new FourTochkiProductCodeDTO()->setValue('code'));
        self::assertEquals('code', $fourTochkiProductDTO
            ->getCode()
            ->getValue(),
        );

        $fourTochkiProductDTO->setRefresh(new FourTochkiProductRefreshDTO()->setValue(true));
        self::assertTrue($fourTochkiProductDTO
            ->getRefresh()
            ->getValue(),
        );

        $fourTochkiProductDTO->setPrice(new FourTochkiProductPriceDTO()->setValue(true));
        self::assertTrue($fourTochkiProductDTO
            ->getPrice()
            ->getValue(),
        );


        $profile = $_SERVER['TEST_PROFILE'] ?? UserProfileUid::TEST;

        $fourTochkiProductDTO->getProfile()->setValue(new UserProfileUid($profile));
        self::assertTrue($fourTochkiProductDTO->getProfile()->getValue()->equals($profile));

        $container = self::getContainer();

        /** @var FourTochkiProductHandler $FourTochkiProductHandler */
        $FourTochkiProductHandler = $container->get(FourTochkiProductHandler::class);
        $newFourTochkiProduct = $FourTochkiProductHandler->handle($fourTochkiProductDTO);
        self::assertTrue(
            $newFourTochkiProduct instanceof FourTochkiProduct,
            message: (string) $newFourTochkiProduct,
        );
    }
}
