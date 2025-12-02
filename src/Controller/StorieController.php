<?php

namespace App\Controller;

use App\Entity\Storie;
use App\Form\StorieType;
use App\Repository\StorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Repository\UserRepository;

#[Route('/storie')]
#[IsGranted('ROLE_USER')]
final class StorieController extends AbstractController
{
    // =========================
    // Listado de usuarios con historias
    // =========================

#[Route(name: 'app_storie_index', methods: ['GET'])]
public function index(StorieRepository $storieRepository, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
{
    // Actualizar visibilidad de historias que pasen las 24h
    $minDate = new \DateTime('-24 hours');

    $storiesToCheck = $storieRepository->createQueryBuilder('s')
        ->where('s.visible = :visible')
        ->setParameter('visible', true)
        ->getQuery()
        ->getResult();

    foreach ($storiesToCheck as $storie) {
        if ($storie->getDatetime() < $minDate) {
            $storie->setVisible(false);
            $entityManager->persist($storie);
        }
    }
    $entityManager->flush();

    // Obtener usuarios que tengan historias visibles
    $qb = $userRepository->createQueryBuilder('u')
        ->join('u.stories', 's')
        ->where('s.visible = :visible')
        ->setParameter('visible', true)
        ->orderBy('u.username', 'ASC')
        ->distinct();

    $users = $qb->getQuery()->getResult();

    return $this->render('storie/index.html.twig', [
        'users' => $users,
    ]);
}


    // =========================
    // Historias de un usuario
    // =========================
    #[Route('/user/{username}', name: 'app_storie_user_index', methods: ['GET'])]
    public function storiesByUser(StorieRepository $storieRepository, string $username): Response
    {
        $stories = $storieRepository->createQueryBuilder('s')
            ->join('s.author', 'u')
            ->where('u.username = :username')
            ->andWhere('s.visible = :visible')
            ->setParameter('username', $username)
            ->setParameter('visible', true)
            ->orderBy('s.datetime', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('storie/user_index.html.twig', [
            'stories' => $stories,
            'username' => $username,
        ]);
    }

    // =========================
    // Crear nueva historia
    // =========================
    #[Route('/new', name: 'app_storie_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $storie = new Storie();
        $form = $this->createForm(StorieType::class, $storie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Fecha actual
            $storie->setDatetime(new \DateTime());

            // Por defecto visible
            $storie->setVisible(true);

            // Autor actual
            $storie->setAuthor($this->getUser());

            // Obtener archivo de imagen
            $img = $form->get('img')->getData();

            if ($img) {
                $directory = $this->getParameter('storie_directory');

                $originalFilename = pathinfo($img->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$img->guessExtension();

                try {
                    $img->move($directory, $newFilename);
                } catch (FileException $e) {
                    // Manejar error de subida
                    $this->addFlash('error', 'Error al subir la imagen.');
                }

                $storie->setImg($newFilename);
            }

            $entityManager->persist($storie);
            $entityManager->flush();

            return $this->redirectToRoute('app_storie_index');
        }

        return $this->render('storie/new.html.twig', [
            'storie' => $storie,
            'form' => $form,
        ]);
    }

    // =========================
    // Mostrar historia
    // =========================
    #[Route('/{id}', name: 'app_storie_show', methods: ['GET'])]
    public function show(Storie $storie): Response
    {
        return $this->render('storie/show.html.twig', [
            'storie' => $storie,
        ]);
    }

    // =========================
    // Eliminar historia
    // =========================
    #[Route('/{id}', name: 'app_storie_delete', methods: ['POST'])]
    public function delete(Request $request, Storie $storie, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$storie->getId(), $request->request->get('_token'))) {
            $entityManager->remove($storie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_storie_index');
    }
}
