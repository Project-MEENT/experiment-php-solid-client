<?php

/**
 * Example of how to discover OIDC endpoints from an issuer.
 *
 * Given an OIDC issuer URL, this example demonstrates fetching the OpenID Connect
 * configuration from the issuer's /.well-known/openid-configuration endpoint.
 *
 * This configuration contains the authorization_endpoint, token_endpoint,
 * userinfo_endpoint, and other metadata needed for authentication.
 */

namespace Potherca\Examples\Solid;

use Facile\OpenIDClient\Issuer\IssuerBuilder;
use Facile\OpenIDClient\Issuer\Metadata\Provider\MetadataProviderBuilder;
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
// Create HTTP Client
// -----------------------------------------------------------------------------
$httpClientConfig = [
    // Allow self-signed certificates for local development
    // 'verify' => false,
    // 'verify_host' => false,
    // 'verify_peer' => false,
];
$httpClient = new Client($httpClientConfig);

/* Basic Usage */
$metadataProviderBuilder = new MetadataProviderBuilder();
$metadataProviderBuilder->setHttpClient($httpClient);
$issuerBuilder = new IssuerBuilder();
$issuerBuilder = $issuerBuilder->setMetadataProviderBuilder($metadataProviderBuilder);
// =============================================================================


// =============================================================================
// Handle requests
// -----------------------------------------------------------------------------
// Get issuer from HTTP param
$issuerUrl = $request->getParsedBody()['issuer']
    ?? $request->getQueryParams()['issuer']
    ?? '';

if ($issuerUrl !== '' && filter_var($issuerUrl, FILTER_VALIDATE_URL)) {
    $issuerUrl = rtrim($issuerUrl, '/');
    $openidDiscoveryUrl = $issuerUrl . '/.well-known/openid-configuration';

    if (isset($issuerBuilder)) {
        // @uses \Facile\OpenIDClient\Issuer\IssuerBuilderInterface
        $issuer = $issuerBuilder->build($openidDiscoveryUrl); // @throws \Http\Discovery\Exception | \Facile\OpenIDClient\Exception\ExceptionInterface

        $issuerConfig = $issuer->getMetadata()->toArray();
    } else {
        // @uses \GuzzleHttp\ClientInterface | \Psr\Http\Client\ClientInterface
        $discoveryResponse = $httpClient->get($openidDiscoveryUrl); // @throws \GuzzleHttp\Exception\GuzzleException
        $contents = $discoveryResponse->getBody()->getContents(); // @throws \RuntimeException
        $issuerConfig = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }
}

$showOutput = ! empty($issuerUrl) || ! empty($issuerConfig);

$fileHandle = fopen(__FILE__, 'rb');
fseek($fileHandle, __COMPILER_HALT_OFFSET__);
$homepage = stream_get_contents($fileHandle);
$content = vsprintf($homepage, [
    '%1$s Show Form' => ! isset($exception) && $showOutput ? 'hidden' : '',
    '%2$s Show Output' => $showOutput ? '' : 'hidden',
    '%3$s Issuer URL' => $issuerUrl,
    '%4$s OIDC Config' => isset($issuerConfig) ? var_export($issuerConfig, true) : '',
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
    <h1><title>OIDC Server Discovery</title></h1>
    <p>
        This example demonstrates how to fetch a issuer's OpenID Connect configuration
        to get endpoints for authentication.
    </p>
</header>
<main>
    <section>
        <form enctype="application/x-www-form-urlencoded" method="GET" %1$s>
            <label>
                Issuer URL:
                <input
                    name="issuer"
                    placeholder="https://idp.example.com/"
                    required
                    type="url"
                    value="%3$s"
                />
            </label>
            <button>Discover</button>
        </form>
    </section>
    <section>
        <output %2$s>
            <ol>
                <li>
                    The OIDC Provider is <code>%3$s</code>
                </li>
                <li>OIDC Provider Configuration:
                    <pre><code>%4$s</code></pre>
                </li>
                <li>
                    Use this issuer <a href="/oidc-auth/?issuer=%3$s">in the OIDC Authorization Code Flow Example</a>
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
