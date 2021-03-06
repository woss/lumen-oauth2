<?php namespace Nord\Lumen\OAuth2;

use Exception;
use Illuminate\Contracts\Container\Container;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\Storage\RefreshTokenInterface;
use Nord\Lumen\OAuth2\Contracts\OAuth2Service as OAuth2ServiceContract;
use Nord\Lumen\OAuth2\Exceptions\InvalidArgument;
use Nord\Lumen\OAuth2\Facades\OAuth2Service as OAuthServiceFacade;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Storage\AccessTokenInterface;
use League\OAuth2\Server\Storage\ClientInterface;
use League\OAuth2\Server\Storage\ScopeInterface;
use League\OAuth2\Server\Storage\SessionInterface;

class OAuth2ServiceProvider extends ServiceProvider
{

    /**
     * @inheritdoc
     */
    public function register()
    {
        $this->registerBindings($this->app, $this->app['config']);
        $this->registerFacades();
    }


    /**
     * Registers container bindings.
     *
     * @param Container $container
     * @param array     $config
     */
    protected function registerBindings(Container $container, ConfigRepository $config)
    {
        $container->alias(OAuth2Service::class, OAuth2ServiceContract::class);

        $container->bind(OAuth2Service::class, function ($container) use ($config) {
            return $this->createService($container, $config['oauth2']);
        });
    }


    /**
     * Registers facades.
     */
    protected function registerFacades()
    {
        class_alias(OAuthServiceFacade::class, 'OAuth2');
    }


    /**
     * Creates the service instance.
     *
     * @param Application $container
     * @param array       $config
     *
     * @return OAuth2Service
     */
    protected function createService(Container $container, array $config)
    {
        $authorizationServer = $this->createAuthorizationServer($container, $config);
        $resourceServer      = $this->createResourceServer($container);

        return new OAuth2Service($authorizationServer, $resourceServer);
    }


    /**
     * Creates the authorization instance.
     *
     * @param Container $container
     * @param array     $config
     *
     * @return AuthorizationServer
     * @throws Exception
     */
    protected function createAuthorizationServer(Container $container, array $config)
    {
        // TODO: Support scopes

        $authorizationServer = $container->make(AuthorizationServer::class);

        $authorizationServer->setSessionStorage($container->make(SessionInterface::class));
        $authorizationServer->setAccessTokenStorage($container->make(AccessTokenInterface::class));
        $authorizationServer->setRefreshTokenStorage($container->make(RefreshTokenInterface::class));
        $authorizationServer->setClientStorage($container->make(ClientInterface::class));
        $authorizationServer->setScopeStorage($container->make(ScopeInterface::class));

        $this->configureAuthorizationServer($authorizationServer, $config);

        return $authorizationServer;
    }


    /**
     * Configures the authorization server instance.
     *
     * @param AuthorizationServer $authorizationServer
     * @param array               $config
     */
    protected function configureAuthorizationServer(AuthorizationServer $authorizationServer, array $config)
    {
        if (isset($config['scope_param'])) {
            $authorizationServer->requireScopeParam($config['scope_param']);
        }
        if (isset($config['default_scope'])) {
            $authorizationServer->setDefaultScope($config['default_scope']);
        }
        if (isset($config['state_param'])) {
            $authorizationServer->requireStateParam($config['state_param']);
        }
        if (isset($config['scope_delimiter'])) {
            $authorizationServer->setScopeDelimiter($config['scope_delimiter']);
        }
        if (isset($config['access_token_ttl'])) {
            $authorizationServer->setAccessTokenTTL($config['access_token_ttl']);
        }

        $this->configureGrantTypes($authorizationServer, $config['grant_types']);
    }


    /**
     * Configures the grant types for the authorization server instance.
     *
     * @param AuthorizationServer $authorizationServer
     * @param array               $config
     *
     * @throws InvalidArgument
     */
    protected function configureGrantTypes(AuthorizationServer $authorizationServer, array $config)
    {
        // TODO: Support configuring of the remaining grant types
        foreach ($config as $name => $params) {
            if (!isset($params['class'])) {
                throw new InvalidArgument("Parameter 'class' must be set for grant type.");
            }

            /** @var AbstractGrant $grantType */
            $grantType = new $params['class'];

            if (isset($params['access_token_ttl'])) {
                $grantType->setAccessTokenTTL($params['access_token_ttl']);
            }

            if ($grantType instanceof PasswordGrant) {
                /** @var PasswordGrant $grantType */
                if (isset($params['callback'])) {
                    $grantType->setVerifyCredentialsCallback($params['callback']);
                }
            }

            if ($grantType instanceof RefreshTokenGrant) {
                /** @var RefreshTokenGrant $grantType */
                if (isset($params['refresh_token_rotate'])) {
                    $grantType->setRefreshTokenRotation($params['refresh_token_rotate']);
                }
                if (isset($params['refresh_token_ttl'])) {
                    $grantType->setRefreshTokenTTL($params['refresh_token_ttl']);
                }
            }

            $authorizationServer->addGrantType($grantType);
        }
    }


    /**
     * Creates the resource server.
     *
     * @param Container $container
     *
     * @return ResourceServer
     */
    protected function createResourceServer(Container $container)
    {
        return $container->make(ResourceServer::class);
    }
}
