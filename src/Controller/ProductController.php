<?php

namespace App\Controller;

use App\Entity\AddProductHistory;
use App\Entity\Product;
use App\Form\AddProductHistoryType;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('editor/product')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();

            $image = $form->get('image')->getData();

            if ($image) {
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileName = $slugger->slug($originalName);
                $newFileName  = $safeFileName . '-' . uniqid() . '.' . $image->guessExtension();

                $image->move($this->getParameter('image_dir'), $newFileName);
                $product->setImage($newFileName);
            }


            $entityManager->persist($product);

            // Historique de stock initial
            $stockHistory = new AddProductHistory();
            $stockHistory->setQte($product->getStock());
            $stockHistory->setProduct($product);
            $stockHistory->setCreatedAt(new \DateTimeImmutable());
            $stockHistory->setType('initial'); // <-- assignation obligatoire
            $entityManager->persist($stockHistory);

            $entityManager->flush();

            $this->addFlash('success', 'Votre produit a été ajouté.');
            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Votre produit a été modifié.');
            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->request->get('_token');

        if ($this->isCsrfTokenValid('delete' . $product->getId(), $token)) {
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('danger', 'Votre produit a été supprimé.');
        }

        return $this->redirectToRoute('app_product_index');
    }

    #[Route('/add/product/{id}/stock', name: 'app_product_stock_add', methods: ['GET', 'POST'])]
    public function addStock(
        Product $product,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $addStock = new AddProductHistory();
        $form     = $this->createForm(AddProductHistoryType::class, $addStock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $qte = $addStock->getQte();

            // Mise à jour du stock
            $product->setStock($product->getStock() + $qte);

            // Historique
            $addStock->setProduct($product);
            $addStock->setCreatedAt(new \DateTimeImmutable());
            $addStock->setType('add');
            $addStock->setReason('Ajout manuel de stock');

            $entityManager->persist($addStock);
            $entityManager->flush();

            $this->addFlash('success', 'Stock ajouté avec succès.');
            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/addStock.html.twig', [
            'form'    => $form->createView(),
            'product' => $product,
        ]);
    }
}
