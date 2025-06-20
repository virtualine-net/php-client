<?php
/*
  Virtualine Request Library

    https://virtualine.net
*/

namespace Virtualine\Http;

/**
 * Class Request
 *
 * This class represents an HTTP request. It includes methods for setting and getting
 * the request method, URL, headers, and body.
 */
class Request {
    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var mixed
     */
    private $body;

    /**
     * @var array
     */
    private $query = [];

    /**
     * Request constructor.
     *
     * @param string $method The HTTP method for the request.
     * @param string $uri The URI for the request.
     * @param array $options An associative array of options for the request. The following options are supported:
     *                      - headers: array of headers
     *                      - query: array of query parameters
     *                      - json: data to be sent as JSON
     *                      - form_params: data to be sent as form parameters
     *                      - multipart: data to be sent as multipart form data
     */
    public function __construct(string $method, string $uri, array $options = []) {
        $this->method = $method;
        $this->uri = $uri;
        $this->processOptions($options);
    }

    /**
     * Set the URI of the request.
     *
     * @param string $uri The URI of the request.
     * @return self
     */
    public function setUri(string $uri): self {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Set a header for the request.
     *
     * @param string $name The name of the header.
     * @param string $value The value of the header.
     * @return self
     */
    public function setHeader(string $name, string $value): self {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Add multiple headers to the request.
     *
     * @param array $headers An associative array of headers to add.
     * @return self
     */
    public function addHeaders(array $headers): self {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Remove a header from the request.
     *
     * @param string $name The name of the header to remove.
     * @return self
     */
    public function removeHeader(string $name): self {
        unset($this->headers[$name]);
        return $this;
    }

    /**
     * Clear all headers from the request.
     * 
     * @return self
     */
    public function clearHeaders(): self {
        $this->headers = [];
        return $this;
    }

    /**
     * Get a header from the request.
     *
     * @param string $name The name of the header.
     * @return string The value of the header, or an empty string if the header is not set.
     */
    public function getHeader(string $name): string {
        return $this->headers[$name] ?? '';
    }

    /**
     * Get all headers from the request.
     *
     * @return array An array of all headers.
     */
    public function getHeaders(): array {
        return $this->headers;
    }

    /**
     * Set the body of the request.
     *
     * @param mixed $body The body of the request.
     * @return self
     */
    public function setBody($body): self {
        $this->body = $body;
        return $this;
    }

    /**
     * Set the body of the request as JSON.
     *
     * @param mixed $body The body of the request.
     * @return self
     * @throws \JsonException If JSON encoding fails
     */
    public function setJsonBody($body): self {
        $this->setHeader('Content-Type', 'application/json');
        $this->setBody(json_encode($body, JSON_THROW_ON_ERROR));
        return $this;
    }

    /**
     * Set the body of the request as form data.
     *
     * @param array $body The body of the request.
     * @return self
     */
    public function setFormBody(array $body): self {
        $this->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->setBody(http_build_query($body));
        return $this;
    }

    /**
     * Set the body of the request as multipart form data.
     *
     * @param array $body The body of the request.
     * @return self
     */
    public function setMultipartBody(array $body): self {
        $this->setHeader('Content-Type', 'multipart/form-data');
        $this->setBody($body);
        return $this;
    }

    /**
     * Get the body of the request.
     *
     * @return mixed The body of the request.
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Get the queries of the request.
     *
     * @return array The queries of the request.
     */
    public function getQuery(): array {
        return $this->query;
    }

    /**
     * Get the HTTP method of the request.
     *
     * @return string The HTTP method of the request.
     */
    public function getMethod(): string {
        return $this->method;
    }

    /**
     * Get the URI of the request.
     *
     * @return string The URL of the request.
     */
    public function getUri(): string {
        return $this->uri;
    }

    /**
     * Process request options.
     *
     * @param array $options Request options
     * @return void
     * @throws \JsonException If JSON encoding fails
     */
    protected function processOptions(array $options): void {
        if (isset($options['json'])) {
            $this->headers['Content-Type'] = 'application/json';
            $this->body = json_encode($options['json'], JSON_THROW_ON_ERROR);
        }
        elseif (isset($options['form_params'])) {
            $this->headers['Content-Type'] = 'application/x-www-form-urlencoded';
            $this->body = http_build_query($options['form_params']);
        }
        elseif (isset($options['multipart'])) {
            $this->headers['Content-Type'] = 'multipart/form-data';
            $this->body = $options['multipart'];
        }

        if (isset($options['headers'])) {
            foreach ($options['headers'] as $name => $value) {
                $this->setHeader($name, $value);
            }
        }

        if (isset($options['query'])) {
            $this->query = $options['query'];
        }
    }
}