<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\HariLibur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class HariLiburType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tanggal', DateType::class, [
                'label' => 'Tanggal Libur',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Tanggal libur harus diisi']),
                ],
                'help' => 'Pilih tanggal hari libur nasional atau daerah'
            ])
            ->add('keterangan', TextType::class, [
                'label' => 'Keterangan',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: Hari Kemerdekaan RI, Idul Fitri, dll',
                    'maxlength' => 100
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Keterangan hari libur harus diisi']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Keterangan tidak boleh lebih dari {{ limit }} karakter'
                    ]),
                ],
                'help' => 'Nama atau keterangan hari libur (maks 100 karakter)'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HariLibur::class,
        ]);
    }
}