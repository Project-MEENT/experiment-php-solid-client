<?php

/**
 * Example of how to
 */

namespace Potherca\Examples\Solid;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;

// =============================================================================
// Bootstrap
// -----------------------------------------------------------------------------
require_once __DIR__ . '/vendor/autoload.php';

// -----------------------------------------------------------------------------
// Create PSR Request and Response objects
// -----------------------------------------------------------------------------
$request = $request ?? ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
$response = $response ?? new Response();

// -----------------------------------------------------------------------------
// Use Pretty error pages if dev dependencies are installed
// -----------------------------------------------------------------------------
if (class_exists(\Whoops\Run::class) && ! isset($whoops)) {
    $handler = new \Whoops\Handler\PrettyPageHandler;
    $whoops = new \Whoops\Run;
    $whoops->pushHandler($handler);
    $whoops->register();
}
// =============================================================================


// =============================================================================
// Handle requests
// -----------------------------------------------------------------------------
$content = '<h1>Example</h1>';
$response->getBody()->write($content);
// =============================================================================


// =============================================================================
// Handle the response
// -----------------------------------------------------------------------------
if (isset($context)) {
    // Not called directly but included from index.php
    unset($context);

    return $response;
}

http_response_code($response->getStatusCode());

foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}

echo trim($response->getBody());
exit;
// =============================================================================

