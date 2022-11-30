<?php

namespace App\Security;

use App\Entity\User as AppUser;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AdminChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {

    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        $identifier = $user->getUserIdentifier();
        $roles = $user->getRoles();

        if (!in_array('ROLE_ADMIN', $roles)) {
            $ex = new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
            $ex->setUserIdentifier($identifier);

            throw new BadCredentialsException('Bad credentials.', 0, $ex);
        }
    }
}
