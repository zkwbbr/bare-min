<?php

declare(strict_types=1);
\error_reporting(E_ALL);

// ------------------------------------------------

$phpSapiName = \php_sapi_name();

// ------------------------------------------------

if ($phpSapiName != 'cli')
    \ini_set('display_errors', '0'); // always set to '0' here then let our custom Whoops handler change it later
else
    \ini_set('display_errors', '1');

// ------------------------------------------------

\define('APP_ROOT_DIR', __DIR__ . '/../../');

// ------------------------------------------------

require APP_ROOT_DIR . 'vendor/autoload.php';

// ----------------------------------------------
// fundamental config
// ----------------------------------------------

\define('APP_DEVELOPMENT_MODE', \file_exists(APP_ROOT_DIR . '.development_mode'));
\define('APP_ERROR_LOG_DIR', APP_ROOT_DIR . 'logs/app/error/'); // used by /src/Framework/whoops.php
\define('APP_ERROR_LOG_TIMEZONE', 'Asia/Manila'); // set this to the timezone of whoever reads the error logs
\define('APP_SESSION_NAME', 'RFmQGk2c7ZK0'); // 12 chars alphanumeric (can't be all numbers)
\define('APP_SESSION_TIMEOUT', '3600'); // in seconds
\define('APP_SESSION_PATH', ''); // use custom session save path (without trailing slash) or blank to disable
\define('APP_CREDS_PATH_DEV', __DIR__ . '/../../config/creds/dev.creds.yaml.enc');
\define('APP_CREDS_PATH_PROD', __DIR__ . '/../../config/creds/prod.creds.yaml.enc');
\define('APP_CREDS_KEY', \App\Config\Key::getKey());

// ------------------------------------------------

$diContainer = \App\Factories\DiContainer::getInstance();

// ------------------------------------------------

if ($phpSapiName != 'cli')
    require APP_ROOT_DIR . 'src/Framework/whoops.php';

// ------------------------------------------------

\ini_set('url_rewriter.tags', '');
\ini_set('session.name', APP_SESSION_NAME);
\ini_set('session.use_trans_sid', '0');
\ini_set('session.cookie_httponly', '1');
\ini_set('session.use_cookies', '1');
\ini_set('session.use_only_cookies', '1');
\ini_set('session.use_strict_mode', '1');
\ini_set('session.cookie_lifetime', '0');
\ini_set('session.gc_maxlifetime', APP_SESSION_TIMEOUT);
\ini_set('session.cache_limiter', 'private_no_expire'); // this is important when using HTTP caching with sessions (e.g., Cache-Control, ETags). More info: https://github.com/micheh/psr7-cache/issues/4. But, you might not want to use this as this also caches logged-in pages, which makes logging off tricky. Make sure to explictly set max-age if you use this feature.

if (APP_SESSION_PATH) // @phpstan-ignore-line
    \ini_set('session.save_path', APP_SESSION_PATH);

if (!APP_DEVELOPMENT_MODE)
    \ini_set('session.cookie_secure', '1');

// ------------------------------------------------

\mb_internal_encoding('UTF-8');

// ------------------------------------------------

$appCfg = \App\Factories\AppCfg::getInstance();

\date_default_timezone_set($appCfg->getTimezoneOnCreate());

// ----------------------------------------------
// use cookies as session handler in prod only because Whoops errors are causing "headers already sent" issues.
// comment this block if you don't want to use cookie sessions.
// ----------------------------------------------
if (!APP_DEVELOPMENT_MODE) {

    $options['path'] = '/';
    $options = $options + ['secure' => true, 'httponly' => true];

    $handler = new \MetaRush\CookieSessions\Handler(\App\Config\Key::getKey(), $options);
    \session_set_save_handler($handler, true);
}

// ----------------------------------------------
// dispatch routes
// ----------------------------------------------

/* we use ApplicationStrategy instead of the built-in JsonStrategy because it has limited functionality */
$strategy = new \League\Route\Strategy\ApplicationStrategy;

// set our dependency container (from src/Framework/dependencies.php)
$strategy->setContainer($diContainer);

$router = new \League\Route\Router;
$router = $router->setStrategy($strategy);

require __DIR__ . '/middlewares.php';
require __DIR__ . '/routes.php';

$request = \Laminas\Diactoros\ServerRequestFactory::fromGlobals(
        $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
);

// build a valid HTTP response or HTTP error response if any
try {

    $response = $router->dispatch($request); // @phpstan-ignore-line

} catch (\League\Route\Http\Exception $ex) {

    $msg = (string) \file_get_contents(__DIR__ . '/../Views/Default/stop.php');
    $msg = \str_replace('<?=$stopMessage?>', '<b>Error ' . $ex->getStatusCode() . '</b><br />' . $ex->getMessage(), $msg);

    $response = new \Laminas\Diactoros\Response;
    $response->getBody()->write($msg);
    $response = $response->withAddedHeader('content-type', 'text/html')->withStatus($ex->getStatusCode());
}

// send the response to the browser
(new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter)->emit($response);
