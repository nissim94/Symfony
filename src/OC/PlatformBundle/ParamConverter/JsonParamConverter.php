<?php
/**
 * Created by PhpStorm.
 * User: Nissim Chettrit
 * Date: 02/01/2017
 * Time: 14:56
 */

namespace OC\PlatformBundle\ParamConverter;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class JsonParamConverter implements ParamConverterInterface
{

    public function apply(Request $request, ParamConverter $configuration)
    {
        // On récupère la valeur actuelle de l'attribut
        $json = $request->attributes->get('json');

        // On effectue notre action : le décoder
        $json = json_decode($json, true);

        // On met à jour la nouvelle valeur de l'attribut
        $request->attributes->set('json', $json);
    }

    public function supports(ParamConverter $configuration)
    {
        if('json' !== $configuration->getName()){
            return false;
        }
        return true;
    }
}