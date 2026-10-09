<?php
declare(strict_types=1);

namespace App\Service\Security\Authentication;

use App\Model\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\Exception\InvalidTypeException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Answers a successful PUT /api/token with a freshly issued access token.
 */
final readonly class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface {
    public function __construct(
        private TokenService $tokens,
        private SerializerInterface $serializer,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse {
        $user = $token->getUser();
        if (!$user instanceof User) {
            throw new InvalidTypeException(User::class, get_debug_type($user), 'user');
        }

        return JsonResponse::fromJsonString($this->serializer->serialize($this->tokens->issue($user), 'json'));
    }
}
