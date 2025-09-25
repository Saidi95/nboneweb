<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Document\ContactMessage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;


class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact', methods: ['POST'])]
    public function contact(Request $request, DocumentManager $dm): JsonResponse
    {
        $contactMessage = new ContactMessage();
        $contactMessage->setName($request->request->get('name'));
        $contactMessage->setEmail($request->request->get('email'));
        $contactMessage->setMessage($request->request->get('message'));

        $dm->persist($contactMessage);
        $dm->flush();

        return new JsonResponse(['status' => 'success']);
    }
    #[Route('/contact', name: 'contact_form', methods: ['GET'])]
    public function contactForm(): Response
    {
        return $this->render('contact/contact.html.twig');
    }

}


