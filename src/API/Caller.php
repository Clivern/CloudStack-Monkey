<?php

/*
 * This file is part of Monkey - Apache CloudStack SDK
 * (c) Clivern <hello@clivern.com>
 */

namespace Clivern\Monkey\API;

use Clivern\Monkey\API\Contract\RequestInterface;
use Clivern\Monkey\API\Contract\ResponseInterface;
use Clivern\Monkey\API\Request\RequestType;
use Clivern\Monkey\API\Request\ResponseType;
use GuzzleHttp\Client;

/**
 * CloudStack API Caller Class.
 *
 * @since 1.0.0
 */
class Caller
{
    protected $client;
    protected $ident;
    protected $response;
    protected $request;
    protected $status;
    protected $shared = [];
    protected $apiData = [
        'api_url' => '',
        'api_key' => '',
        'secret_key' => '',
        'sso_enabled' => false,
        'sso_key' => '',
        'verify_ssl' => true,
    ];

    /**
     * Class Constructor.
     *
     * @param RequestInterface  $request  The Request Object
     * @param ResponseInterface $response The Response Object
     * @param string            $ident    The Caller Ident
     * @param string            $apiData  The CloudStack Configs
     */
    public function __construct(
        RequestInterface $request = null,
        ResponseInterface $response = null,
        $ident = null,
        $apiData = []
    ) {
        $this->ident = $ident;
        $this->request = $request;
        $this->response = $response;
        $this->apiData = array_merge($this->apiData, $apiData);
        $this->client = new Client(['verify' => $this->apiData['verify_ssl']]);
        $this->request->addParameter('apiKey', (isset($apiData['api_key'])) ? $apiData['api_key'] : '');
        $this->status = CallerStatus::$PENDING;
    }

    /**
     * Execute The Caller.
     *
     * @return Caller
     */
    public function execute()
    {
        if ($this->status === CallerStatus::$SUCCEEDED) {
            return $this;
        }

        if ($this->status === CallerStatus::$ASYNC_JOB) {
            return $this->chechAsyncCall();
        }

        if (($this->status === CallerStatus::$PENDING) ||
            ($this->status === CallerStatus::$IN_PROGRESS) ||
            ($this->status === CallerStatus::$FAILED)) {
            if ($this->request->getType() === RequestType::$ASYNCHRONOUS) {
                return $this->executeAsyncCall();
            }

            return $this->executeSyncCall();
        }

        return $this;
    }

    /**
     * Get Caller Status.
     *
     * @return string the caller status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set Caller Status.
     *
     * @param string $status the caller status
     *
     * @return Caller
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get Request Object.
     *
     * @return RequestInterface
     */
    public function getRequestObject()
    {
        return $this->request;
    }

    /**
     * Get Response Object.
     *
     * @return ResponseInterface
     */
    public function getResponseObject()
    {
        return $this->response;
    }

    /**
     * Get Request Object.
     *
     * @return RequestInterface
     */
    public function request()
    {
        return $this->request;
    }

    /**
     * Get Response Object.
     *
     * @return ResponseInterface
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * Get Request Object.
     *
     * @return RequestInterface
     */
    public function requestObject()
    {
        return $this->request;
    }

    /**
     * Get Response Object.
     *
     * @return ResponseInterface
     */
    public function responseObject()
    {
        return $this->response;
    }

    /**
     * Add Shared Item.
     *
     * @param string $key   the shared item key
     * @param mixed  $value the shared item value
     *
     * @return Caller
     */
    public function addItem($key, $value)
    {
        $this->shared[$key] = $value;

        return $this;
    }

    /**
     * Get Shared Item.
     *
     * @param string $key the shared item key
     *
     * @return mixed the shared item value
     */
    public function getItem($key)
    {
        return (isset($this->shared[$key])) ? $this->shared[$key] : null;
    }

    /**
     * Check if Item Exists.
     *
     * @param string $key the shared item key
     *
     * @return bool whether item exists
     */
    public function itemExists($key)
    {
        return isset($this->shared[$key]);
    }

    /**
     * Get Caller Ident.
     *
     * @return string the caller ident
     */
    public function getIdent()
    {
        return $this->ident;
    }

