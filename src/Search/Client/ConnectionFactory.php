<?php

namespace Devkit\Search\Client;

use Devkit\Search\Foundation\AwsSignedHandler;
use Elasticsearch\ClientBuilder;

/**
 * Thin helper around elasticsearch-php ClientBuilder. Wires a Client
 * from a plain config array; when AWS credentials are present and the
 * AWS SDK is installed, installs {@see AwsSignedHandler} so the
 * outgoing requests get SigV4-signed.
 *
 * Recognised config keys:
 *   hosts            string[]                       Required.
 *   retries          int                            Optional.
 *   ssl_verification bool|string                    Optional.
 *   basic_auth       array{0:string,1:string}       Optional [user, pass].
 *   aws              array                          Optional — triggers signed handler.
 *     ├── region          string                    Required when set.
 *     ├── key             string                    Optional, paired with secret.
 *     ├── secret          string                    Optional, paired with key.
 *     ├── token           string                    Optional STS session token.
 *     └── credentials     \Aws\Credentials\CredentialsInterface  Optional, overrides key/secret.
 */
class ConnectionFactory
{
    /**
     * @param  array  $config
     * @return \Elasticsearch\Client
     */
    public function make(array $config)
    {
        $builder = ClientBuilder::create();

        if (isset($config['hosts']) && is_array($config['hosts'])) {
            $builder->setHosts($config['hosts']);
        }

        if (isset($config['retries'])) {
            $builder->setRetries((int) $config['retries']);
        }

        if (isset($config['ssl_verification'])) {
            $builder->setSSLVerification($config['ssl_verification']);
        }

        if (isset($config['basic_auth']) && is_array($config['basic_auth']) && count($config['basic_auth']) === 2) {
            $builder->setBasicAuthentication(
                (string) $config['basic_auth'][0],
                (string) $config['basic_auth'][1]
            );
        }

        if (isset($config['aws']) && is_array($config['aws'])) {
            $handler = $this->buildAwsHandler($config['aws']);
            if ($handler !== null) {
                $builder->setHandler($handler);
            }
        }

        return $builder->build();
    }

    /**
     * @param  array  $aws
     * @return callable|null  Null when AWS SDK is missing (manager falls back to default handler).
     */
    protected function buildAwsHandler(array $aws)
    {
        if (!class_exists('\\Aws\\Credentials\\Credentials')) {
            return null;
        }

        $region = isset($aws['region']) ? (string) $aws['region'] : '';
        if ($region === '') {
            return null;
        }

        $credentials = $this->resolveAwsCredentials($aws);
        if ($credentials === null) {
            return null;
        }

        return new AwsSignedHandler($region, $credentials);
    }

    /**
     * @param  array  $aws
     * @return \Aws\Credentials\CredentialsInterface|null
     */
    protected function resolveAwsCredentials(array $aws)
    {
        if (isset($aws['credentials']) && is_object($aws['credentials'])) {
            return $aws['credentials'];
        }

        $key = isset($aws['key']) ? (string) $aws['key'] : '';
        $secret = isset($aws['secret']) ? (string) $aws['secret'] : '';
        if ($key === '' || $secret === '') {
            return null;
        }

        $token = isset($aws['token']) ? (string) $aws['token'] : null;
        $credentialsClass = '\\Aws\\Credentials\\Credentials';

        return new $credentialsClass($key, $secret, $token);
    }
}
