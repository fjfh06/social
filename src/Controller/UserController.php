<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Entity\Md;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{username}', name: 'app_user_show', methods: ['GET'])]
    public function show(#[MapEntity(mapping: ['username' => 'username'])] User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/follow', name: 'app_user_follow', methods: ['GET'])]
    public function follow(int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        }

        $currentUser = $this->getUser();
        if ($currentUser && $currentUser !== $user) {
            $currentUser->addFollowing($user);
            $em->flush();
        }

        return $this->redirectToRoute('app_post_index');
    }

    #[Route('/{id}/unfollow', name: 'app_user_unfollow', methods: ['GET'])]
    public function unfollow(int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        }

        $currentUser = $this->getUser();
        if ($currentUser && $currentUser !== $user) {
            $currentUser->removeFollowing($user);
            $em->flush();
        }

        return $this->redirectToRoute('app_post_index');
    }

    #[Route('/{id}/chat', name: 'user_chat', methods: ['GET'])]
    public function chat(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        }

        // Obtener todos los mensajes Md relacionados con este usuario
        $messages = $user->getMds() ?? [];

        return $this->render('user/chat.html.twig', [
            'user' => $user,
            'messages' => $messages,
        ]);
    }

    #[Route('/chat/detail/{id}', name: 'user_chat_detail', methods: ['GET'])]
    public function chatDetail(int $id, EntityManagerInterface $em): Response
    {
        $chat = $em->getRepository(Md::class)->find($id);

        if (!$chat) {
            throw $this->createNotFoundException('Chat no encontrado.');
        }

        // Obtener los usuarios del chat excepto el actual
        $otherUser = $chat->getUsers()
                        ->filter(fn($u) => $u !== $this->getUser())
                        ->first();

        // No lanzar excepción, sino pasar null a Twig
        return $this->render('user/chat_detail.html.twig', [
            'chat' => $chat,
            'otherUser' => $otherUser ?: null,
        ]);
    }

    #[Route('/chat/new/{userId}', name: 'user_chat_new', methods: ['GET'])]
    public function newChat(int $userId, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $currentUser = $this->getUser();
        $otherUser = $userRepository->find($userId);

        if (!$otherUser) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        }

        if ($currentUser === $otherUser) {
            $this->addFlash('warning', 'No puedes iniciar un chat contigo mismo.');
            return $this->redirectToRoute('app_user_index');
        }

        // Buscar chat existente entre los dos usuarios
        $qb = $em->createQueryBuilder()
            ->select('c')
            ->from(Md::class, 'c')
            ->join('c.users', 'u')
            ->andWhere('u = :user1 OR u = :user2')
            ->setParameter('user1', $currentUser)
            ->setParameter('user2', $otherUser)
            ->groupBy('c.id')
            ->having('COUNT(u) = 2')
            ->setMaxResults(1);

        $existingChat = $qb->getQuery()->getOneOrNullResult();

        if ($existingChat) {
            // Redirigir al chat existente
            return $this->redirectToRoute('user_chat_detail', ['id' => $existingChat->getId()]);
        }

        // Si no existe, crear un nuevo chat
        $chat = new Md();
        $chat->setDaySent(new \DateTime());
        $chat->addUser($currentUser);
        $chat->addUser($otherUser);

        $em->persist($chat);
        $em->flush();

        return $this->redirectToRoute('user_chat_detail', ['id' => $chat->getId()]);
    }



    #[Route('/chat/{id}/send', name: 'user_chat_send', methods: ['POST'])]
    public function sendMessage(Request $request, Md $chat, EntityManagerInterface $em): Response
    {
        $currentUser = $this->getUser();
        $text = $request->request->get('text');

        if ($text) {
            $chat->setText($text); // Si quieres guardar solo el último mensaje en Md
            // Si quieres guardar cada mensaje como entidad aparte, deberías crear entidad Message
            $em->flush();
        }

        return $this->redirectToRoute('user_chat_detail', ['id' => $chat->getId()]);
    }


}
