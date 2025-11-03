<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SubCategoryRepository;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response
    {
        // Récupère tous les produits triés par ID décroissant
        $data = $productRepository->findBy([], ['id' => 'DESC']);

        // Pagination
        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1), // page courante
            4 // nombre de produits par page
        );

        return $this->render('home/index.html.twig', [
            'products'   => $products,
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/home/product/{id}/show', name: 'app_home_product_show', methods: ['GET'])]
    public function show(
        Product $product,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response
    {
        // Derniers produits pour la sidebar ou suggestions
        $lastProducts = $productRepository->findBy([], ['id' => 'DESC'], 5);

        return $this->render('home/show.html.twig', [
            'product'    => $product,
            'products'   => $lastProducts,
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/home/product/subcategory/{id}/filter', name: 'app_home_product_filter', methods: ['GET'])]
    public function filter(
        int $id,
        SubCategoryRepository $subCategoryRepository,
        CategoryRepository $categoryRepository
    ): Response
    {
        $subcategory = $subCategoryRepository->find($id);

        if (!$subcategory) {
            throw $this->createNotFoundException('Sous-catégorie introuvable.');
        }

        return $this->render('home/filter.html.twig', [
            'products'    => $subcategory->getProducts(),
            'subcategory' => $subcategory,
            'categories'  => $categoryRepository->findAll(),
        ]);
    }
}