    /**
     * Set Caller Ident.
     *
     * @param string $ident the caller ident
     *
     * @return Caller
     */
    public function setIdent($ident)
    {
        $this->ident = $ident;

        return $this;
    }

    /**
     * Dump The Caller Instance Data.
     *
     * @param string $type the type of data
     *
     * @return mixed
     */
    public function dump($type)
    {
        $data = [
            'shared' => $this->shared,
            'status' => $this->status,
            'ident' => $this->ident,
            'apiData' => $this->apiData,
            'response' => $this->response->dump(DumpType::$ARRAY),
            'request' => $this->request->dump(DumpType::$ARRAY),
        ];

        return ($type === DumpType::$JSON) ? json_encode($data) : $data;
    }

    /**
     * Reload The Caller Instance Data.
     *
     * @param mixed  $data The Caller Instance Data
     * @param string $type the type of data
     */
    public function reload($data, $type)
    {
        $data = ($type === DumpType::$JSON) ? json_decode($data, true) : $data;

        $this->shared = $data['shared'];
        $this->status = $data['status'];
        $this->ident = $data['ident'];
        $this->apiData = $data['apiData'];
        $this->response->reload($data['response'], DumpType::$ARRAY);
        $this->request->reload($data['request'], DumpType::$ARRAY);

        return $this;
    }

    /**
     * Execute Sync Call.
     *
     * @return Caller
     */
    protected function executeSyncCall()
    {
        $this->status = CallerStatus::$IN_PROGRESS;
        $status = true;

        try {
            $response = $this->client->request($this->request->getMethod(), $this->getUrl(), [
                'headers' => $this->request->getHeaders(),
                'body' => $this->request->getBody(DumpType::$JSON),
            ]);
        } catch (\Exception $e) {
            $status = false;
            $parsedError = (!empty($e->getResponse()) && !empty((string) $e->getResponse()->getBody(true))) ?
                json_decode((string) $e->getResponse()->getBody(true), true) : [];
            $errorCode = 'M100';
            $errorMessage = 'Error! Something Unexpected Happened.';

            foreach ($parsedError as $error_data) {
                $errorCode = (isset($error_data['errorcode'])) ? $error_data['errorcode'] : $errorCode;
                $errorMessage = (isset($error_data['errortext'])) ? $error_data['errortext'] : $errorMessage;
            }

            $this->response->setError([
                'parsed' => $parsedError,
                'plain' => $e->getMessage(),
                'code' => $errorCode,
                'message' => $errorMessage,
            ]);
        }

        if ($status) {
            $this->status = CallerStatus::$SUCCEEDED;
            $this->response->setResponse(json_decode((string) $response->getBody(), true));
        } else {
            $this->status = CallerStatus::$FAILED;
            $this->response->setResponse([]);
        }

        $callback = $this->response->getCallback();

        if (!empty($callback['method'])) {
            \call_user_func_array($callback['method'], [$this, $callback['arguments']]);
        }

        return $this;
    }

    /**
     * Execute Async Call.
     *
     * @return Caller
     */
    protected function executeAsyncCall()
    {
        $this->status = CallerStatus::$IN_PROGRESS;
        $status = true;

        try {
            $response = $this->client->request($this->request->getMethod(), $this->getUrl(), [
                'headers' => $this->request->getHeaders(),
                'body' => $this->request->getBody(DumpType::$JSON),
            ]);
        } catch (\Exception $e) {
            $status = false;
            $parsedError = (!empty($e->getResponse()) && !empty((string) $e->getResponse()->getBody(true))) ?
                json_decode((string) $e->getResponse()->getBody(true), true) : [];
            $errorCode = 'M100';
            $errorMessage = 'Error! Something Unexpected Happened.';

            foreach ($parsedError as $error_data) {
                $errorCode = (isset($error_data['errorcode'])) ? $error_data['errorcode'] : $errorCode;
                $errorMessage = (isset($error_data['errortext'])) ? $error_data['errortext'] : $errorMessage;
            }

            $this->response->setError([
                'parsed' => $parsedError,
                'plain' => $e->getMessage(),
                'code' => $errorCode,
                'message' => $errorMessage,
            ]);
        }

        if ($status) {
            $this->status = CallerStatus::$ASYNC_JOB;

            $asyncJob = json_decode((string) $response->getBody(), true);
            $asyncJobId = '';

            foreach ($asyncJob as $asyncJobData) {
                $asyncJobId = $asyncJobData['jobid'];
            }

            $this->response->setAsyncJob($asyncJob)->setAsyncJobId($asyncJobId);
        } else {
            $this->status = CallerStatus::$FAILED;
            $this->response->setAsyncJob([]);
        }

        $callback = $this->response->getCallback();

        if (!empty($callback['method'])) {
            \call_user_func_array($callback['method'], [$this, $callback['arguments']]);
        }

        return $this;
    }

