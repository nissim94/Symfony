<?php
/**
 * Created by PhpStorm.
 * User: Nissim Chettrit
 * Date: 31/10/2016
 * Time: 10:22
 */

namespace OC\PlatformBundle\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AntifloodValidator extends ConstraintValidator
{
    private $requestStack;
    private $em;

    /**
     * AntifloodValidator constructor.
     * @param RequestStack $requestStack
     * @param EntityManagerInterface $em
     */
    public function __construct(RequestStack $requestStack, EntityManagerInterface $em)
    {
        $this->requestStack = $requestStack;
        $this->em = $em;
    }

    /**
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $request = $this->requestStack->getCurrentRequest();
        $ip = $request->getClientIp();

        $isFlood = $this->em
            ->getRepository('OCPlatformBundle:Application')
            ->isFlood($ip, 15);

        if ($isFlood)
            $this->context->addViolation($constraint->message);
    }
}