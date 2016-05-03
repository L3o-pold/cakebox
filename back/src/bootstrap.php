<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Silex\Application;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Silex\Provider\MonologServiceProvider;
use JDesrosiers\Silex\Provider\CorsServiceProvider;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;

define('APPLICATION_ENV', getenv('APPLICATION_ENV') ?: 'dev');

$app = new Application();
if (APPLICATION_ENV != 'production') {
    $app['debug'] = true;
}

$app['extension.video']    = ['mp4', 'mov', 'mpg', 'flv', 'avi', 'mkv', 'wmv'];
$app['extension.audio']    = ['mp3', 'flac', 'ogg', 'aac', 'wma'];
$app['extension.image']    = ['png', 'gif', 'jpg', 'jpeg'];
$app['extension.archive']  = ['zip', 'rar', 'gz', 'bz2', '7z'];
$app['extension.subtitle'] = ['srt'];

// Include specific user conf
$user = @$_SERVER['PHP_AUTH_USER'];
if (isset($user) && file_exists(__DIR__ . "/../config/{$user}.php"))
    require_once __DIR__ . "/../config/{$user}.php";
else
    require_once __DIR__ . '/../config/default.php';

// Remove ending slash if needed
if (substr($app['cakebox.root'], -1) == '/')
    $app['cakebox.root'] = rtrim($app['cakebox.root'], '/');

/**
 * Register service providers
 */
$app->register(new CorsServiceProvider, [
    'cors.allowOrigin'  => '*',
    'cors.allowMethods' => 'GET, POST, PUT, DELETE, OPTIONS',
]);

$app->after($app['cors']);

$app->register(new MonologServiceProvider(), [
    'monolog.logfile' => __DIR__ . '/../logs/application.log',
    'monolog.level'   => Logger::WARNING,
    'monolog.name'    => 'api'
]);

$app['security.jwt'] = [
    'secret_key' => $app['jwt.secretKey'],
    'life_time'  => 86400,
    'options'    => [
        'username_claim' => 'sub', // default name, option specifying claim containing username
        'header_name' => 'Authorization', // default null, option for usage normal oauth2 header
        'token_prefix' => 'Bearer',
    ]
];

/**
 * @todo load user from a file
 * @return InMemoryUserProvider
 */
$app['users'] = function () use ($app) {
    $users = [
        'admin' => array(
            'roles' => array('ROLE_ADMIN'),
            /**
             * raw password is foo sha512 encoded
            */
            'password' => 'f7fbba6e0636f890e56fbbf3283e524c6fa3204ae298382d624741d0dc6638326e282c41be5e4254d8820772c5518a2c5a8c0c7f7eda19594a7eb539453e1ed7',
            'enabled' => true
        ),
    ];

    return new InMemoryUserProvider($users);
};

$app['security.firewalls'] = array(
    'signin' => [
        'pattern' => 'login|register|oauth|signin|app',
        'anonymous' => true,
    ],
    'secured' => array(
        'pattern' => '^.*$',
        'logout' => array('logout_path' => '/logout'),
        'users' => $app['users'],
        'jwt' => array(
            'use_forward' => true,
            'require_previous_session' => false,
            'stateless' => true,
        )
    ),
);

$app['security.encoder.digest'] = $app->share(function ($app) {
    return new MessageDigestPasswordEncoder('sha512', false, 1);
});

$app->register(new Silex\Provider\SecurityServiceProvider());
$app->register(new Silex\Provider\SecurityJWTServiceProvider());

/**
 * Register our custom services
 */
$app['service.main'] = $app->share(function ($app) {
    return new Cakebox\Service\MainService($app);
});

/**
 * Register error handler
 */
$app->error(function (\Exception $e, $code) use ($app) {
    return new JsonResponse(['status_code' => $code, 'message' => $e->getMessage()]);
});

require_once __DIR__ . '/routing.php';

return $app;
