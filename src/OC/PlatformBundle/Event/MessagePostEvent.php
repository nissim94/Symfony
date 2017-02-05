<?php
/**
 * Created by PhpStorm.
 * User: Nissim Chettrit
 * Date: 27/11/2016
 * Time: 16:07
 */

namespace OC\PlatformBundle\Event;



use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Core\User\UserInterface;

class MessagePostEvent extends Event
{
	protected $message;
	protected $user;

    /**
     * MessagePostEvent constructor.
     * @param $message
     * @param $user
     */
    public function __construct($message, UserInterface $user)
    {
        $this->message = $message;
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return UserInterface
     */
    public function getUser(): UserInterface
    {
        return $this->user;
    }
}