<?php

namespace Potherca\Examples;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;

// =============================================================================
// Bootstrap (usually handled by a bootstrap or framework)
// -----------------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ob_start();
session_start();

require_once __DIR__ . '/vendor/autoload.php';

// -----------------------------------------------------------------------------
// Create PSR Request and Response objects
// -----------------------------------------------------------------------------
$request = $request ?? ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
$response = $response ?? new Response();

// -----------------------------------------------------------------------------
// Allow overriding the Accept header via a query parameter for ease of use
// -----------------------------------------------------------------------------
$queryParams = $request->getQueryParams();
if (isset($queryParams['accept'])) {
    $accept = $queryParams['accept'];
    $request = $request
        ->withHeader('Accept', $accept)
        ->withQueryParams($queryParams);
    unset($queryParams['accept'], $accept);
}

// -----------------------------------------------------------------------------
// Determine the output type
// -----------------------------------------------------------------------------
$acceptList = array_map(static function ($value) {
    return explode(';', $value)[0];
}, explode(',', $request->getHeader('Accept')[0]));

$accept = reset($acceptList);
$response = $response->withHeader('Content-Type', [$accept]);

switch ($accept) {
    case 'application/ld+json':
    case 'application/n-triples':
    case 'application/rdf+xml':
    case 'text/turtle':
        $outputType = 'linked-data';
    break;

    case 'application/json':
        $outputType = 'json';
    break;

    case 'text/plain':
        $outputType = 'text';
    break;

    case 'application/xhtml+xml':
    case 'text/html':
    case '*/*':
    default:
        $outputType = 'html';
    break;
}

// -----------------------------------------------------------------------------
// Use Pretty error pages if dev dependencies are installed
// -----------------------------------------------------------------------------
if (class_exists(\Whoops\Run::class) && ! isset($whoops)) {
    if ($outputType === 'json') {
        $handler = new \Whoops\Handler\JsonResponseHandler;
        $handler->addTraceToOutput(true);
    } elseif ($outputType === 'html') {
        $handler = new \Whoops\Handler\PrettyPageHandler;
    } else {
        $handler = new \Whoops\Handler\PlainTextHandler;
    }

    $whoops = new \Whoops\Run;
    $whoops->pushHandler($handler);
    $whoops->register();
}
// =============================================================================


// =============================================================================
// Handle requests
// -----------------------------------------------------------------------------
$path = trim($request->getUri()->getPath(), '/');

$context = [
    'content' => '',
    'description' => '',
    'details' => '',
    'status' => 200,
];

switch ($path) {
    case '':
    case basename(__FILE__):
        $context['content'] = <<<'HTML'
<ol>
    <li><a href="/webid">WebID</a></li>
    <li><a href="/oidc">OIDC</a></li>
</ol>
HTML;

        $context['description'] = 'Examples of how to call Solid Server APIs from PHP.';
        $context['details'] = '/docs/';
        $context['title'] = 'Solid Examples';
    break;

    case 'webid':
        $response = require __DIR__ . '/example.01.webid.php';
    break;

    case 'oidc':
        $response = require __DIR__ . '/example.02.oidc-discovery.php';
    break;

    default:
        $context['content'] = "The requested resource '$path' was not found on this server.";
        $context['description'] = 'Resource Not Found';
        $context['title'] = '404 Not Found';
    break;
}
// =============================================================================


// =============================================================================
// Handle the response (usually also handled by a framework)
// -----------------------------------------------------------------------------
$output = ob_get_clean();

// Assume output means trouble (as $response is expected to be used for output)
if ($output) {
    $context['content'] = 'The response caused unexpected output: ' . $output;
    $context['details'] = '/errors/';
    $context['status'] = 500;
    $context['title'] = 'Unexpected Output';
}

