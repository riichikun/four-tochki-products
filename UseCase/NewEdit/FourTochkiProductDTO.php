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

namespace BaksDev\FourTochki\Products\UseCase\NewEdit;

use BaksDev\FourTochki\Products\Entity\FourTochkiProductInterface;
use BaksDev\FourTochki\Products\Type\Id\FourTochkiProductUid;
use BaksDev\FourTochki\Products\UseCase\NewEdit\Code\FourTochkiProductCodeDTO;
use BaksDev\FourTochki\Products\UseCase\NewEdit\Price\FourTochkiProductPriceDTO;
use BaksDev\FourTochki\Products\UseCase\NewEdit\Profile\FourTochkiProductProfileDTO;
use BaksDev\FourTochki\Products\UseCase\NewEdit\Refresh\FourTochkiProductRefreshDTO;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Symfony\Component\Validator\Constraints as Assert;

/** @see FourTochkiProduct */
final class FourTochkiProductDTO implements FourTochkiProductInterface
{
    #[Assert\Uuid]
    private ?FourTochkiProductUid $id = null;

    /** ID продукта (не уникальный) */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private ProductUid $product;

    /** Константа ТП */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private ProductOfferConst $offer;

    /** Константа множественного варианта */
    #[Assert\Uuid]
    private ?ProductVariationConst $variation = null;

    /** Константа модификации множественного варианта */
    #[Assert\Uuid]
    private ?ProductModificationConst $modification = null;

    /** Код продукта */
    #[Assert\Valid]
    private FourTochkiProductCodeDTO $code;

    /** Необходимость в обновлении цены продукта */
    #[Assert\Valid]
    private FourTochkiProductPriceDTO $price;

    /** Необходимость в обновлении остатков продукта на складе */
    #[Assert\Valid]
    private FourTochkiProductRefreshDTO $refresh;

    /** Идентификатор профиля */
    #[Assert\Valid]
    private FourTochkiProductProfileDTO $profile;

    public function __construct()
    {
        $this->code = new FourTochkiProductCodeDTO();
        $this->price = new FourTochkiProductPriceDTO();
        $this->refresh = new FourTochkiProductRefreshDTO();
        $this->profile = new FourTochkiProductProfileDTO();
    }

    public function setId(FourTochkiProductUid $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getFourTochkiProductUid(): ?FourTochkiProductUid
    {
        return $this->id;
    }

    public function getProduct(): ProductUid
    {
        return $this->product;
    }

    public function setProduct(ProductUid $product): self
    {
        $this->product = $product;
        return $this;
    }

    public function getOffer(): ?ProductOfferConst
    {
        return $this->offer;
    }

    public function setOffer(?ProductOfferConst $offer): self
    {
        $this->offer = $offer;
        return $this;
    }

    public function getVariation(): ?ProductVariationConst
    {
        return $this->variation;
    }

    public function setVariation(?ProductVariationConst $variation): self
    {
        $this->variation = $variation;
        return $this;
    }

    public function getModification(): ?ProductModificationConst
    {
        return $this->modification;
    }

    public function setModification(?ProductModificationConst $modification): self
    {
        $this->modification = $modification;
        return $this;
    }

    public function getCode(): FourTochkiProductCodeDTO
    {
        return $this->code;
    }

    public function setCode(FourTochkiProductCodeDTO $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getPrice(): FourTochkiProductPriceDTO
    {
        return $this->price;
    }

    public function setPrice(FourTochkiProductPriceDTO $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getRefresh(): FourTochkiProductRefreshDTO
    {
        return $this->refresh;
    }

    public function setRefresh(FourTochkiProductRefreshDTO $refresh): self
    {
        $this->refresh = $refresh;
        return $this;
    }

    public function getProfile(): FourTochkiProductProfileDTO
    {
        return $this->profile;
    }
}
