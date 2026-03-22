<?php

namespace App\Controller\Teachers;

use App\Entity\Teatchers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/teatchers')]

/**
 * cette classe permet de supprimer un enseignant
 */
final class DeleteTeacherController extends AbstractController
{
    #[Route('/{id}', name: 'teatchers_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Teatchers $teatcher, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $teatcher->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($teatcher);
            $entityManager->flush();
        }

        return $this->redirectToRoute('teatchers_index', [], Response::HTTP_SEE_OTHER);
    }
}
