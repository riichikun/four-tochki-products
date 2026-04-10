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

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\FourTochki\Api\GetFindTyre\FourTochkiGetFindTyreRequest;
use BaksDev\FourTochki\Api\GetFindTyre\FourTochkiGetFindTyreResult;
use BaksDev\FourTochki\Products\Repository\FourTochkiProductProfile\FourTochkiProductProfileInterface;
use BaksDev\FourTochki\Products\UseCase\NewEdit\FourTochkiProductDTO;
use BaksDev\Products\Product\Messenger\Price\UpdateProductPriceMessage;
use BaksDev\Products\Product\Repository\CurrentProductEvent\CurrentProductEventInterface;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierByConstInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final readonly class UpdateOneFourTochkiProductPriceDispatcher
{
    public function __construct(
        #[Target('fourTochkiLogger')] private LoggerInterface $Logger,
        private FourTochkiGetFindTyreRequest $FourTochkiGetFindTyreRequest,
        private FourTochkiProductProfileInterface $FourTochkiProductProfileRepository,
        private CurrentProductIdentifierByConstInterface $CurrentProductIdentifierByConstRepository,
        private MessageDispatchInterface $MessageDispatch,
    ) {}

    /** Получаем информацию о цене данного товара на 4tochki и обновляем цену в соответствии с полученными данными */
    public function __invoke(UpdateOneFourTochkiProductPriceMessage $message): void
    {
        /** Находим настройки продукта для 4tochki */
        $fourTochkiProduct = $this->FourTochkiProductProfileRepository
            ->product($message->getProduct())
            ->offerConst($message->getOfferConst())
            ->variationConst($message->getVariationConst())
            ->modificationConst($message->getModificationConst())
            ->forProfile($message->getProfile())
            ->find();

        if(false === $fourTochkiProduct)
        {
            $this->Logger->warning(
                'Настройки для продукта не были найдены',
                [var_export($message, true), self::class.':'.__LINE__],
            );
            return;
        }

        $fourTochkiProductDTO = new FourTochkiProductDTO();
        $fourTochkiProduct->getDto($fourTochkiProductDTO);

        /** Если не выбрана настройка обновления цены в карточке */
        if(false === $fourTochkiProductDTO->getPrice()->getValue())
        {
            return;
        }

        /**
         * Обновляем цену в карточке
         */

        $code = $fourTochkiProductDTO
            ->getCode()
            ->getValue();

        $fourTochkiGetFindTyreResult = $this->FourTochkiGetFindTyreRequest
            ->profile($message->getProfile())
            ->findTyre($code);

        if(false === ($fourTochkiGetFindTyreResult instanceof FourTochkiGetFindTyreResult))
        {
            $this->Logger->warning(
                sprintf('Модель с артикулом %s не была найдена на складах 4tochki', $code),
                [var_export($message, true), self::class.':'.__LINE__],
            );
            return;
        }

        $CurrentProductIdentifierResult = $this->CurrentProductIdentifierByConstRepository
            ->forProduct($message->getProduct())
            ->forOfferConst($message->getOfferConst())
            ->forVariationConst($message->getVariationConst())
            ->forModificationConst($message->getModificationConst())
            ->find();

        /** Получаем цену с учетом торговой наценки */
        $price = $fourTochkiGetFindTyreResult->getPriceWithPercent();

        $updateProductPriceMessage = new UpdateProductPriceMessage()
            ->setEvent($CurrentProductIdentifierResult->getEvent())
            ->setOffer($CurrentProductIdentifierResult->getOffer())
            ->setVariation($CurrentProductIdentifierResult->getVariation())
            ->setModification($CurrentProductIdentifierResult->getModification())
            ->setPrice($price);

        /** Обновляем цену в карточке */
        $this->MessageDispatch->dispatch(
            message: $updateProductPriceMessage,
            transport: 'products-product',
        );

        $this->Logger->info(
            'Цена продукции в карточке успешно обновлена',
            [var_export($message, true), self::class.':'.__LINE__],
        );
    }
}