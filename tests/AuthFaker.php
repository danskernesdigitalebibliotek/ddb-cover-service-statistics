<?php

/**
 * @file
 * Auth faker.
 */

namespace App\Tests;

use App\Security\User;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Class AuthFaker.
 */
class AuthFaker
{
    private $tokenCache;

    /**
     * AuthFaker constructor.
     *
     * @param AdapterInterface $tokenCache
     */
    public function __construct(AdapterInterface $tokenCache)
    {
        $this->tokenCache = $tokenCache;
    }

    /**
     * Fake login.
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function login()
    {
        $token = '1234';
        $clientId = '5678';

        $user = new User();
        $user->setPassword($token);
        $user->setExpires(new \DateTime('now + 1 day'));
        $user->setAgency('775100');
        $user->setAuthType('anonymous');
        $user->setClientId($clientId);

        // Store access token in local cache.
        $item = $this->tokenCache->getItem($token);
        $item->set($user);
        $this->tokenCache->save($item);

        return [
            'Authorization' => "Bearer $token",
        ];
    }
}