if (isset($context)) {
    $response = $response->withStatus($context['status']);

    switch ($outputType) {
        case 'json':
            try {
                $content = json_encode($context, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            } catch (\JsonException $e) {
                $template = <<<'JSON_TEMPLATE'
                {
                    "content": "{{ content }}",
                    "description": "{{ description }}",
                    "details": "{{ details }}",
                    "status":  {{ status }},
                    "title": "{{ title }}"
                }
    JSON_TEMPLATE;

                $context = [
                    'content' => 'An error occurred while encoding the response as JSON: ' . $e->getMessage(),
                    'description' => 'JSON Encoding Error',
                    'details' => '/errors/',
                    'status' => 500,
                    'title' => 'JSON Encoding Error',
                ];

                $response = $response
                    ->withHeader('Content-Type', ['application/problem+json'])
                    ->withStatus(500);
            }
        break;

        case 'text':
            $template = "Status: {{ status }}\nTitle: {{ title }}\nDescription: {{ description }}\nDetails: {{ details }}\nContent:\n{{ content }}";
        break;

        case 'html':
        default:
            $fileHandle = fopen(__FILE__, 'rb');
            fseek($fileHandle, __COMPILER_HALT_OFFSET__);
            $template = stream_get_contents($fileHandle);
        break;
    }

    if (! isset($content)) {
        if (isset($template)) {
            $content = str_replace(
                array_map(static function ($key) {
                    return '{{ ' . $key . ' }}';
                }, array_keys($context)),
                array_values($context),
                $template
            );
        } else {
            throw new \RuntimeException('Unable to render response, no template or content provided');
        }
    }
    $response->getBody()->rewind();
    $response->getBody()->write($content);
}

http_response_code($response->getStatusCode());

foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}

header_remove('X-Powered-By');

echo trim($response->getBody());
exit;
// =============================================================================

__halt_compiler();<!doctype html>
<html color-mode="user" lang="en">
<meta charset="utf-8">

<link
    href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 352 322'><g fill='none'><path fill='%23fff' d='M88 282.4 27.2 177a32 32 0 0 1 0-31.5L88 40.2c5.6-9.8 16-15.8 27.3-15.8h121.4c11.2 0 21.7 6 27.3 15.8l60.8 105.3a32 32 0 0 1 0 31.6L264 282.4a32 32 0 0 1-27.3 15.8H115.4c-11.3 0-21.8-6-27.4-15.8' /><path fill='%237c4dff' d='m93.2 275.2-57.2-99a30 30 0 0 1 0-29.7l57.2-99.1a30 30 0 0 1 25.7-14.9H233c10.6 0 20.4 5.7 25.7 14.9l57.2 99a30 30 0 0 1 0 29.7L259 275.2a30 30 0 0 1-25.8 14.9H119a30 30 0 0 1-25.7-14.9' /><path fill='%23f7f7f7' d='M118.5 142.2H236c1.5 0 2.7-1.2 2.7-2.6v-22A26.6 26.6 0 0 0 212 91h-70.6a37 37 0 0 0-37.1 37.1 14 14 0 0 0 14 14.1m11.5 97.4H200a38.5 38.5 0 0 0 38.5-38.4 13 13 0 0 0-12.9-12.9H107a2.5 2.5 0 0 0-2.5 2.6v23a25.6 25.6 0 0 0 25.6 25.7' /><path fill='%23f7f7f7' d='m109.6 139.3 87.7 87.7a15 15 0 0 0 21 0l15.2-15.2a15 15 0 0 0 0-21L145.8 103a15 15 0 0 0-21 0l-15.2 15.2a15 15 0 0 0 0 21' /><path fill='%23444' d='m198.7 228.5-51.5-40.2h11.4zm-54.3-126.8 40.5 40.5h13.8z' opacity='.3' /></g></svg>"
    rel='icon' title='Solid Logo' />

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/mvp.css@1.17.3/mvp.min.css" />

<style>
    title {
        display: inline;
    }
</style>
<header>
    <h1><title>{{ title }}</title></h1>
    <p>{{ description }}</p></header>
<main>{{ content }}</main>
<footer>{{ details }}</footer>
<script>
</script>
</html>
