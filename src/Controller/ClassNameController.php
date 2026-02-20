<?php

namespace App\Controller;

use App\Entity\ClassName;
use App\Form\ClassNameType;
use App\Repository\ClassNameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/class/name')]
final class ClassNameController extends AbstractController
{
    #[Route(name: 'app_class_name_index', methods: ['GET'])]
    public function index(ClassNameRepository $classNameRepository): Response
    {
        return $this->render('class_name/index.html.twig', [
            'class_names' => $classNameRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_class_name_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $className = new ClassName();
        $form = $this->createForm(ClassNameType::class, $className);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($className);
            $entityManager->flush();

            return $this->redirectToRoute('app_class_name_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('class_name/new.html.twig', [
            'class_name' => $className,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_class_name_show', methods: ['GET'])]
    public function show(ClassName $className): Response
    {
        return $this->render('class_name/show.html.twig', [
            'class_name' => $className,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_class_name_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ClassName $className, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClassNameType::class, $className);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_class_name_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('class_name/edit.html.twig', [
            'class_name' => $className,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_class_name_delete', methods: ['POST'])]
    public function delete(Request $request, ClassName $className, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $className->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($className);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_class_name_index', [], Response::HTTP_SEE_OTHER);
    }
}
