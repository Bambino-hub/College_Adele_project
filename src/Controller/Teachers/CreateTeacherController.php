<?php

namespace App\Controller\Teachers;

use App\Entity\Teatchers;
use App\Form\TeatchersType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/teatchers')]
final class CreateTeacherController extends AbstractController
{

    public function  __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack
    ) {}



    #[Route('/create', name: 'teatchers_create', methods: ['GET', 'POST'])]

    /**
     * cette fonction permet de creer un nouveau enseignant
     * @return Response|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function new(): Response
    {
        $teatcher = new Teatchers();
        $form = $this->createForm(TeatchersType::class, $teatcher);
        $form->handleRequest($this->requestStack->getCurrentRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($teatcher);
            $this->entityManager->flush();

            // turbo request -> return a turbo-stream response so the list updates
            if ($this->requestStack->getCurrentRequest()->headers->get('Turbo-Frame')) {
                return $this->render('teatchers/create.turbo-stream.html.twig', [
                    'teatcher' => $teatcher,
                ], new Response('', 200, ['Content-Type' => 'text/vnd.turbo-stream.html']));
            }

            return $this->redirectToRoute('teatchers_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('teatchers/new.html.twig', [
            'teatcher' => $teatcher,
            'form' => $form,
        ]);
    }
}
