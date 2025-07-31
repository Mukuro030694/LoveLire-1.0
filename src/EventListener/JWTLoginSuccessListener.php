<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\HttpFoundation\Cookie;

class JWTLoginSuccessListener
{
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $token = $event->getData()['token'];
        $response = $event->getResponse();

        
        $cookie = Cookie::create('BEARER')
            ->withValue($token)
            ->withPath('/')
            ->withHttpOnly(true)
            ->withSecure(false) // Set to true in production
            ->withSameSite('Lax');

        $response->headers->setCookie($cookie);
    }
}
