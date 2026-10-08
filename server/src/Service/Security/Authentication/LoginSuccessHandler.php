<?php
declare(strict_types=1);

namespace App\Service\Security\Authentication;

use App\Model\Entity\User;
use DateTimeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Answers a successful PUT /token with a freshly issued access token.
 */
final readonly class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface {
    public function __construct(private TokenService $tokens) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse {
        $user = $token->getUser();
        if (!$user instanceof User) {
            throw new \LogicException(sprintf('Expected "%s", got "%s".', User::class, get_debug_type($user)));
        }

        ['token' => $accessToken, 'expires_at' => $expiresAt] = $this->tokens->issue($user);

        return new JsonResponse([
            'token' => $accessToken,
            'expires_at' => $expiresAt->format(DateTimeInterface::ATOM),
        ]);
    }
}
