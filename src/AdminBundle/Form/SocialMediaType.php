<?php

namespace AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SocialMediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('url', UrlType::class, array(
            'label'    => 'ogolne.social_media.url',
            'translation_domain' => 'admin'
        ))->add('enabled', CheckboxType::class, array(
            'label'    => 'admin.aktywnosc',
            'translation_domain' => 'admin',
            'required' => false,
            'attr'     => array('checked' => 'checked'),
        ))->add('icon', ChoiceType::class, array(
            'label'    => 'ogolne.social_media.ikonka',
            'translation_domain' => 'admin',
            'choices' => $this->getSocialIcons()
        ));
    }

    public function getBlockPrefix()
    {
        return 'admin_social_media';
    }

    private function getSocialIcons()
    {
        $files = glob('assets/images/social/*');
        $files = array_map(function($file) {
            return basename($file);
        }, $files);
        return array_combine($files, $files);
    }
}