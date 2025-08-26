<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\UnitKerja;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UnitKerjaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('kode', TextType::class, [
                'label' => 'Kode Unit Kerja',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: BAGTU, MADRASAH',
                    'maxlength' => 20
                ],
                'help' => 'Kode unik untuk unit kerja (maksimal 20 karakter)',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Kode unit kerja harus diisi'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 20,
                        'minMessage' => 'Kode minimal {{ limit }} karakter',
                        'maxMessage' => 'Kode maksimal {{ limit }} karakter'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[A-Z0-9]+$/',
                        'message' => 'Kode hanya boleh berisi huruf kapital dan angka'
                    ])
                ]
            ])
            ->add('nama', TextType::class, [
                'label' => 'Nama Unit Kerja',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: Bagian Tata Usaha',
                    'maxlength' => 255
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Nama unit kerja harus diisi'
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Nama minimal {{ limit }} karakter',
                        'maxMessage' => 'Nama maksimal {{ limit }} karakter'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UnitKerja::class,
        ]);
    }
}