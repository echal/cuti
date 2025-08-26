<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use App\Entity\UnitKerja;
use App\Repository\UnitKerjaRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nama', TextType::class, [
                'label' => 'Nama Lengkap',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Nama tidak boleh kosong']),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Nama tidak boleh lebih dari {{ limit }} karakter'
                    ])
                ]
            ])
            ->add('nip', TextType::class, [
                'label' => 'NIP',
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 50,
                        'maxMessage' => 'NIP tidak boleh lebih dari {{ limit }} karakter'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'constraints' => [
                    new Assert\Email(['message' => 'Format email tidak valid'])
                ]
            ])
            ->add('telp', TelType::class, [
                'label' => 'Nomor Telepon',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Contoh: 08123456789'],
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 20,
                        'maxMessage' => 'Nomor telepon tidak boleh lebih dari {{ limit }} karakter'
                    ])
                ]
            ])
            ->add('jenisKelamin', ChoiceType::class, [
                'label' => 'Jenis Kelamin',
                'attr' => ['class' => 'form-select'],
                'choices' => [
                    'Pilih Jenis Kelamin' => '',
                    'Laki-laki' => 'L',
                    'Perempuan' => 'P'
                ],
                'required' => false
            ])
            ->add('jabatan', TextType::class, [
                'label' => 'Jabatan',
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Jabatan tidak boleh lebih dari {{ limit }} karakter'
                    ])
                ]
            ])
            ->add('golongan', TextType::class, [
                'label' => 'Golongan',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Contoh: III/a - Penata Muda'],
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 50,
                        'maxMessage' => 'Golongan tidak boleh lebih dari {{ limit }} karakter'
                    ])
                ]
            ])
            ->add('unitKerja', EntityType::class, [
                'label' => 'Unit Kerja',
                'class' => UnitKerja::class,
                'choice_label' => 'nama',
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Pilih Unit Kerja',
                'required' => false,
                'query_builder' => function (UnitKerjaRepository $repo) {
                    return $repo->createQueryBuilder('uk')->orderBy('uk.nama', 'ASC');
                }
            ])
            ->add('statusKepegawaian', ChoiceType::class, [
                'label' => 'Status Kepegawaian',
                'attr' => ['class' => 'form-select'],
                'choices' => [
                    'Pilih Status Kepegawaian' => '',
                    'PNS' => 'PNS',
                    'PPPK' => 'PPPK',
                    'Honorer' => 'Honorer'
                ],
                'required' => false
            ])
            ->add('masaKerja', TextType::class, [
                'label' => 'Masa Kerja',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Contoh: 5 tahun 3 bulan'],
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 50,
                        'maxMessage' => 'Masa kerja tidak boleh lebih dari {{ limit }} karakter'
                    ])
                ]
            ])
            ->add('tmtCpns', DateType::class, [
                'label' => 'TMT CPNS',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'help' => 'Tanggal Mulai Tugas sebagai CPNS'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}