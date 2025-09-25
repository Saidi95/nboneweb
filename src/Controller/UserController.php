<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/admin/user', name: 'app_user')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll()
        ]);
    }

    #[Route('/admin/user/{id}/to/editor', name: 'app_user_to_editor')]
    public function addEditorRole(EntityManagerInterface $entityManager, User $user): Response
    {
        $roles = $user->getRoles();
        if (!in_array('ROLE_EDITOR', $roles, true)) {
            $roles[] = 'ROLE_EDITOR';
            $user->setRoles($roles);
            $entityManager->flush();
            $this->addFlash('success', 'Le rôle éditeur a été ajouté à l\'utilisateur.');
        } else {
            $this->addFlash('info', 'L\'utilisateur possède déjà le rôle éditeur.');
        }

        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/remove/editor/role', name: 'app_user_remove_editor_role')]
    public function removeEditorRole(EntityManagerInterface $entityManager, User $user): Response
    {
        $roles = $user->getRoles();
        $roles = array_values(array_filter($roles, fn($r) => $r !== 'ROLE_EDITOR'));

        // Si plus aucun rôle, au moins ROLE_USER
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        $user->setRoles($roles);
        $entityManager->flush();

        $this->addFlash('success', 'Le rôle éditeur a été retiré à l\'utilisateur.');

        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/delete', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {

            if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                $this->addFlash('danger', 'Impossible de supprimer un administrateur.');
                return $this->redirectToRoute('app_user');
            }

            if ($this->getUser() && $this->getUser()->getId() === $user->getId()) {
                $this->addFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
                return $this->redirectToRoute('app_user');
            }

            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_user');
    }
}
