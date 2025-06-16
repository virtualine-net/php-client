<?php
/*
  Virtualine Request Library

    https://virtualine.net
*/

namespace Virtualine\Http;

use Virtualine\Http\Helpers\Utils;

/**
 * Class Client
 * 
 * HTTP client for making requests with support for various HTTP features.
 */
class Client {
    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var array
     */
    protected $defaultHeaders;

    /**
     * @var int
     */
    protected $timeout;

    /**
     * @var bool
     */
    protected $allowRedirects;

    /**
     * @var string|null
     */
    protected $proxy;

    /**
     * @var bool
     */
    protected $verify;

    /**
     * @var string
     */
    protected $userAgent;

    /**
     * @var int
     */
    protected $httpVersion;

    /**
     * @var bool
     */
    protected $verbose;

    /**
     * Client constructor.
     *
     * @param array $config Configuration options:
     *                      - base_uri: Base URL for all requests
     *                      - headers: Default headers
     *                      - timeout: Request timeout in seconds
     *                      - allow_redirects: Whether to follow redirects
     *                      - proxy: Proxy configuration
     *                      - verify: Whether to verify SSL certificates
     *                      - user_agent: User agent string
     *                      - http_version: HTTP version to use
     *                      - verbose: Whether to enable verbose output
     */
    public function __construct(array $config = []) {
        $this->baseUrl = $config['base_uri'] ?? '';
        $this->defaultHeaders = $config['headers'] ?? [];
        $this->timeout = $config['timeout'] ?? 30;
        $this->allowRedirects = $config['allow_redirects'] ?? true;
        $this->proxy = $config['proxy'] ?? null;
        $this->verify = $config['verify'] ?? true;
        $this->userAgent = $config['user_agent'] ?? 'Virtualine HttpClient/1.0';
        $this->httpVersion = $config['http_version'] ?? CURL_HTTP_VERSION_2_0;
        $this->verbose = $config['verbose'] ?? false;
    }

    /**
     * Set the base URI for all requests.
     *
     * @param string $uri Base URI
     * @return self
     */
    public function setBaseUri(string $uri): self {
        $this->baseUrl = $uri;
        return $this;
    }

    /**
     * Set default headers for all requests.
     *
     * @param array $headers Headers
     * @return self
     */
    public function setHeaders(array $headers): self {
        $this->defaultHeaders = $headers;
        return $this;
    }

    /**
     * Add headers to the default headers.
     *
     * @param array $headers Headers to add
     * @return self
     */
    public function addHeaders(array $headers): self {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
        return $this;
    }

    /**
     * Add a single header to the default headers.
     *
     * @param string $key Header name
     * @param string $value Header value
     * @return self
     */
    public function addHeader(string $key, string $value): self {
        $this->defaultHeaders[$key] = $value;
        return $this;
    }

