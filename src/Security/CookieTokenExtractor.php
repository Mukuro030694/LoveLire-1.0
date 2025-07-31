<?php
// src/Security/CookieTokenExtractor.php
namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\Request;

class CookieTokenExtractor implements TokenExtractorInterface
{
    private string $cookieName;

    public function __construct(string $cookieName = 'BEARER')
    {
        $this->cookieName = $cookieName;
    }

    public function extract(Request $request): ?string
    {
        return $request->cookies->get($this->cookieName);
    }
}
