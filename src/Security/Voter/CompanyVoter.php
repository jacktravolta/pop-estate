<?php
namespace App\Security\Voter;

use App\Entity\Company;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CompanyVoter extends Voter
{
    public const VIEW = 'COMPANY_VIEW';
    public const CREATE = 'COMPANY_CREATE';
    public const EDIT = 'COMPANY_EDIT';
    public const DELETE = 'COMPANY_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::DELETE])
            && ($subject === null || $subject instanceof Company);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($user),
            self::CREATE => $this->canCreate($user),
            self::EDIT => $this->canEdit($user, $subject),
            self::DELETE => $this->canDelete($user, $subject),
            default => false,
        };
    }

    private function canView(User $user): bool
    {
        // Cualquier usuario autenticado puede ver empresas
        return true;
    }

    private function canCreate(User $user): bool
    {
        // ADMIN, ACCOUNTANT y USER pueden crear
        return in_array('ROLE_ADMIN', $user->getRoles())
            || in_array('ROLE_ACCOUNTANT', $user->getRoles())
            || in_array('ROLE_USER', $user->getRoles());
    }

    private function canEdit(User $user, ?Company $company): bool
    {
        // Solo ADMIN y ACCOUNTANT pueden editar
        return in_array('ROLE_ADMIN', $user->getRoles())
            || in_array('ROLE_ACCOUNTANT', $user->getRoles());
    }

    private function canDelete(User $user, ?Company $company): bool
    {
        // Solo ADMIN puede eliminar
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}