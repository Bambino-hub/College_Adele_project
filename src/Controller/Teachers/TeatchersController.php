<?php

namespace App\Controller\Teachers;

use App\Repository\TeatchersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/teatchers')]
final class TeatchersController extends AbstractController
{

    public function  __construct(private readonly TeatchersRepository $teatchersRepository) {}

    #[Route(name: 'teatchers_index', methods: ['GET'])]
    /**
     * cette fonction permet d'afficher la liste des enseignants
     * @return Response
     */
    public function index(): Response
    {
        return $this->render('teatchers/index.html.twig', [
            'teatchers' => $this->teatchersRepository->findBy([], ['lastname' => 'ASC', 'firstname' => 'ASC']),
        ]);
    }
}
