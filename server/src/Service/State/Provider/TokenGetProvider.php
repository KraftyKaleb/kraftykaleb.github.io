<?php
declare(strict_types=1);

namespace App\Service\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Model\Token;
use App\Service\Security\Authentication\TokenService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\AccessToken\HeaderAccessTokenExtractor;

/**
 * Describes the bearer token the current request was authenticated with.
 *
 * @implements ProviderInterface<Token>
 */
final readonly class TokenGetProvider implements ProviderInterface {
    public function __construct(
        private RequestStack $requestStack,
        private TokenService $tokens,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Token {
        $request = $this->requestStack->getCurrentRequest();
        $accessToken = $request === null ? null : new HeaderAccessTokenExtractor()->extractAccessToken($request);

        return $accessToken === null ? null : $this->tokens->read($accessToken);
    }
}
