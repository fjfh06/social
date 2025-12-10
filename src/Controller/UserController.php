<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Form\UserEditType;
use App\Entity\Md;
use App\Entity\ChatMessage;
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
    $form = $this->createForm(UserEditType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        $newPassword = $form->get('password')->getData();

        if ($newPassword) {
            $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_user_show', [
            'username' => $user->getUsername()
        ]);
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

        // Todos los chats del usuario actual
        $chats = $user->getMds();

        return $this->render('user/chat.html.twig', [
            'user' => $user,
            'messages' => $chats,
        ]);
    }

    #[Route('/chat/detail/{id}', name: 'user_chat_detail', methods: ['GET'])]
    public function chatDetail(int $id, EntityManagerInterface $em): Response
    {
        $chat = $em->getRepository(Md::class)->find($id);

        if (!$chat) {
            throw $this->createNotFoundException('Chat no encontrado.');
        }

        $otherUser = $chat->getUsers()
                        ->filter(fn($u) => $u !== $this->getUser())
                        ->first();

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
        $existingChat = null;
        foreach ($currentUser->getMds() as $chat) {
            if ($chat->getUsers()->contains($otherUser) && $chat->getUsers()->count() === 2) {
                $existingChat = $chat;
                break;
            }
        }

        if ($existingChat) {
            return $this->redirectToRoute('user_chat_detail', ['id' => $existingChat->getId()]);
        }

        // Crear un nuevo chat
        $chat = new Md();
        $chat->setDaySent(new \DateTimeImmutable());
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

        if ($currentUser && $text) {
            $message = new ChatMessage();
            $message->setChat($chat);
            $message->setAuthor($currentUser);
            $message->setText($text);
            $message->setCreatedAt(new \DateTimeImmutable());

            $chat->setDaySent(new \DateTimeImmutable());

            $em->persist($message);
            $em->flush();
        }

        return $this->redirectToRoute('user_chat_detail', ['id' => $chat->getId()]);
    }



}
