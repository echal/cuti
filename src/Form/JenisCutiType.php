<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\JenisCuti;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class JenisCutiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('kode', TextType::class, [
                'label' => 'Kode Jenis Cuti',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: CT, CB, CS, CM',
                    'maxlength' => 10
                ],
                'help' => 'Kode unik untuk jenis cuti (maksimal 10 karakter)',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Kode jenis cuti harus diisi'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 10,
                        'minMessage' => 'Kode minimal 2 karakter',
                        'maxMessage' => 'Kode maksimal 10 karakter'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[A-Z0-9]+$/',
                        'message' => 'Kode hanya boleh berisi huruf kapital dan angka'
                    ])
                ]
            ])
            ->add('nama', TextType::class, [
                'label' => 'Nama Jenis Cuti',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: Cuti Tahunan',
                    'maxlength' => 255
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Nama jenis cuti harus diisi'
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Nama minimal 3 karakter',
                        'maxMessage' => 'Nama maksimal 255 karakter'
                    ])
                ]
            ])
            ->add('deskripsi', TextareaType::class, [
                'label' => 'Deskripsi',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Penjelasan detail tentang jenis cuti ini...'
                ],
                'help' => 'Penjelasan detail tentang jenis cuti (opsional)',
                'constraints' => [
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'Deskripsi maksimal 1000 karakter'
                    ])
                ]
            ])
            ->add('durasiMax', IntegerType::class, [
                'label' => 'Durasi Maksimal (Hari)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: 12',
                    'min' => 1,
                    'max' => 9999
                ],
                'help' => 'Maksimal hari cuti yang bisa diambil (kosongkan jika tidak ada batasan)',
                'constraints' => [
                    new Assert\Type([
                        'type' => 'integer',
                        'message' => 'Durasi maksimal harus berupa angka'
                    ]),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 9999,
                        'notInRangeMessage' => 'Durasi harus antara {{ min }} sampai {{ max }} hari'
                    ])
                ]
            ])
            ->add('dasarHukum', TextType::class, [
                'label' => 'Dasar Hukum',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: PP 11/2017',
                    'maxlength' => 500
                ],
                'help' => 'Dasar peraturan yang mengatur jenis cuti ini',
                'constraints' => [
                    new Assert\Length([
                        'max' => 500,
                        'maxMessage' => 'Dasar hukum maksimal 500 karakter'
                    ])
                ]
            ])
            ->add('tersediUntuk', ChoiceType::class, [
                'label' => 'Tersedia Untuk',
                'choices' => [
                    'Semua (PNS & PPPK)' => 'ALL',
                    'PNS Saja' => 'PNS',
                    'PPPK Saja' => 'PPPK'
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Menentukan siapa saja yang bisa menggunakan jenis cuti ini',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Status kepegawaian yang bisa menggunakan harus dipilih'
                    ]),
                    new Assert\Choice([
                        'choices' => ['ALL', 'PNS', 'PPPK'],
                        'message' => 'Pilihan tidak valid'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JenisCuti::class,
        ]);
    }
}