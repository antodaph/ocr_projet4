<?php

namespace OC\PlatformBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

// Repository
use OC\PlatformBundle\Repository\CategoryRepository;

// Evenement Formulaire
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdvertType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Ajoutez ici tous vos champs sauf le champ published
        $builder->add('date',      DateTimeType::class)
                ->add('title',     TextType::class)
                ->add('author',    TextType::class)
                ->add('content',   CkeditorType::class) // TextareaType::class)
                ->add('image',     ImageType::class) // Ajoutez cette ligne
                ->add('categories', EntityType::class, array(
                          'class'        => 'OCPlatformBundle:Category',
                          'choice_label' => 'name',
                          'multiple'     => true,))
                ->add('save',      SubmitType::class);
        
        // On ajoute une fonction qui va écouter un évènement
        // 1er argument : L'évènement qui nous intéresse : ici, PRE_SET_DATA
        // 2e argument : La fonction à exécuter lorsque l'évènement est déclenché
        $builder->addEventListener(FormEvents::PRE_SET_DATA,function(FormEvent $event) { 
            // On récupère notre objet Advert sous-jacent
            $advert = $event->getData();

            // Cette condition est importante, on en reparle plus loin
            if (null === $advert) {
                return; // On sort de la fonction sans rien faire lorsque $advert vaut null
            }

            // Si l'annonce n'est pas publiée, ou si elle n'existe pas encore en base (id est null)
            if (!$advert->getPublished() || null === $advert->getId()) {
                // Alors on ajoute le champ published
                $event->getForm()->add('published', CheckboxType::class, array('required' => false));
            } 
            else {
            // Sinon, on le supprime
                $event->getForm()->remove('published');
            }
        });        
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'OC\PlatformBundle\Entity\Advert'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oc_platformbundle_advert';
    }

    /**
     * Il s'agit du code avant avec la notion d'héritage - et des formulaires imbriques
     */
    public function buildForm_1(FormBuilderInterface $builder, array $options)
    {
        // Arbitrairement, on récupère toutes les catégories qui commencent par "D"
        $pattern = 'D%';
        
        //$builder->add('date')->add('title')->add('author')->add('content')->add('published')->add('updatedAt')->add('nbApplications')->add('slug')->add('image')->add('categories');
        $builder->add('date',      DateTimeType::class)
                ->add('title',     TextType::class)
                ->add('author',    TextType::class)
                ->add('content',   TextareaType::class)
                ->add('published', CheckboxType::class, array('required' => false))
                ->add('image',     ImageType::class) // Ajoutez cette ligne
                /*
                * Rappel :
                ** - 1er argument : nom du champ, ici « categories », car c'est le nom de l'attribut
                ** - 2e argument : type du champ, ici « CollectionType » qui est une liste de quelque chose
                ** - 3e argument : tableau d'options du champ
                */
                /*->add('categories', CollectionType::class, array(
                      'entry_type'   => CategoryType::class,
                      'allow_add'    => true,
                      'allow_delete' => true))*/
                ->add('categories', EntityType::class, array(
                      'class'        => 'OCPlatformBundle:Category',
                      'choice_label' => 'name',
                      'multiple'     => true,
                      'query_builder' => function(CategoryRepository $repository) use($pattern) {
                          return $repository->getLikeQueryBuilder($pattern);
                      }
                ))
                ->add('save',      SubmitType::class);
    }
    
    
    
}