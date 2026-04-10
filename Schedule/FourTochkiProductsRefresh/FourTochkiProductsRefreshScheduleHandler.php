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

namespace BaksDev\FourTochki\Products\Schedule\FourTochkiProductsRefresh;

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\FourTochki\Products\Messenger\UpdateFourTochkiProducts\UpdateFourTochkiProductsMessage;
use BaksDev\FourTochki\Repository\AllFourTochkiAuth\AllFourTochkiAuthInterface;
use BaksDev\FourTochki\Repository\AllFourTochkiAuth\AllFourTochkiAuthResult;
use BaksDev\FourTochki\Repository\AllProfileAuth\AllProfileFourTochkiAuthInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final readonly class FourTochkiProductsRefreshScheduleHandler
{
    public function __construct(
        private MessageDispatchInterface $messageDispatch,
        private AllProfileFourTochkiAuthInterface $AllProfileFourTochkiRepository,
    ) {}

    public function __invoke(FourTochkiProductsRefreshScheduleMessage $message): void
    {
        /** Получаем все активные профили, у которых активная авторизация */
        $profiles = $this->AllProfileFourTochkiRepository
            ->onlyActive()
            ->findAll();

        if(true === $profiles || false === $profiles->valid())
        {
            return;
        }

        foreach($profiles as $UserProfileUid)
        {
            $this->messageDispatch->dispatch(
                message: new UpdateFourTochkiProductsMessage($UserProfileUid),
                transport: (string) $UserProfileUid,
            );
        }
    }
}
