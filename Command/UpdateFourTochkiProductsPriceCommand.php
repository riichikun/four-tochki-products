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

namespace BaksDev\FourTochki\Products\Command;

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\FourTochki\Products\Forms\FourTochkiFilter\FourTochkiProductsFilterDTO;
use BaksDev\FourTochki\Products\Messenger\UpdateOneFourTochkiProductPrice\UpdateOneFourTochkiProductPriceMessage;
use BaksDev\FourTochki\Products\Repository\AllProductsWithFourTochkiSettings\AllProductsWithFourTochkiSettingsInterface;
use BaksDev\FourTochki\Products\Repository\AllProductsWithFourTochkiSettings\AllProductsWithFourTochkiSettingsResult;
use BaksDev\FourTochki\Repository\AllProfileAuth\AllProfileFourTochkiAuthInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserByUserProfile\UserByUserProfileInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Entity\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Получаем карточки товаров и обновляем цены в карточках
 */
#[AsCommand(name: 'four-tochki:update:price', description: 'Обновляет цену товаров с 4tochki')]
class UpdateFourTochkiProductsPriceCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly AllProfileFourTochkiAuthInterface $AllProfileFourTochkiAuthRepository,
        private readonly UserByUserProfileInterface $UserByUserProfileRepository,
        private readonly AllProductsWithFourTochkiSettingsInterface $AllProductsWithFourTochkiSettingsRepository,
        private readonly MessageDispatchInterface $MessageDispatch,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'article',
            'a',
            InputOption::VALUE_OPTIONAL,
            'Фильтр по артикулу ((--article=... || -a ...))',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        /** Получаем все активные профили, у которых активная авторизация */
        $profiles = $this->AllProfileFourTochkiAuthRepository
            ->onlyActive()
            ->findAll();

        if(false === $profiles || false === $profiles->valid())
        {
            $this->io->warning('Активных профилей пользователя не найдено');
            return Command::FAILURE;
        }

        $profiles = iterator_to_array($profiles);

        $helper = $this->getHelper('question');


        /**
         * Интерактивная форма списка профилей
         */

        $questions[] = 'Все';

        foreach($profiles as $quest)
        {
            $questions[] = $quest->getAttr();
        }

        $questions['+'] = 'Выполнить все асинхронно';
        $questions['-'] = 'Выйти';

        $question = new ChoiceQuestion(
            'Профиль пользователя (Ctrl+C чтобы выйти)',
            $questions,
            '0',
        );

        $key = $helper->ask($input, $output, $question);

        /**
         *  Выходим без выполненного запроса
         */

        if($key === '-' || $key === 'Выйти')
        {
            return Command::SUCCESS;
        }


        /**
         * Выполняем все с возможностью асинхронно в очереди
         */

        if($key === '+' || $key === '0' || $key === 'Все')
        {
            /** @var UserProfileUid $profile */
            foreach($profiles as $profile)
            {
                $this->update($profile, $input->getOption('article'), $key === '+');
            }

            $this->io->success('Обновление успешно запущены');
            return Command::SUCCESS;
        }


        /**
         * Выполняем определенный профиль
         */

        $userProfileUid = null;

        foreach($profiles as $profile)
        {
            if($profile->getAttr() === $questions[$key])
            {
                /* Присваиваем профиль пользователя */
                $userProfileUid = $profile;
                break;
            }
        }

        if($userProfileUid)
        {
            $this->update($userProfileUid, $input->getOption('article'));

            $this->io->success('Цены успешно обновлены');
            return Command::SUCCESS;
        }


        $this->io->success('Профиль пользователя не найден');
        return Command::SUCCESS;

    }

    public function update(UserProfileUid $userProfileUid, ?string $article = null, bool $async = false): void
    {
        $this->io->note(sprintf('Обновляем профиль %s', $userProfileUid->getAttr()));

        /** Получаем пользователя, в которого авторизуемся  */
        $user = $this->UserByUserProfileRepository
            ->forProfile($userProfileUid)
            ->find();

        if(false === ($user instanceof User))
        {
            $this->io->note('Пользователь не был найден по данному профилю');
            return;
        }


        /** Получаем все продукты для данного профиля */
        $result = $this->AllProductsWithFourTochkiSettingsRepository
            ->profile($userProfileUid)
            ->filterFourTochkiProducts(new FourTochkiProductsFilterDTO()->setExists(true))
            ->findPaginator()
            ->getData();

        /** @var AllProductsWithFourTochkiSettingsResult $allProductsWithFourTochkiSettingsResult */
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

            if(
                !empty($article) &&
                stripos($allProductsWithFourTochkiSettingsResult->getProductArticle(), $article) === false
            )
            {
                $this->io->writeln(sprintf(
                    '<fg=gray>... %s</>',
                    $allProductsWithFourTochkiSettingsResult->getProductArticle(),
                ));

                continue;
            }

            $this->io->note(sprintf(
                'Обновляем артикул %s',
                $allProductsWithFourTochkiSettingsResult->getProductArticle(),
            ));

            /** Отправляем сообщение0  */
            $this->MessageDispatch->dispatch(
                new UpdateOneFourTochkiProductPriceMessage(
                    $allProductsWithFourTochkiSettingsResult->getId(),
                    $allProductsWithFourTochkiSettingsResult->getProductOfferId(),
                    $allProductsWithFourTochkiSettingsResult->getProductVariationId(),
                    $allProductsWithFourTochkiSettingsResult->getProductModificationId(),

                    $allProductsWithFourTochkiSettingsResult->getProductOfferConst(),
                    $allProductsWithFourTochkiSettingsResult->getProductVariationConst(),
                    $allProductsWithFourTochkiSettingsResult->getProductModificationConst(),

                    $userProfileUid,
                ),
                transport: $async === true ? $userProfileUid.'-low' : null,
            );

            if($allProductsWithFourTochkiSettingsResult->getProductArticle() === $article)
            {
                break;
            }
        }
    }
}
