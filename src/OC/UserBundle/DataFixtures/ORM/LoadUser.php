<?php
// src/OC/UserBundle/DataFixtures/ORM/LoadUser.php

namespace OC\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use OC\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUser implements FixtureInterface, ContainerAwareInterface
{
    private $container;
    
    /**
     * Sets the container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        // Les noms d'utilisateurs à créer
        $listNames = array('auteur', 'admin');        

        foreach ($listNames as $name) {
            // On crée l'utilisateur
            $user = new User;

            // Le nom d'utilisateur et le mot de passe sont identiques pour l'instant
            $user->setUsername($name);
            //$user->setPassword($name+'pass');
            $user->setEmail($name+'@exemple.com');
            

            // On ne se sert pas du sel pour l'instant
            $user->setSalt('');
            // On définit uniquement le role ROLE_USER qui est le role de base
            if ($name == "admin"){
                $user->setRoles(array('ROLE_ADMIN'));
            }
            else{
                $user->setRoles(array('ROLE_AUTEUR'));            
            }
            
            // On encode le mdp
            $encoder = $this->container->get('security.password_encoder');
            $password = $encoder->encodePassword($user, $name.'pass');
            // On définit le mdp encodé
            $user->setPassword($password);
            
            
            // On le persiste
            $manager->persist($user);
        }

        // On déclenche l'enregistrement
        $manager->flush();
    }
}