<?php

namespace AdminBundle\Form;

use AppBundle\Repository\NewsCategoryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AdminBundle\Form\DataTransformer\UserToUsernameTransformer;
use Doctrine\Common\Persistence\ObjectManager;

class NewsType extends AbstractType
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('langs', CollectionType::class, array(
            'entry_type' => NewsLangType::class,
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'translation_domain' => 'admin'
        ))->add('slug', TextType::class, array(
            'label'     =>  'slug',
            'translation_domain' => 'admin',
            'required' => true,
        ))->add('image', FileType::class, array(
            'label'    => false,
            'required' => false,
        ))->add('users', CollectionType::class, array(
            'entry_type'   => TextType::class,
            'entry_options' => array(
                'attr'      => array('class' => 'user-box')
            ),
            'allow_add' => true,
            'allow_delete' => true,
            'label'    => false,
            'required' => false,
        ))->add('category', EntityType::class, array(
            'label'    => false,
            'class' => 'AppBundle\Entity\NewsCategory',
            'choice_label' => 'langs.first.title',
            'query_builder' => function (NewsCategoryRepository $er) {
                return $er->createQueryBuilder('nc')
                    ->join('nc.langs', 'l')->addSelect('l')
                    ->join('l.lang', 'lg')
                    ->where('lg.shortcut = :lang')
                    ->setParameter(':lang', 'pl')
                    ->groupBy('nc.id');
            },
        ));

        if(
            in_array('ROLE_ADMIN', $options['role']) ||
            in_array('ROLE_SUPER_ADMIN', $options['role'])
        )
        {
            $builder->add('pinned', CheckboxType::class, array(
                'label' => 'aktualnosci.wyroznione',
                'translation_domain' => 'admin',
                'required' => false
            ));
        }

        $builder->get('users')
            ->addModelTransformer(new UserToUsernameTransformer($this->manager));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\News',
            'role' => ['ROLE_ADMIN']
        ));
    }

    public function getBlockPrefix()
    {
        return 'admin_news';
    }
}