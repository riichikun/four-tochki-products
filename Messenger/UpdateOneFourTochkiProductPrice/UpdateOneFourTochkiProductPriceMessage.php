<?php
/*
 * Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\FourTochki\Products\Messenger\UpdateOneFourTochkiProductPrice;

use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

final readonly class UpdateOneFourTochkiProductPriceMessage
{
    private string $product;

    private string $profile;

    private string $offerConst;

    private string $variationConst;

    private string $modificationConst;

    public function __construct(
        ProductUid $product,
        ProductOfferConst $offerConst,
        ProductVariationConst $variationConst,
        ProductModificationConst $modificationConst,
        UserProfileUid $profile,
    )
    {
        $this->product = (string) $product;
        $this->offerConst = (string) $offerConst;
        $this->variationConst = (string) $variationConst;
        $this->modificationConst = (string) $modificationConst;

        $this->profile = (string) $profile;
    }

    public function getProduct(): ProductUid
    {
        return new ProductUid($this->product);
    }

    public function getOffer(): ProductOfferUid
    {
        return new ProductOfferUid($this->offerId);
    }

    public function getVariation(): ProductVariationUid
    {
        return new ProductVariationUid($this->variationId);
    }

    public function getModification(): ProductModificationUid
    {
        return new ProductModificationUid($this->modificationId);
    }

    public function getOfferConst(): ProductOfferConst
    {
        return new ProductOfferConst($this->offerConst);
    }

    public function getVariationConst(): ProductVariationConst
    {
        return new ProductVariationConst($this->variationConst);
    }

    public function getModificationConst(): ProductModificationConst
    {
        return new ProductModificationConst($this->modificationConst);
    }

    public function getProfile(): UserProfileUid
    {
        return new UserProfileUid($this->profile);
    }
}