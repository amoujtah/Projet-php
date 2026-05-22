<?php
// src/Form/PlatType.php

namespace App\Form;

use App\Entity\Plat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
// RETIREZ FileType
// use Symfony\Component\Form\Extension\Core\Type\FileType;
// use Symfony\Component\Validator\Constraints\File;

class PlatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du plat',
                'attr' => [
                    'placeholder' => 'Ex: Filet de bœuf Rossini',
                    'class' => 'form-control'
                ]
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Entrées' => 'Entrées',
                    'Plats principaux' => 'Plats principaux',
                    'Desserts' => 'Desserts',
                    'Boissons' => 'Boissons',
                    'Fromages' => 'Fromages',
                    'Apéritifs' => 'Apéritifs',
                    'Menus spéciaux' => 'Menus spéciaux'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Description détaillée du plat...',
                    'class' => 'form-control'
                ]
            ])
            ->add('prix', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
                'attr' => [
                    'placeholder' => '24.50',
                    'class' => 'form-control'
                ]
            ])
            // RETIREZ CE CHAMP :
            // ->add('image', FileType::class, [
            //     'label' => 'Image du plat',
            //     'mapped' => false,
            //     'required' => false,
            //     'constraints' => [
            //         new File([
            //             'maxSize' => '1024k',
            //             'mimeTypes' => [
            //                 'image/jpeg',
            //                 'image/png',
            //                 'image/webp',
            //             ],
            //             'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, WebP)',
            //         ])
            //     ],
            //     'attr' => ['class' => 'form-control']
            // ])
            ->add('disponible', CheckboxType::class, [
                'label' => 'Disponible à la carte',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('createdAt', DateTimeType::class, [
                'label' => 'Date d\'ajout',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Plat::class,
        ]);
    }
}