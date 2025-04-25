<?php

namespace App\Controller;

use App\Entity\SweetFood;
use App\Form\SweetFoodForm;
use App\Repository\SweetFoodRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sweet/food')]
final class SweetFoodController extends AbstractController
{
    #[Route(name: 'app_sweet_food_index', methods: ['GET'])]
    public function index(SweetFoodRepository $sweetFoodRepository): Response
    {
        return $this->render('sweet_food/index.html.twig', [
            'sweet_foods' => $sweetFoodRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_sweet_food_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sweetFood = new SweetFood();
        $form = $this->createForm(SweetFoodForm::class, $sweetFood);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($sweetFood);
            $entityManager->flush();

            return $this->redirectToRoute('app_sweet_food_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sweet_food/new.html.twig', [
            'sweet_food' => $sweetFood,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sweet_food_show', methods: ['GET'])]
    public function show(SweetFood $sweetFood): Response
    {
        return $this->render('sweet_food/show.html.twig', [
            'sweet_food' => $sweetFood,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sweet_food_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SweetFood $sweetFood, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SweetFoodForm::class, $sweetFood);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_sweet_food_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sweet_food/edit.html.twig', [
            'sweet_food' => $sweetFood,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sweet_food_delete', methods: ['POST'])]
    public function delete(Request $request, SweetFood $sweetFood, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sweetFood->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($sweetFood);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_sweet_food_index', [], Response::HTTP_SEE_OTHER);
    }
}
