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

namespace BaksDev\FourTochki\Products\Controller\Admin;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\FourTochki\Products\Entity\FourTochkiProduct;
use BaksDev\FourTochki\Products\Repository\FourTochkiProductProfile\FourTochkiProductProfileInterface;
use BaksDev\FourTochki\Products\Repository\OneProductWithFourTochkiSettings\OneProductWithFourTochkiSettingsInterface;
use BaksDev\FourTochki\Products\UseCase\NewEdit\FourTochkiProductDTO;
use BaksDev\FourTochki\Products\UseCase\NewEdit\FourTochkiProductForm;
use BaksDev\FourTochki\Products\UseCase\NewEdit\FourTochkiProductHandler;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_FOUR_TOCHKI_PRODUCTS_EDIT')]
final class NewEditController extends AbstractController
{
    /** @throws JsonException */
    #[Route(
        '/admin/four-tochki/product/{product}/{offer}/{variation}/{modification}',
        name: 'admin.products.edit',
        methods: ['GET', 'POST']
    )]
    public function index(
        Request $request,
        FourTochkiProductProfileInterface $FourTochkiProductProfileInterface,
        FourTochkiProductHandler $FourTochkiProductHandler,
        OneProductWithFourTochkiSettingsInterface $OneProductWithFourTochkiSettings,
        #[ParamConverter(ProductUid::class)] $product,
        #[ParamConverter(ProductOfferConst::class)] ?ProductOfferConst $offer = null,
        #[ParamConverter(ProductVariationConst::class)] ?ProductVariationConst $variation = null,
        #[ParamConverter(ProductModificationConst::class)] ?ProductModificationConst $modification = null,
    ): Response
    {
        $fourTochkiProductDTO = new FourTochkiProductDTO();

        $fourTochkiProductDTO
            ->setProduct($product)
            ->setOffer($offer)
            ->setVariation($variation)
            ->setModification($modification);

        $fourTochkiProductDTO
            ->getProfile()
            ->setValue($this->getProfileUid());


        /**
         * Находим уникальный продукт FourTochki, делаем его инстанс, передаем в форму
         *
         * @var FourTochkiProduct|false $fourTochkiProductCard
         */
        $fourTochkiProductCard = $FourTochkiProductProfileInterface
            ->product($fourTochkiProductDTO->getProduct())
            ->offerConst($fourTochkiProductDTO->getOffer())
            ->variationConst($fourTochkiProductDTO->getVariation())
            ->modificationConst($fourTochkiProductDTO->getModification())
            ->find();

        if(true === ($fourTochkiProductCard instanceof FourTochkiProduct))
        {
            $fourTochkiProductCard->getDto($fourTochkiProductDTO);
        }

        $form = $this->createForm(
            FourTochkiProductForm::class,
            $fourTochkiProductDTO,
            ['action' => $this->generateUrl(
                'four-tochki-products:admin.products.edit',
                [
                    'product' => $fourTochkiProductDTO->getProduct(),
                    'offer' => $fourTochkiProductDTO->getOffer(),
                    'variation' => $fourTochkiProductDTO->getVariation(),
                    'modification' => $fourTochkiProductDTO->getModification(),
                    'page' => $request->get('page'),
                ],
            )],
        );

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('four_tochki_product_newedit'))
        {
            $this->refreshTokenForm($form);

            $handle = $FourTochkiProductHandler->handle($fourTochkiProductDTO);

            $this->addFlash(
                'page.edit',
                $handle instanceof FourTochkiProduct ? 'success.edit' : 'danger.edit',
                'four-tochki-products.admin',
                $handle,
            );

            return $this->redirectToRoute(
                route: 'four-tochki-products:admin.products.index',
                parameters: ['page' => $request->get('page')],
            );
        }

        $product = $OneProductWithFourTochkiSettings
            ->product($fourTochkiProductDTO->getProduct())
            ->offerConst($fourTochkiProductDTO->getOffer())
            ->variationConst($fourTochkiProductDTO->getVariation())
            ->modificationConst($fourTochkiProductDTO->getModification())
            ->find();

        if(false === $product)
        {
            throw new InvalidArgumentException('Продукт не найден ');
        }

        return $this->render(['form' => $form->createView(), 'product' => $product]);
    }
}
