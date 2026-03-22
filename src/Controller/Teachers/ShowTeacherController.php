<?php

namespace App\Controller\Teachers;

use App\Entity\Teatchers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/teatchers')]
final class ShowTeacherController extends AbstractController
{

    #[Route('/{id}', name: 'teatchers_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    /**
     * cette fonction permet d'afficher les details d'un enseignant
     * @return Response
     */
    public function show(Teatchers $teatcher): Response
    {
        return $this->render('teatchers/show.html.twig', [
            'teatcher' => $teatcher,
        ]);
    }
}
