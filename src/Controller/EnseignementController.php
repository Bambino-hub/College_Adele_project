<?php

namespace App\Controller;

use App\Entity\Enseignement;
use App\Entity\ClassName;
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
        // return $this->json($enseignementRepository->findAll(), Response::HTTP_OK, [], ['groups' => 'Enseignement:read']);
    }

    #[Route('/new', name: 'app_enseignement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EnseignementRepository $enseignementRepository): Response
    {
        $enseignement = new Enseignement();
        $form = $this->createForm(EnseignementType::class, $enseignement, [
            'bulk_creation' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedClasses = $form->get('classNames')->getData();

            if (count($selectedClasses) === 0) {
                $this->addFlash('warning', 'Veuillez selectionner au moins une classe.');

                return $this->render('enseignement/new.html.twig', [
                    'enseignement' => $enseignement,
                    'form' => $form,
                ]);
            }

            $createdCount = 0;

            foreach ($selectedClasses as $className) {
                if (!$className instanceof ClassName) {
                    continue;
                }

                $existing = $enseignementRepository->findOneBy([
                    'teacher' => $enseignement->getTeacher(),
                    'matter' => $enseignement->getMatter(),
                    'className' => $className,
                ]);

                if ($existing !== null) {
                    continue;
                }

                $newEnseignement = new Enseignement();
                $newEnseignement
                    ->setTeacher($enseignement->getTeacher())
                    ->setMatter($enseignement->getMatter())
                    ->setClassName($className);

                $entityManager->persist($newEnseignement);
                ++$createdCount;
            }

            if ($createdCount === 0) {
                $this->addFlash('warning', 'Aucun enseignement cree: les associations existent deja.');

                return $this->render('enseignement/new.html.twig', [
                    'enseignement' => $enseignement,
                    'form' => $form,
                ]);
            }

            $entityManager->flush();

            $this->addFlash('success', sprintf('%d enseignement(s) cree(s) avec succes.', $createdCount));

            return $this->redirectToRoute('app_enseignement_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('enseignement/new.html.twig', [
            'enseignement' => $enseignement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_enseignement_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Enseignement $enseignement): Response
    {
        return $this->render('enseignement/show.html.twig', [
            'enseignement' => $enseignement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_enseignement_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
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

    #[Route('/{id}', name: 'app_enseignement_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Enseignement $enseignement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $enseignement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($enseignement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_enseignement_index', [], Response::HTTP_SEE_OTHER);
    }
}
