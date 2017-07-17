<?php

namespace OC\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface; // Plus besoin d'implémenterUserInterface, car on    hérite de l'entité Userdu bundleFOSUB, qui, elle, implémente cette interface. Pas besoin non plus          d'écrire tous les setters et getters, ils sont tous hérités, même le getter getId 
//use FOS\UserBundle\Model\User as BaseUser;

    
/**
 * User
 *
 * @ORM\Table(name="oc_user")
 * @ORM\Entity(repositoryClass="OC\UserBundle\Repository\UserRepository")
 */
class User implements UserInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    
    private $id;

    /**
    * @ORM\Column(name="username", type="string", length=255, unique=true)
    */
    private $username;

    /**
    * @ORM\Column(name="email", type="string", length=255)
    */
    private $email;
    
    /**
    * @ORM\Column(name="password", type="string", length=255)
    */
    private $password;

    /**
    * @ORM\Column(name="salt", type="string", length=255)
    */
    private $salt;

    /**
    * @ORM\Column(name="roles", type="array")
    */
    private $roles = array();

    // Les getters et setters
    public function getUsername(){
        return $this->username;
    }
    
    public function getRoles(){
        if (count($this->roles)>0){
            return $this->roles;
        }
        else
        {
            return array('ROLE_USER');
        }

    }
    
    public function getPassword(){
        return $this->password;
    
    }
    
    public function getSalt(){
        return $this->salt;
    }
    
    public function eraseCredentials()
    {
    }    

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set salt
     *
     * @param string $salt
     *
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Set roles
     *
     * @param array $roles
     *
     * @return User
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
}
