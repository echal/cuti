<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Password Saat Ini',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Password saat ini harus diisi'])
                ]
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Konfirmasi password tidak sama.',
                'required' => true,
                'first_options' => [
                    'label' => 'Password Baru',
                    'attr' => ['class' => 'form-control'],
                    'help' => 'Password minimal 6 karakter'
                ],
                'second_options' => [
                    'label' => 'Konfirmasi Password Baru',
                    'attr' => ['class' => 'form-control']
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Password baru harus diisi']),
                    new Assert\Length([
                        'min' => 6,
                        'minMessage' => 'Password minimal {{ limit }} karakter'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}