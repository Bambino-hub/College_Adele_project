<?php

namespace App\Controller\Teachers;

use App\Entity\Teatchers;
use App\Form\TeatchersType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/teatchers')]
final class EdithTeachersController extends AbstractController
{
    #[Route('/{id}/edit', name: 'teatchers_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    /**
     * cette fonction permet de modifier un enseignant
     * @return Response|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Request $request, Teatchers $teatcher, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TeatchersType::class, $teatcher);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('teatchers_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('teatchers/edit.html.twig', [
            'teatcher' => $teatcher,
            'form' => $form,
        ]);
    }
}
