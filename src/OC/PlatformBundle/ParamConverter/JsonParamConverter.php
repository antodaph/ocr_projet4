<?php
// src/OC/PlatformBundle/ParamConverter/JsonParamConverter.php

namespace OC\PlatformBundle\ParamConverter;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class JsonParamConverter implements ParamConverterInterface
{
    /*La méthode supports() doit retourner true lorsque le convertisseur souhaite convertir le paramètre en question, false sinon. Les informations sur le paramètre courant sont stockées dans l'argument $configuration, et contiennent :
        $configuration->getClass() : le typage de l'argument dans la méthode du contrôleur ;
        $configuration->getName() : le nom de l'argument dans la méthode du contrôleur ;
        $configuration->getOptions() : les options de l'annotation, si elles sont explicitées (vide bien sûr lorsqu'il n'y a pas l'annotation).*/
    function supports(ParamConverter $configuration)
    {
        // Si le nom de l'argument du contrôleur n'est pas "json", on n'applique pas le convertisseur
        if ('json' !== $configuration->getName()) {
          return false;
        }

        return true;
    }

    /*La méthode apply() doit effectivement créer un attribut de requête, qui sera injecté dans l'argument de la méthode du contrôleur.
    Ce travail peut être effectué grâce à ses deux arguments :
        La configuration, qui contient les informations sur l'argument de la méthode du contrôleur, que nous avons vu juste au-dessus ;
        La requête, qui contient tout ce que vous savez, et notamment les paramètres de la route courante via $request->attributs->get('paramètre_de_route').*/
    /**
     * {@inheritdoc}
     *
     * @throws BadRequestHttpException When object is not a valid json
     */
    function apply(Request $request, ParamConverter $configuration)
    {
        $name = $configuration->getName();
        $class = $configuration->getClass();
        
        // On récupère la valeur actuelle de l'attribut
        $json = $request->attributes->get('json');

        // On effectue notre action : le décoder
        $json = json_decode($json, true);
        
        $errorMessage = null;
        if(!json_last_error()=== JSON_ERROR_NONE){
            $errorMessage=sprintf('Content is not a valid json', $class, 
                                  $this->getAnnotationName($configuration));
            //$this->log("Content was not a valid json string");
            throw new BadRequestHttpException($errorMessage);
        }

        // On met à jour la nouvelle valeur de l'attribut
        $request->attributes->set('json', $json);

    }
}