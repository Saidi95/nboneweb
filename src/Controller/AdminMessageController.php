<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Document\ContactMessage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminMessageController extends AbstractController
{
    #[Route('/admin/messages', name: 'admin_messages')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminPage(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('admin/messages.html.twig');
    }

    #[Route('/admin/messages/json', name: 'admin_messages_json')]
    #[IsGranted('ROLE_ADMIN')]
    public function messagesJson(DocumentManager $dm): JsonResponse
    {
        $messages = $dm->getRepository(ContactMessage::class)->findBy([], ['createdAt' => 'DESC']);

        $data = array_map(fn($msg) => [
            'name' => $msg->getName(),
            'email' => $msg->getEmail(),
            'message' => $msg->getMessage(),
            'createdAt' => $msg->getCreatedAt()->format('d/m/Y H:i')
        ], $messages);

        return new JsonResponse($data);
    }
}