    /**
     * Check Async Call.
     *
     * @return Caller
     */
    protected function chechAsyncCall()
    {
        $status = true;

        try {
            $response = $this->client->request(
                $this->request->getMethod(),
                $this->getJobUrl($this->response->getAsyncJobId()),
                [
                    'headers' => $this->request->getHeaders(),
                    'body' => $this->request->getBody(DumpType::$JSON),
                ]
            );
        } catch (\Exception $e) {
            $status = false;
            $parsedError = (!empty($e->getResponse()) && !empty((string) $e->getResponse()->getBody(true))) ?
                json_decode((string) $e->getResponse()->getBody(true), true) : [];
            $errorCode = 'M100';
            $errorMessage = 'Error! Something Unexpected Happened.';

            foreach ($parsedError as $error_data) {
                $errorCode = (isset($error_data['errorcode'])) ? $error_data['errorcode'] : $errorCode;
                $errorMessage = (isset($error_data['errortext'])) ? $error_data['errortext'] : $errorMessage;
            }

            $this->response->setError([
                'parsed' => $parsedError,
                'plain' => $e->getMessage(),
                'code' => $errorCode,
                'message' => $errorMessage,
            ]);
        }

        if ($status) {
            $this->status = CallerStatus::$SUCCEEDED;
            $this->response->setResponse(json_decode((string) $response->getBody(), true));
        } else {
            $this->status = CallerStatus::$FAILED;
            $this->response->setResponse([]);
        }

        $callback = $this->response->getCallback();

        if (!empty($callback['method'])) {
            \call_user_func_array($callback['method'], [$this, $callback['arguments']]);
        }

        return $this;
    }

    /**
     * Get Request URL.
     *
     * @return string the request final url
     */
    protected function getUrl()
    {
        $parameters = $this->request->getParameters();

        if ($this->apiData['sso_enabled'] && empty($this->apiData['sso_key'])) {
            throw new \InvalidArgumentException('Required options not defined: sso_key');
        }
        ksort($parameters);

        $query = http_build_query($parameters, false, '&', PHP_QUERY_RFC3986);
        $key = $this->apiData['sso_enabled'] ? $this->apiData['sso_key'] : $this->apiData['secret_key'];

        $signature = rawurlencode(base64_encode(hash_hmac(
            'SHA1',
            mb_strtolower($query),
            $key,
            true
        )));

        $query = trim($query.'&signature='.$signature, '?&');

        return $this->apiData['api_url'].'?'.$query;
    }

    /**
     * Get Job URL.
     *
     * @param mixed $jobId
     *
     * @return string the async job check url
     */
    protected function getJobUrl($jobId)
    {
        $parameters = [
            'response' => ResponseType::$JSON,
            'apiKey' => $this->apiData['api_key'],
            'command' => 'queryAsyncJobResult',
            'jobId' => $jobId,
        ];

        if ($this->apiData['sso_enabled'] && empty($this->apiData['sso_key'])) {
            throw new \InvalidArgumentException('Required options not defined: sso_key');
        }

        ksort($parameters);

        $query = http_build_query($parameters, false, '&', PHP_QUERY_RFC3986);
        $key = $this->apiData['sso_enabled'] ? $this->apiData['sso_key'] : $this->apiData['secret_key'];

        $signature = rawurlencode(base64_encode(hash_hmac(
            'SHA1',
            mb_strtolower($query),
            $key,
            true
        )));

        $query = trim($query.'&signature='.$signature, '?&');

        return $this->apiData['api_url'].'?'.$query;
    }
}
