# Lumen OAuth2

OAuth2 module for the Lumen PHP framework.

## Requirements

- PHP 5.4 or newer
- [Composer](http://getcomposer.org)

## Usage

### Installation

Run the following command to install the package through Composer:

```sh
composer require nordsoftware/lumen-oauth2
```

### Configure

Copy the configuration template in `config/oauth2.php` to your application's `config` directory and modifying according to your needs. For more information see the [Configuration Files](http://lumen.laravel.com/docs/configuration#configuration-files) section in the Lumen documentation.

### Bootstrapping

Add the following lines to ```bootstrap/app.php```:

```php
$app->configure('oauth2');
```

```php
$app->register('Nord\Lumen\OAuth2\Eloquent\EloquentServiceProvider');
$app->register('Nord\Lumen\OAuth2\OAuth2ServiceProvider');
```

```php
$app->routerMiddleware([
	.....
	'Nord\Lumen\OAuth2\Middleware\OAuth2Middleware',
]);
```

## Contributing

Please note the following guidelines before submitting pull requests:

- Use the [PSR-2 coding style](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)
- Create pull requests for the *develop* branch

## License

See [LICENSE](LICENSE).
