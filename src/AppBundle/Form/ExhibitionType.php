<?php

namespace AppBundle\Form;

use AdminBundle\Form\ExhibitionLangType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

use AdminBundle\Form\DataTransformer\UserToUsernameTransformer;
use Doctrine\Common\Persistence\ObjectManager;

class ExhibitionType extends AbstractType
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('langs', CollectionType::class, array(
            'entry_type' => ExhibitionLangType::class,
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'translation_domain' => 'admin'
        ))->add('address', TextType::class, array(
            'label'    => 'user.adres',
        ))->add('city', TextType::class, array(
            'label'    => 'user.miasto',
        ))->add('startDate', DateTimeType::class, array(
            'label'    => 'main.data_start',
            'years' => range(date('Y'), date('Y')+2)
        ))->add('endDate', DateTimeType::class, array(
            'label'    => 'main.data_koniec',
            'years' => range(date('Y'), date('Y')+2)
        ))->add('image', FileType::class, array(
            'label'    => 'add_work.grafika',
        ))->add('users', CollectionType::class, array(
            'entry_type'   => TextType::class,
            'entry_options' => array(
                'attr'      => array('class' => 'user-box')
            ),
            'allow_add' => true,
            'allow_delete' => true,
            'label'    => false,
            'required' => false,
        ))->add('submit', SubmitType::class, array(
            'label'    => 'main.zapisz',
            'attr'     => array('class' => 'btn-success')
        ));
        
        $builder->get('users')
            ->addModelTransformer(new UserToUsernameTransformer($this->manager));
    }

    public function getBlockPrefix()
    {
        return 'app_exhibition';
    }
}