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

namespace BaksDev\FourTochki\Products\UseCase\Delete\Tests;

use BaksDev\FourTochki\Products\Entity\FourTochkiProduct;
use BaksDev\FourTochki\Products\Type\Id\FourTochkiProductUid;
use BaksDev\FourTochki\Products\UseCase\Delete\FourTochkiProductDeleteDTO;
use BaksDev\FourTochki\Products\UseCase\Delete\FourTochkiProductDeleteHandler;
use BaksDev\FourTochki\Products\UseCase\NewEdit\Tests\FourTochkiProductEditTest;
use BaksDev\FourTochki\UseCase\Admin\Delete\Tests\FourTochkiAuthDeleteTest;
use BaksDev\Products\Product\UseCase\Admin\Delete\Tests\ProductsProductDeleteAdminUseCaseTest;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('four-tochki-products')]
#[Group('four-tochki-products-usecase')]
class FourTochkiProductDeleteTest extends KernelTestCase
{
    public static function tearDownAfterClass(): void
    {
        /** Удаляем тестовый продукт после завершения */
        ProductsProductDeleteAdminUseCaseTest::tearDownAfterClass();

        /** Удаляем тестовую авторизацию FourTochki */
        FourTochkiAuthDeleteTest::tearDownAfterClass();
    }

    #[DependsOnClass(FourTochkiProductEditTest::class)]
    public function testDelete(): void
    {
        $Container = self::getContainer();
        $EntityManager = $Container->get(EntityManagerInterface::class);

        $fourTochkiProduct = $EntityManager
            ->getRepository(FourTochkiProduct::class)
            ->find(FourTochkiProductUid::TEST);

        self::assertNotNull($fourTochkiProduct);

        $deleteDTO = new FourTochkiProductDeleteDTO();

        $fourTochkiProduct->getDto($deleteDTO);

        /** @var FourTochkiProductDeleteHandler $Handler */
        $FourTochkiProductDeleteHandler = $Container->get(FourTochkiProductDeleteHandler::class);
        $deletedFourTochkiProduct = $FourTochkiProductDeleteHandler->handle($deleteDTO);
        self::assertTrue($deletedFourTochkiProduct instanceof FourTochkiProduct);
    }
}
