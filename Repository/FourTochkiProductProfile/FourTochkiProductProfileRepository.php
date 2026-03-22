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

namespace BaksDev\FourTochki\Products\Repository\FourTochkiProductProfile;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\FourTochki\Products\Entity\FourTochkiProduct;
use BaksDev\FourTochki\Products\Entity\Price\FourTochkiProductPrice;
use BaksDev\FourTochki\Products\Entity\Profile\FourTochkiProductProfile;
use BaksDev\FourTochki\Products\Entity\Refresh\FourTochkiProductRefresh;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use InvalidArgumentException;

final class FourTochkiProductProfileRepository implements FourTochkiProductProfileInterface
{
    private ProductUid|false $product = false;

    private ProductOfferConst|false $offer = false;

    private ProductVariationConst|false $variation = false;

    private ProductModificationConst|false $modification = false;

    private UserProfileUid|false $profile = false;

    public function __construct(
        private readonly ORMQueryBuilder $ORMQueryBuilder,
        private readonly UserProfileTokenStorageInterface $UserProfileTokenStorage
    ) {}

    public function product(ProductUid|Product $product): self
    {
        if($product instanceof Product)
        {
            $product = $product->getId();
        }

        $this->product = $product;

        return $this;
    }

    public function offerConst(ProductOfferConst|ProductOffer|false|null $offer): self
    {
        if(empty($offer))
        {
            $this->offer = false;
            return $this;
        }

        if($offer instanceof ProductOffer)
        {
            $offer = $offer->getConst();
        }

        $this->offer = $offer;

        return $this;
    }

    public function variationConst(ProductVariationConst|ProductVariation|false|null $variation): self
    {
        if(empty($variation))
        {
            $this->variation = false;
            return $this;
        }

        if($variation instanceof ProductVariation)
        {
            $variation = $variation->getConst();
        }

        $this->variation = $variation;

        return $this;
    }

    public function modificationConst(ProductModificationConst|ProductModification|null|false $modification): self
    {
        if(empty($modification))
        {
            $this->modification = false;
            return $this;
        }

        if($modification instanceof ProductModification)
        {
            $modification = $modification->getConst();
        }

        $this->modification = $modification;

        return $this;
    }

    public function forProfile(UserProfileUid|UserProfile $profile): self
    {
        if($profile instanceof UserProfile)
        {
            $profile = $profile->getId();
        }

        $this->profile = $profile;

        return $this;
    }


    /** Метод возвращает объект сущности FourTochkiProduct */
    public function find(): FourTochkiProduct|false
    {
        if(false === ($this->product instanceof ProductUid))
        {
            throw new InvalidArgumentException('Invalid Argument Product');
        }

        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm
            ->select('four_tochki')
            ->from(FourTochkiProduct::class, 'four_tochki')
            ->where('four_tochki.product = :product')
            ->setParameter(
                key: 'product',
                value: $this->product,
                type: ProductUid::TYPE,
            );

        $orm
            ->join(
                FourTochkiProductProfile::class,
                'profile',
                'WITH',
                'profile.main = four_tochki.id AND profile.value = :profile',
            )
            ->setParameter(
                key: 'profile',
                value: $this->profile ?: $this->UserProfileTokenStorage->getProfile(),
                type: UserProfileUid::TYPE,
            );

        $orm
            ->leftJoin(
                FourTochkiProductRefresh::class,
                'refresh',
                'WITH',
                'refresh.main = four_tochki.id',
            );

        $orm
            ->leftJoin(
                FourTochkiProductPrice::class,
                'price',
                'WITH',
                'price.main = four_tochki.id',
            );

        if(true === ($this->offer instanceof ProductOfferConst))
        {
            $orm
                ->andWhere('four_tochki.offer = :offer')
                ->setParameter(
                    key: 'offer',
                    value: $this->offer,
                    type: ProductOfferConst::TYPE,
                );
        }
        else
        {
            $orm->andWhere('four_tochki.offer IS NULL');
        }


        if(true === ($this->variation instanceof ProductVariationConst))
        {
            $orm
                ->andWhere('four_tochki.variation = :variation')
                ->setParameter(
                    key: 'variation',
                    value: $this->variation,
                    type: ProductVariationConst::TYPE,
                );
        }
        else
        {
            $orm->andWhere('four_tochki.variation IS NULL');
        }

        if(true === ($this->modification instanceof ProductModificationConst))
        {
            $orm
                ->andWhere('four_tochki.modification = :modification')
                ->setParameter(
                    key: 'modification',
                    value: $this->modification,
                    type: ProductModificationConst::TYPE,
                );
        }
        else
        {
            $orm->andWhere('four_tochki.modification IS NULL');
        }

        $orm->andWhere('refresh.value IS NOT NULL or price.value IS NOT NULL');

        return $orm->getOneOrNullResult() ?: false;
    }
}