<?php

namespace App\Security;

use App\Entity\Proposition;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Controle qu'un adherent ne peut supprimer que SES propres propositions.
 * Les admins sont geres par le firewall (ROLE_ADMIN sur /admin/*).
 *
 * @extends Voter<string, Proposition>
 */
final class PropositionVoter extends Voter
{
    public const DELETE = 'proposition_delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::DELETE && $subject instanceof Proposition;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Proposition $subject */
        return $subject->getAuteur() === $user;
    }
}
