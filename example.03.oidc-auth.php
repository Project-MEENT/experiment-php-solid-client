<?php

/**
 * Example of how to authenticate a user with OpenID Connect.
 *
 * Given an OIDC issuer URL, this example demonstrates how to fetch Tokens
 * (Code, Refresh Access) needed to access a private resource.
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
if (class_exists(\Whoops\Run::class) && ! isset($whoops)) {
    $handler = new \Whoops\Handler\PrettyPageHandler;
    $whoops = new \Whoops\Run;
    $whoops->pushHandler($handler);
    $whoops->register();
}
// =============================================================================


// =============================================================================
// Config
// -----------------------------------------------------------------------------
$storageLocation = __DIR__ . '/build/storage/';

// -----------------------------------------------------------------------------
$clientMetadataFile = 'client_id.json';

$clientServer = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost() . ':' . $request->getUri()->getPort();
// Redirect URI is the request URL, including port and path, excluding query param
$clientRedirectUri = $request->getUri()->withQuery('')->__toString();

$clientId = $clientServer . '/' . $clientMetadataFile;
$clientName = 'Example Client Name';
$clientRedirectUris = [$clientRedirectUri];
$clientSecret = 'my-client-secret';
// =============================================================================


// =============================================================================
// Create Client
// -----------------------------------------------------------------------------
$httpClientConfig = [
    // Allow self-signed certificates for local development
    // 'verify' => false,
    // 'verify_host' => false,
    // 'verify_peer' => false,
];
$httpClient = new Client($httpClientConfig);

// -----------------------------------------------------------------------------
// Setup cache and storage
// -----------------------------------------------------------------------------
if ($storageLocation) {
    $adapter = new \League\Flysystem\Local\LocalFilesystemAdapter($storageLocation);
} else {
    $adapter = new \League\Flysystem\InMemory\InMemoryFilesystemAdapter();
}

$filesystem = new \League\Flysystem\Filesystem($adapter);

if ($filesystem) {
    $store = new \MatthiasMullie\Scrapbook\Adapters\Flysystem($filesystem);
} else {
    $store = new \MatthiasMullie\Scrapbook\Adapters\MemoryStore();
}

// simple-cache implementation
$cache = new \MatthiasMullie\Scrapbook\Psr16\SimpleCache($store);

/* Basic Usage */
$issuerBuilder = new IssuerBuilder();
$metadataProviderBuilder = new MetadataProviderBuilder();
$metadataProviderBuilder->setHttpClient($httpClient);

$clientMetadataFileExists = $filesystem->fileExists($clientMetadataFile);
if (! $clientMetadataFileExists) {
    // Client metadata file not found, creating...
    $data = [
        'client_id' => $clientId,
        'client_name' => $clientName,
        'client_secret' => $clientSecret,
        'redirect_uris' => $clientRedirectUris,
        'token_endpoint_auth_method' => 'client_secret_basic', // the auth method tor the token endpoint
    ];

    $filesystem->write($clientMetadataFile, json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
}

// Reading client metadata from file
$clientData = json_decode($filesystem->read($clientMetadataFile), true, 512, JSON_THROW_ON_ERROR);
// =============================================================================


// =============================================================================
// Handle requests
// -----------------------------------------------------------------------------
// Get issuer from HTTP param
$issuerUrl = $request->getParsedBody()['issuer']
    ?? $request->getQueryParams()['issuer']
    ?? '';

$isRedirect = isset($request->getQueryParams()['code'])
    || isset($request->getQueryParams()['error']);

if ($issuerUrl !== '' && filter_var($issuerUrl, FILTER_VALIDATE_URL)) {
    $issuerUrl = rtrim($issuerUrl, '/');
    $openidDiscoveryUrl = $issuerUrl . '/.well-known/openid-configuration';

    $issuer = $issuerBuilder
        ->setMetadataProviderBuilder($metadataProviderBuilder)
        ->build($openidDiscoveryUrl); // @throws \Http\Discovery\Exception | \Facile\OpenIDClient\Exception\ExceptionInterface

    $issuerConfig = $issuer->getMetadata()->toArray();
}

$outputState = empty($issuerUrl) || empty($issuerConfig) ? '' : 'hidden';

if ($isRedirect) {
}

$fileHandle = fopen(__FILE__, 'rb');
fseek($fileHandle, __COMPILER_HALT_OFFSET__);
$homepage = stream_get_contents($fileHandle);
$content = vsprintf($homepage, [
    '%1$s Show Form' => ! isset($exception) && $outputState ? 'hidden' : '',
    '%2$s Show Output' => $outputState ? '' : 'hidden',
    '%3$s Issuer URL' => $issuerUrl,
    '%4$s OIDC Config' => isset($issuerConfig) ? var_export($issuerConfig, true) : '',
    '%5$s Client Metadata File' => $clientMetadataFile,
    '%6$s Client Metadata File exists' => $clientMetadataFileExists ? '▶️' : '⏺️',
    '%7$s Client Metadata' => var_export($clientData, true),

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
    <h1><title>OIDC Server Tokens Example</title></h1>
    <p>
        This example demonstrates how to authenticate a user with OpenID Connect to get tokens.
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
            <button>Connect</button>
        </form>
    </section>
    <section>
        <output %2$s>
            <ol>
                <li>
                    The OIDC Provider is <code>%3$s</code>
                </li>
                <li>
                    <details>
                        <summary>OIDC Provider Configuration</summary>
                        <pre><code>%4$s</code></pre>
                    </details>
                </li>
                <li>
                    Reading client metadata from file: <code>%5$s</code> %6$s
                    <details>
                        <summary>Client metadata</summary>
                        <pre><code>%7$s</code></pre>
                    </details>
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
