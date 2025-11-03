<?php

namespace App\Controller;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Repository\ProductRepository;

class PaymentController extends AbstractController
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    #[Route('/payment', name: 'app_payment', methods: ['POST'])]
    public function payment(SessionInterface $session): JsonResponse
    {
        // Configuration de la clé Stripe
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        // Récupération du panier en session
        $cart = $session->get('cart', []);
        $lineItems = [];

        foreach ($cart as $id => $quantity) {
            $product = $this->productRepository->find($id);
            if ($product) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $product->getName(),
                        ],
                        'unit_amount' => $product->getPrice() * 100, // prix en centimes
                    ],
                    'quantity' => $quantity,
                ];
            }
        }

        // Vérifie si le panier est vide
        if (empty($lineItems)) {
            return new JsonResponse(['error' => 'Votre panier est vide'], 400);
        }

        // Création de la session Stripe Checkout
        $checkoutSession = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return new JsonResponse(['id' => $checkoutSession->id]);
    }

    #[Route('/payment/success', name: 'app_success', methods: ['GET'])]
    public function success(SessionInterface $session): Response
    {
        // On vide le panier après paiement réussi
        $session->set('cart', []);

        return $this->render('payment/success.html.twig');
    }

    #[Route('/payment/cancel', name: 'app_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }
}
