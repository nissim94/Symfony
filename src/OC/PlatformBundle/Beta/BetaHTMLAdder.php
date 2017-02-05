<?php

namespace OC\PlatformBundle\Beta;

use Symfony\Component\HttpFoundation\Response;

class BetaHTMLAdder
{

    public function addBeta(Response $response, int $remainingDays) : Response
    {
        $content = $response->getContent();

        // Code à rajouter
        // (Je mets ici du CSS en ligne, mais il faudrait utiliser un fichier CSS bien sûr !)
        $html = "<div style='position: absolute; top: 0; background: orange; width: 100%; text-align: center; padding: 0.5em;'>
                    Beta J-$remainingDays !
                 </div>";

        $content = str_replace(
            '<body>',
            '<body>'. $html,
            $content
        );

        //modif de ma reponse retournée
        $response->setContent($content);

        return $response;
    }
}