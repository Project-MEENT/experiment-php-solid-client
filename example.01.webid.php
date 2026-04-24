<?php

/**
 * Example of how to discover the OIDC issuer(s) for a given WebID URL.
 *
 * This example was built so it can be run directly or included from another file.
 */

namespace Potherca\Examples\Solid;

use EasyRdf\Graph;
use EasyRdf\RdfNamespace;
use EasyRdf\Resource;
use GuzzleHttp\Client;
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
if (class_exists('\\Whoops\\Run') && ! isset($whoops)) {
    $handler = new \Whoops\Handler\PrettyPageHandler;
    $whoops = new \Whoops\Run;
    $whoops->pushHandler($handler);
    $whoops->register();
}
// =============================================================================


// =============================================================================
// Handle requests
// -----------------------------------------------------------------------------
$webIdUrl = $request->getParsedBody()['webid']
    ?? $request->getQueryParams()['webid']
    ?? '';

if ($webIdUrl) {
    if (filter_var($webIdUrl, FILTER_VALIDATE_URL)) {
        if (! RdfNamespace::get('solid')) {
            RdfNamespace::set('solid', 'http://www.w3.org/ns/solid/terms#');
        }

        $client = new Client();
        $graph = new Graph();

        $webIdResponse = $client->get($webIdUrl);
        $content = $webIdResponse->getBody()->getContents();
        $format = explode(';', $webIdResponse->getHeaderLine('Content-Type'))[0] ?? null;
        $graph->parse($content, $format, $webIdUrl);

        $profile = $graph->resource($webIdUrl);

        $issuers = [];

        if ($profile->hasProperty('solid:oidcIssuer')) {
            $resources = $profile->allResources('solid:oidcIssuer');
            $uris = array_map(static function (Resource $issuer) {return $issuer->getUri();}, $resources);
            $issuers = array_unique($uris);
        }
    }
}

$showOutput = ! empty($issuerUrl) || ! empty($issuerConfig) || $isRedirect;

$fileHandle = fopen(__FILE__, 'rb');
fseek($fileHandle, __COMPILER_HALT_OFFSET__);
$homepage = stream_get_contents($fileHandle);
$content = vsprintf($homepage, [
    '%1$s Issuer' => empty($issuers) ? '' : $issuers[0],
    '%2$s Issuers' => empty($issuers) ? '' : var_export($issuers, true),
    '%3$s Show Form' => ! isset($exception) && $showOutput ? 'hidden' : '',
    '%4$s Show Output' => $showOutput ? '' : 'hidden',
    '%5$s Web ID URL' => $webIdUrl,
    '%6$s WebID Profile' => empty($profile) ? '' : $profile->dump(),
]);
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

header_remove('X-Powered-By');
echo trim($response->getBody());
exit;
// =============================================================================

__halt_compiler();<!doctype html>
<html color-mode="user" lang="en">
<meta charset="utf-8">

<link
    href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' stroke='%%23000' stroke-width='0' viewBox='-5 -5 110 110'><circle cx='50' cy='50' r='51' fill='%%237C4DFF'/><circle cx='50' cy='50' r='34' fill='%%23F2E205'/><path fill='%%23FFF' stroke-width='2' d='M-1 50h16a38 35.5 0 0027.5 34.45V68.7A20 20 0 0150 30.2a20 20 0 017.5 38.5v15.75A38 35.5 0 0085 50h15A1 1 0 010 50z'/></svg>"
    rel='icon' title='PDS Interop Logo' />

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/mvp.css@1.17.3/mvp.min.css" />

<style>
    form { background-color: var(--color-bg-secondary); }
    title { display: inline; }
    .card {
        background-color: var(--color-accent);
        border-radius: 10px;
        padding: 10px;
    }
</style>
<header>
    <h1><title>Fetch OIDC Issuer from WebID Profile</title></h1>
    <p>
        This example demonstrates how to fetch a WebID profile and extract the
        OIDC issuer(s) so it can be used for authentication.
    </p>
</header>
<main>
    <section>
        <form enctype="application/x-www-form-urlencoded" method="GET" %3$s>
            <label>
                WebID URI:
                <input
                    name="webid"
                    placeholder="https://example.com/profile/card#me"
                    required
                    type="url"
                    value="%5$s"
                />
            </label>
            <button>Fetch issuer</button>
        </form>
    </section>
    <section>
        <output %4$s>
            <ol>
                <li>The WebID URI is <code>%5$s</code></li>
                <li>The WebID profile is: %6$s</li>
                <li>The OIDC issuer(s) are:
                    <pre><code>%2$s</code></pre>
                </li>
                <li>
                    The first OIDC issuer is: <code>%1$s</code>
                    <a href="/oidc-discovery/?issuer=%1$s">Use this issuer in the OIDC example</a>
                </li>
            </ol>
        </output>
    </section>
</main>
<footer></footer>
<script>
    document.querySelectorAll('output div[style]').forEach(element => {
        element.querySelectorAll('[style]').forEach(el => el.removeAttribute('style'))
        element.removeAttribute('style')
        element.classList.add('card')
    })
</script>
</html>
