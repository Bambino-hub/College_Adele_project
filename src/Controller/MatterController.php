<?php

namespace App\Controller;

use App\Entity\Matter;
use App\Form\MatterType;
use App\Repository\MatterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/matter')]
final class MatterController extends AbstractController
{
    #[Route(name: 'app_matter_index', methods: ['GET'])]
    public function index(MatterRepository $matterRepository): Response
    {
        return $this->render('matter/index.html.twig', [
            'matters' => $matterRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_matter_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $matter = new Matter();
        $form = $this->createForm(MatterType::class, $matter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($matter);
            $entityManager->flush();

            return $this->redirectToRoute('app_matter_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('matter/new.html.twig', [
            'matter' => $matter,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_matter_show', methods: ['GET'])]
    public function show(Matter $matter): Response
    {
        return $this->render('matter/show.html.twig', [
            'matter' => $matter,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_matter_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Matter $matter, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MatterType::class, $matter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_matter_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('matter/edit.html.twig', [
            'matter' => $matter,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_matter_delete', methods: ['POST'])]
    public function delete(Request $request, Matter $matter, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $matter->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($matter);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_matter_index', [], Response::HTTP_SEE_OTHER);
    }
}