    /**
     * Set the request timeout.
     *
     * @param int $timeout Timeout in seconds
     * @return self
     */
    public function setTimeout(int $timeout): self {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Set whether to allow redirects.
     *
     * @param bool $allowRedirects Whether to allow redirects
     * @return self
     */
    public function setAllowRedirects(bool $allowRedirects): self {
        $this->allowRedirects = $allowRedirects;
        return $this;
    }

    /**
     * Set the proxy configuration.
     *
     * @param string|null $proxy Proxy URL
     * @return self
     */
    public function setProxy(?string $proxy): self {
        $this->proxy = $proxy;
        return $this;
    }

    /**
     * Set whether to verify SSL certificates.
     *
     * @param bool $verify Whether to verify SSL certificates
     * @return self
     */
    public function setVerify(bool $verify): self {
        $this->verify = $verify;
        return $this;
    }

    /**
     * Set the user agent string.
     *
     * @param string $userAgent User agent string
     * @return self
     */
    public function setUserAgent(string $userAgent): self {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * Set the HTTP version.
     *
     * @param int $httpVersion HTTP version
     * @return self
     */
    public function setHttpVersion(int $httpVersion): self {
        $this->httpVersion = $httpVersion;
        return $this;
    }

    /**
     * Set whether to enable verbose output.
     *
     * @param bool $verbose Whether to enable verbose output
     * @return self
     */
    public function setVerbose(bool $verbose): self {
        $this->verbose = $verbose;
        return $this;
    }

    /**
     * Send a GET request.
     *
     * @param string $url Request URL
     * @param array $options Request options
     * @return Response
     */
    public function get(string $url, array $options = []): Response {
        return $this->request('GET', $url, $options);
    }

    /**
     * Send a POST request.
     *
     * @param string $url Request URL
     * @param array $options Request options
     * @return Response
     */
    public function post(string $url, array $options = []): Response {
        return $this->request('POST', $url, $options);
    }

    /**
     * Send a PUT request.
     *
     * @param string $url Request URL
     * @param array $options Request options
     * @return Response
     */
    public function put(string $url, array $options = []): Response {
        return $this->request('PUT', $url, $options);
    }

    /**
     * Send a DELETE request.
     *
     * @param string $url Request URL
     * @param array $options Request options
     * @return Response
     */
    public function delete(string $url, array $options = []): Response {
        return $this->request('DELETE', $url, $options);
    }

    /**
     * Send a PATCH request.
     *
     * @param string $url Request URL
     * @param array $options Request options
     * @return Response
     */
    public function patch(string $url, array $options = []): Response {
        return $this->request('PATCH', $url, $options);
    }

    /**
     * Send a request.
     *
     * @param Request $request Request object
     * @return Response
     */
    public function send(Request $request): Response {
        $method = $request->getMethod();
        $uri_params = $request->getQuery();
        $uri = $this->buildUrl($request->getUri(), $uri_params);
        $headers = $this->prepareHeaders($request->getHeaders());
        $body = $request->getBody();
        return $this->doSend($method, $uri, $headers, $body);
    }

    /**
     * Build the full URL for a request.
     *
     * @param string $uri Request URI
     * @param array $uri_params Query parameters
     * @return string
     */
    protected function buildUrl(string $uri, array $uri_params = []): string {
        $base = str_replace(['http://', 'https://'], '', $this->baseUrl);
        $return_uri = $this->baseUrl . $uri;
        if ($base !== "" && Utils::startsWith($uri, $base)) {
            $return_uri = $uri;
        }
        if (count($uri_params) > 0) {
            $return_uri .= '?' . http_build_query($uri_params);
        }
        return $return_uri;
    }

    /**
     * Prepare headers for cURL.
     *
     * @param array $headers Headers
     * @return array
     */
    protected function prepareHeaders(array $headers): array {
        $merged = array_merge($this->defaultHeaders, $headers);
        return array_map(function ($key, $value) {
            return $key . ': ' . $value;
        }, array_keys($merged), $merged);
    }

    /**
     * Send a request with the given method, URI, headers, and body.
     *
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @param array $options Request options
     * @return Response
     * @throws \InvalidArgumentException If the HTTP method is invalid
     */
    public function request(string $method, string $uri, array $options = []): Response {
        if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'])) {
            throw new \InvalidArgumentException('Invalid HTTP method');
        }
        $request = new Request($method, $uri, $options);
        return $this->send($request);
    }

    /**
     * Execute the request using cURL.
     *
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @param array $headers Request headers
     * @param mixed $body Request body
     * @return Response
     */
    private function doSend(string $method, string $uri, array $headers, $body): Response {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->allowRedirects);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, $this->httpVersion);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify);
        if ($this->proxy) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        }
        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        if ($this->verbose) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $verboseLog = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verboseLog);
        }

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $curl_error = curl_error($ch);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        if ($this->verbose) {
            rewind($verboseLog);
            $verboseLogContents = stream_get_contents($verboseLog);
            fclose($verboseLog);
            return new Response($statusCode, $headers, $body, $verboseLogContents);
        }

        curl_close($ch);
        return new Response($statusCode, $headers, $body, $curl_error);
    }
}