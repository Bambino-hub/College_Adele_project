<?php

namespace App\Controller;

use App\Entity\Enseignement;
use App\Form\EnseignementType;
use App\Repository\EnseignementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/enseignement')]
final class EnseignementController extends AbstractController
{
    #[Route(name: 'app_enseignement_index', methods: ['GET'])]
    public function index(EnseignementRepository $enseignementRepository): Response
    {
        return $this->render('enseignement/index.html.twig', [
            'enseignements' => $enseignementRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_enseignement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $enseignement = new Enseignement();
        $form = $this->createForm(EnseignementType::class, $enseignement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($enseignement);
            $entityManager->flush();

            return $this->redirectToRoute('app_enseignement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('enseignement/new.html.twig', [
            'enseignement' => $enseignement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_enseignement_show', methods: ['GET'])]
    public function show(Enseignement $enseignement): Response
    {
        return $this->render('enseignement/show.html.twig', [
            'enseignement' => $enseignement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_enseignement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Enseignement $enseignement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EnseignementType::class, $enseignement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_enseignement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('enseignement/edit.html.twig', [
            'enseignement' => $enseignement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_enseignement_delete', methods: ['POST'])]
    public function delete(Request $request, Enseignement $enseignement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$enseignement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($enseignement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_enseignement_index', [], Response::HTTP_SEE_OTHER);
    }
}
