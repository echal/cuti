<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nip', TextType::class, [
                'label' => 'NIP',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Masukkan 18 digit NIP Anda',
                    'maxlength' => 18,
                    'pattern' => '\d{18}',
                    'title' => 'NIP harus berupa 18 digit angka'
                ],
                'help' => 'Contoh: 198201262008011013'
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Masukkan password Anda',
                    'minlength' => 8
                ],
                'help' => 'Minimal 8 karakter dengan huruf kecil, huruf besar, dan angka'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}