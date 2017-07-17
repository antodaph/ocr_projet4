<?php
// src/userBundle/Security/FormAuthenticator.php

namespace OC\UserBundle\Security;

use Symfony\Component\HttpFoundation\RedirectResponse; 
use Symfony\Component\HttpFoundation\Request; 
use Symfony\Component\HttpFoundation\Response; 
use Symfony\Component\Routing\RouterInterface; 
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface; 
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\Exception\AuthenticationException; 
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException; 
use Symfony\Component\Security\Core\Security; 
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class FormAuthenticator extends AbstractGuardAuthenticator
{
    private $failMessage = 'Bad credentials'; 
    private $encoder; 
    private $router;
    
    public function __construct(UserPasswordEncoder $encoder, RouterInterface $router) 
    { 
        $this->encoder = $encoder; 
        $this->router = $router;
    }
    
    // Dirige vers la page de login (clique sur login ou page protégée par login)
    public function start(Request $request, AuthenticationException $authException = null) 
    { 
        return new RedirectResponse('login', $status = 401); 
    }
    
    // On récupéère les infos saisi par l'utilisateur dans le formulaire
    public function getCredentials(Request $request) 
    { 
        if ($request->request->has('_username')) {
            return array( 'username' => $request->request->get('_username'), 
                          'password' => $request->request->get('_password'), ); 
        } else { 
            return; 
        } 
    }
    
    // Recupération des infos en bdd à partir de son pseudo
    public function getUser($credentials, UserProviderInterface $userProvider) 
    { 
        $username = $credentials['username']; 
        return $userProvider->loadUserByUsername($username); 
    }
    
    // Verification form=bdd
    public function checkCredentials($credentials, UserInterface $user) 
    { 
        if ($this->encoder->isPasswordValid($user, $credentials['password'])) { 
            return true; 
        } 
    
        throw new CustomUserMessageAuthenticationException($this->failMessage); 
    }
    // En cas d'erreur d'authentification
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception) 
    { 
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception); 
        return new RedirectResponse($this->router->generate('login')); 
    }
    
    // En cas de succès d'authentification
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey) 
    { 
        return new RedirectResponse($this->router->generate('oc_core_homepage')); 
    }
    
    // Fonction remember me
    public function supportsRememberMe() 
    { 
        return false; 
    }
    
    
}