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

namespace BaksDev\FourTochki\Products\Messenger\UpdateOneFourTochkiProductStock;

use BaksDev\FourTochki\Api\GetFindTyre\FourTochkiGetFindTyreRequest;
use BaksDev\FourTochki\Api\GetFindTyre\FourTochkiGetFindTyreResult;
use BaksDev\FourTochki\Products\Repository\FourTochkiProductProfile\FourTochkiProductProfileInterface;
use BaksDev\FourTochki\Products\UseCase\NewEdit\FourTochkiProductDTO;
use BaksDev\Products\Stocks\Entity\Total\ProductStockTotal;
use BaksDev\Products\Stocks\Repository\ProductStocksTotalStorage\ProductStocksTotalStorageInterface;
use BaksDev\Products\Stocks\UseCase\Admin\EditTotal\ProductStockTotalEditDTO;
use BaksDev\Products\Stocks\UseCase\Admin\EditTotal\ProductStockTotalEditHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final readonly class UpdateOneFourTochkiProductStockDispatcher
{
    public function __construct(
        #[Target('fourTochkiLogger')] private LoggerInterface $Logger,
        private FourTochkiGetFindTyreRequest $FourTochkiGetFindTyreRequest,
        private ProductStockTotalEditHandler $ProductStockTotalEditHandler,
        private ProductStocksTotalStorageInterface $ProductStocksTotalStorageRepository,
        private EntityManagerInterface $EntityManager,
        private FourTochkiProductProfileInterface $FourTochkiProductProfileRepository,
    ) {}

    /**
     * Получаем информацию об остатках данного товара на складе 4tochki и обновляем остаток в соответствии с полученными
     * данными
     */
    public function __invoke(UpdateOneFourTochkiProductStockMessage $message): void
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

        /** Если нет настройки на изменение остатка на складе */
        if(false === $fourTochkiProductDTO->getRefresh()->getValue())
        {
            return;
        }

        /**
         * Обновляем остаток на складе
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


        /** Получаем текущий складской остаток для данных продукта и профиля, если таковой имеется */
        $productStocksTotal = $this->ProductStocksTotalStorageRepository
            ->product($message->getProduct())
            ->offer($message->getOfferConst())
            ->variation($message->getVariationConst())
            ->modification($message->getModificationConst())
            ->profile($message->getProfile())
            ->storage('4tochki')
            ->find();


        /** Если отсутствует место складирования - создаем на указанный профиль пользователя */
        if(false === ($productStocksTotal instanceof ProductStockTotal))
        {
            $productStocksTotal = new ProductStockTotal(
                $message->getUser(),
                $message->getProfile(),
                $message->getProduct(),
                $message->getOfferConst(),
                $message->getVariationConst(),
                $message->getModificationConst(),
                '4tochki',
            );

            $this->EntityManager->persist($productStocksTotal);
            $this->EntityManager->flush();

            $this->Logger->info(
                'Место складирования профиля не найдено! Создали новое место для указанной продукции',
                [
                    self::class.':'.__LINE__,
                    'profile' => (string) $message->getProfile(),
                ],
            );
        }


        $productStockTotalEditDTO = new ProductStockTotalEditDTO();
        $productStocksTotal->getDto($productStockTotalEditDTO);

        $productStockTotalEditDTO
            ->setTotal($fourTochkiGetFindTyreResult->getQuantity())
            ->setStorage('4tochki');


        /** Обновляем остаток на складе */
        $handle = $this->ProductStockTotalEditHandler->handle($productStockTotalEditDTO);

        if(false === ($handle instanceof ProductStockTotal))
        {
            $this->Logger->critical(
                'Не удалось обновить остаток продукта на складе',
                [var_export($message, true), self::class.':'.__LINE__],
            );

            return;
        }

        $this->Logger->info(
            'Остаток продукции на складе успешно обновлен',
            [var_export($message, true), self::class.':'.__LINE__],
        );

    }
}