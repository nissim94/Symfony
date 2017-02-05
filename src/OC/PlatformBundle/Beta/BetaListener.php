<?php
/**
 * Created by PhpStorm.
 * User: Nissim Chettrit
 * Date: 17/11/2016
 * Time: 16:27
 */

namespace OC\PlatformBundle\Beta;


use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class BetaListener
{
    //notre processeur
    protected $betaHTML;

    //la date de fin de la version BETA:
    // - Avant cette date, on affichera un compte a rebours (j-3 par exemple)
    // - Apres cette date, on n'affichera plus le BETA
    protected $endDate;

    public function __construct(BetaHTMLAdder $betaHTML, $endDate)
    {
        $this->betaHTML = $betaHTML;
        $this->endDate = new \Datetime($endDate);
    }

    public function processBeta(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest())
            return;
        

        $remainingDays = $this->endDate->diff(new \Datetime())->days;

        if ($remainingDays <= 0)
            return;

        $response = $this->betaHTML->addBeta($event->getResponse(), $remainingDays);

        $event->setResponse($response);
    }
}