<?php

namespace AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use AppBundle\Repository\LanguageRepository;

class NewsCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, array(
            'label'    => 'admin.tytul',
            'translation_domain' => 'admin'
        ))->add('language', EntityType::class, array(
            'label'    => 'admin.jezyk',
            'translation_domain' => 'admin',
            'class'  => 'AppBundle:Language',
            'query_builder' => function (LanguageRepository $er) {
                return $er->createQueryBuilder('l')
                    ->where('l.enabled = :enabled')
                    ->setParameter(':enabled', true);
            },
            'choice_label' => 'shortcut'
        ));
    }

    public function getBlockPrefix()
    {
        return 'admin_news_category';
    }
}