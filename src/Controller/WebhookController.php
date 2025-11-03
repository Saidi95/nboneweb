<?php

namespace App\Controller;

use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController
{
    #[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
    public function stripeWebhook(Request $request): Response
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $endpointSecret = $_ENV['STRIPE_WEBHOOK_SECRET']; // clé secrète du webhook

        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $event = null;

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Payload invalide
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Signature invalide
            return new Response('Invalid signature', 400);
        }


        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object; // contient la session Stripe
                // Exemple : enregistrer la commande comme "payée"
                // Tu pourrais utiliser $session->id ou $session->customer_email
                // pour retrouver ton client/commande en BDD
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                // Marquer la commande comme échouée
                break;
        }

        return new Response('Webhook reçu', 200);
    }
}
