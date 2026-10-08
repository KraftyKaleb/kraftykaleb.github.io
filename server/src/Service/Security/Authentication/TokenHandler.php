<?php
declare(strict_types=1);

namespace App\Service\Security\Authentication;

use App\Service\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Resolves the user behind an "Authorization: Bearer <token>" header.
 */
final readonly class TokenHandler implements AccessTokenHandlerInterface {
    public function __construct(
        private TokenService $tokens,
        private UserRepository $users,
    ) {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge {
        $userId = $this->tokens->verify($accessToken) ?? throw new BadCredentialsException('Invalid access token.');

        return new UserBadge($userId, fn (string $id) => $this->users->find($id) ?? throw new UserNotFoundException());
    }
}
