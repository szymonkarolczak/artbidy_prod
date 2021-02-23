<?php

namespace UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SocialMediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $files = glob('assets/images/social/*');
        $names = array_map(function($file) {
            return ucfirst(str_replace(array('social-1_square-', '.svg'), array('', ''), basename($file)));
        }, $files);
        $files = array_map(function($file) {
            return basename($file);
        }, $files);
        $choices = array_combine($names, $files);

        $builder->add('url', TextType::class, array(
            'label' => false,
            'attr' => array('placeholder' => 'main.social_media_link')
        ))->add('icon', ChoiceType::class, array(
            'label' => false,
            'choices' => $choices,
            'attr' => array(
                'class' => 'selectpicker'
            ),
            'choice_attr' => function ($allChoices, $currentChoiceKey) {
                return array(
                    'data-thumbnail' => '/assets/images/social/social-1_square-'.strtolower($currentChoiceKey).'.svg'
                );
            },
        ));
    }

    public function getBlockPrefix()
    {
        return 'app_user_socialMedia';
    }
}