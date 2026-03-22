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

namespace BaksDev\FourTochki\Products\Controller\Admin;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\FourTochki\Products\Entity\FourTochkiProduct;
use BaksDev\FourTochki\Products\UseCase\Delete\FourTochkiProductDeleteDTO;
use BaksDev\FourTochki\Products\UseCase\Delete\FourTochkiProductDeleteForm;
use BaksDev\FourTochki\Products\UseCase\Delete\FourTochkiProductDeleteHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_FOUR_TOCHKI_PRODUCTS_DELETE')]
final class DeleteController extends AbstractController
{
    #[Route('/admin/four-tochki/product/delete/{id}', name: 'admin.products.delete', methods: ['GET', 'POST'])]
    public function delete(
        Request $request,
        #[MapEntity] FourTochkiProduct $fourTochkiProduct,
        FourTochkiProductDeleteHandler $FourTochkiProductDeleteHandler,
    ): Response
    {
        $fourTochkiProductDeleteDTO = new FourTochkiProductDeleteDTO();
        $fourTochkiProduct->getDto($fourTochkiProductDeleteDTO);
        $form = $this
            ->createForm(
                FourTochkiProductDeleteForm::class,
                $fourTochkiProductDeleteDTO,
                ['action' => $this->generateUrl(
                    'four-tochki-products:admin.products.delete',
                    ['id' => $fourTochkiProductDeleteDTO->getId()],
                )],
            )
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('four_tochki_product_delete'))
        {
            $handle = $FourTochkiProductDeleteHandler->handle($fourTochkiProductDeleteDTO);

            $this->addFlash
            (
                'page.delete',
                $handle instanceof FourTochkiProduct ? 'success.delete' : 'danger.delete',
                'four-tochki-products.admin',
                $handle,
            );

            return $this->redirectToReferer();
        }

        return $this->render(['form' => $form->createView()]);
    }
}