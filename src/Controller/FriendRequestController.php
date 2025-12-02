<?php

namespace App\Controller;

use App\Entity\FriendRequest;
use App\Entity\User;
use App\Repository\FriendRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')] //solo usuarios logueados
#[Route('/friend-request')] //es el prefijo de la ruta
class FriendRequestController extends AbstractController
{
    //EntityManager -> gestor de entidades, que se encarga de crear registros en la base de datos, editarlos, borrarlos etc
    //persiste() -> registra un objeto como pendiente de guardar
    //flush() -> Guarda en la base de datos
    //remove() -> elimina un objeto de la base de datos
    // 1) ENVIAR SOLICITUD
    #[Route('/send/{id}', name: 'app_friend_request_send', methods: ['GET'])]
    public function send(
        User $user,
        FriendRequestRepository $repo,
        EntityManagerInterface $em
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // No puedes enviarte solicitud a ti misma
        if ($currentUser === $user) {
            return $this->redirectToRoute('app_user_show', [
                'username' => $user->getUsername(),
            ]);
        }

        // Si el usuario NO es privado, se le sigue directamente
        if (!$user->isPrivate()) {
            $user->addFollower($currentUser);
            $em->flush();

            return $this->redirectToRoute('app_user_show', [
                'username' => $user->getUsername(),
            ]);
        }

        // Si es privado, miramos si ya hay una solicitud pendiente
        $existing = $repo->findOneBy([
            'sender'   => $currentUser,
            'receiver' => $user,
            'status'   => 'PENDING',
        ]);

        // Si no existe, la creamos
        if (!$existing) {
            $request = new FriendRequest();
            $request->setSender($currentUser);
            $request->setReceiver($user);
            $request->setStatus('PENDING');

            $em->persist($request);
            $em->flush();
        }

        return $this->redirectToRoute('app_user_show', [
            'username' => $user->getUsername(),
        ]);
    }

    // 2) ACEPTAR SOLICITUD
    #[Route('/accept/{id}', name: 'app_friend_request_accept', methods: ['GET'])]
    public function accept(FriendRequest $friendRequest, EntityManagerInterface $em): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Solo el receptor puede aceptarla
        if ($friendRequest->getReceiver() !== $currentUser) {
            throw $this->createAccessDeniedException();
        }

        $friendRequest->setStatus('ACCEPTED');

        $sender = $friendRequest->getSender();
        $receiver = $friendRequest->getReceiver();

        // Se hacen "amigos": el sender pasa a seguir al receiver
        $receiver->addFollower($sender);

        $em->flush();

        return $this->redirectToRoute('app_user_show', [
            'username' => $currentUser->getUsername(),
        ]);
    }

    // 3) RECHAZAR SOLICITUD
    #[Route('/reject/{id}', name: 'app_friend_request_reject', methods: ['GET'])]
    public function reject(FriendRequest $friendRequest, EntityManagerInterface $em): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($friendRequest->getReceiver() !== $currentUser) {
            throw $this->createAccessDeniedException();
        }

        $friendRequest->setStatus('REJECTED');
        $em->flush();

        return $this->redirectToRoute('app_user_show', [
            'username' => $currentUser->getUsername(),
        ]);
    }
}

