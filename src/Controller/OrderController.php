<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    #[Route('/success', name: 'app_success')]
    public function success(): Response
    {
        return $this->render('order/success.html.twig', [
            'message' => 'Paiement réussi ! Merci pour votre commande.'
        ]);
    }

    #[Route('/cancel', name: 'app_cancel')]
    public function cancel(): Response
    {
        return $this->render('order/cancel.html.twig', [
            'message' => 'Paiement annulé. Vous pouvez réessayer.'
        ]);
    }
}
