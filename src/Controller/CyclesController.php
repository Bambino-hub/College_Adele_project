<?php

namespace App\Controller;

use App\Entity\Cycles;
use App\Form\CyclesType;
use App\Repository\CyclesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cycles')]
final class CyclesController extends AbstractController
{
    #[Route(name: 'app_cycles_index', methods: ['GET'])]
    public function index(CyclesRepository $cyclesRepository): Response
    {
        return $this->render('cycles/index.html.twig', [
            'cycles' => $cyclesRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_cycles_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cycle = new Cycles();
        $form = $this->createForm(CyclesType::class, $cycle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($cycle);
            $entityManager->flush();

            return $this->redirectToRoute('app_cycles_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('cycles/new.html.twig', [
            'cycle' => $cycle,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_cycles_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Cycles $cycle): Response
    {
        return $this->render('cycles/show.html.twig', [
            'cycle' => $cycle,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_cycles_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Cycles $cycle, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CyclesType::class, $cycle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_cycles_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('cycles/edit.html.twig', [
            'cycle' => $cycle,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_cycles_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Cycles $cycle, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $cycle->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($cycle);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_cycles_index', [], Response::HTTP_SEE_OTHER);
    }
}
