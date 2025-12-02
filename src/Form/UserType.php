<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
<<<<<<< HEAD
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
=======
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
>>>>>>> md
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username')
<<<<<<< HEAD
            ->add('isPrivate', CheckboxType::class, [
                'label' => 'Cuenta privada',
                'required' => false,
            ]);
        // NADA más: ni roles ni password
=======
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Usuario' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                ],
                'multiple' => true,   // permite seleccionar varios roles
                'expanded' => true,   // lo muestra como checkboxes
            ])
            ->add('password')
            ->add('likes', EntityType::class, [
                'class' => Post::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
            ->add('following', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
            ->add('followers', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
                'multiple' => true,
            ]);
>>>>>>> md
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
