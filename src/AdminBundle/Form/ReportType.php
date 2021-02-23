<?php

namespace AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, array(
            'label' => 'main.tytul',
        ))->add('description', TextType::class, array(
            'label' => 'add_work.opis',
        ))->add('price', NumberType::class, array(
            'label' => 'add_work.cena',
        ))->add('filename', FileType::class, array(
            'label' => 'raporty.plik',
            'translation_domain' => 'admin',
            'required' => false
        ))->add('image', FileType::class, array(
            'label' => 'raporty.image',
            'translation_domain' => 'admin',
            'required' => false
        ));
    }

    public function getBlockPrefix()
    {
        return 'admin_report';
    }
}