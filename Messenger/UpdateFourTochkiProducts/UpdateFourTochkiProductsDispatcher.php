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

namespace BaksDev\FourTochki\Products\Messenger\UpdateFourTochkiProducts;

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\FourTochki\Products\Forms\FourTochkiFilter\FourTochkiProductsFilterDTO;
use BaksDev\FourTochki\Products\Messenger\UpdateOneFourTochkiProductPrice\UpdateOneFourTochkiProductPriceMessage;
use BaksDev\FourTochki\Products\Messenger\UpdateOneFourTochkiProductStock\UpdateOneFourTochkiProductStockMessage;
use BaksDev\FourTochki\Products\Repository\AllProductsWithFourTochkiSettings\AllProductsWithFourTochkiSettingsInterface;
use BaksDev\FourTochki\Products\Repository\AllProductsWithFourTochkiSettings\AllProductsWithFourTochkiSettingsResult;
use BaksDev\Users\Profile\UserProfile\Repository\UserByUserProfile\UserByUserProfileInterface;
use BaksDev\Users\User\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final readonly class UpdateFourTochkiProductsDispatcher
{
    public function __construct(
        private LoggerInterface $Logger,
        private AllProductsWithFourTochkiSettingsInterface $AllProductsWithFourTochkiSettingsRepository,
        private MessageDispatchInterface $MessageDispatch,
        private UserByUserProfileInterface $UserByUserProfileRepository,
    ) {}

    /** Находим все продукты данного профиля и бросаем сообщение на обновление их остатков и цены */
    public function __invoke(UpdateFourTochkiProductsMessage $message): void
    {
        /** Получаем пользователя, в которого авторизуемся */
        $user = $this->UserByUserProfileRepository
            ->forProfile($message->getProfile())
            ->find();

        if(false === ($user instanceof User))
        {
            $this->Logger->critical(
                'Пользователь не был найден по данному профилю',
                [var_export($message, true), self::class.':'.__LINE__],
            );

            return;
        }


        /** Получаем все продукты для данного профиля */
        $result = $this->AllProductsWithFourTochkiSettingsRepository
            ->filterFourTochkiProducts(new FourTochkiProductsFilterDTO()->setExists(true))
            ->findPaginator()
            ->getData();


        /** @var  AllProductsWithFourTochkiSettingsResult $allProductsWithFourTochkiSettingsResult */
        foreach($result as $allProductsWithFourTochkiSettingsResult)
        {
            /** Пропускаем продукты без модификации */
            if(
                true === empty($allProductsWithFourTochkiSettingsResult->getProductOfferValue()) ||
                true === empty($allProductsWithFourTochkiSettingsResult->getProductVariationValue()) ||
                true === empty($allProductsWithFourTochkiSettingsResult->getProductModificationValue())
            )
            {
                continue;
            }


            /** Отправляем сообщение на обновление остатка */
            $this->MessageDispatch->dispatch(
                new UpdateOneFourTochkiProductStockMessage(
                    $allProductsWithFourTochkiSettingsResult->getId(),
                    $allProductsWithFourTochkiSettingsResult->getProductOfferConst(),
                    $allProductsWithFourTochkiSettingsResult->getProductVariationConst(),
                    $allProductsWithFourTochkiSettingsResult->getProductModificationConst(),
                    $user->getId(),
                    $message->getProfile(),
                ),
                transport: (string) $message->getProfile(),
            );


            /** Отправляем сообщение на обновление цены */
            $this->MessageDispatch->dispatch(
                new UpdateOneFourTochkiProductPriceMessage(
                    $allProductsWithFourTochkiSettingsResult->getId(),
                    $allProductsWithFourTochkiSettingsResult->getProductOfferConst(),
                    $allProductsWithFourTochkiSettingsResult->getProductVariationConst(),
                    $allProductsWithFourTochkiSettingsResult->getProductModificationConst(),

                    $message->getProfile(),
                ),
                transport: (string) $message->getProfile(),
            );
        }
    }
}