<?php
/*
  Virtualine Request Library

    https://virtualine.net
*/

namespace Virtualine\Http;

/**
 * Class Response
 * 
 * Represents an HTTP response with methods to access the response data.
 */
class Response {
    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $body;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var string|null
     */
    protected $curl_error;

    /**
     * @var string|null
     */
    protected $verbose;

    /**
     * Response constructor.
     *
     * @param int $statusCode HTTP status code
     * @param array|string $headers Response headers
     * @param string $body Response body
     * @param string|null $curl_error cURL error message if any
     * @param string|null $verbose Verbose output if enabled
     */
    public function __construct(int $statusCode, $headers, string $body, ?string $curl_error = null, ?string $verbose = null) {
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->headers = $this->prepareHeaders($headers);
        $this->curl_error = $curl_error;
        $this->verbose = $verbose;
    }

    /**
     * Get the HTTP status code.
     *
     * @return int
     */
    public function getStatusCode(): int {
        return $this->statusCode;
    }

    /**
     * Get the response body.
     *
     * @return string
     */
    public function getBody(): string {
        return $this->body;
    }

    /**
     * Check if the response has an error.
     *
     * @return bool
     */
    public function hasError(): bool {
        return !empty($this->curl_error);
    }

    /**
     * Get the response body as JSON.
     *
     * @param bool $assoc Whether to return associative arrays instead of objects
     * @return mixed
     */
    public function getJson(bool $assoc = true) {
        return json_decode($this->body, $assoc);
    }

    /**
     * Get the response body as XML.
     *
     * @return \SimpleXMLElement|null
     */
    public function getXML(): ?\SimpleXMLElement {
        return simplexml_load_string($this->body) ?: null;
    }

    /**
     * Get verbose output if enabled.
     *
     * @return string|null
     */
    public function getVerbose(): ?string {
        return $this->verbose;
    }

    /**
     * Get all response headers.
     *
     * @return array
     */
    public function getHeaders(): array {
        return $this->headers;
    }

    /**
     * Get a specific header value.
     *
     * @param string $name Header name
     * @return string|null
     */
    public function getHeader(string $name): ?string {
        return $this->headers[$name] ?? null;
    }

    /**
     * Check if a header exists.
     *
     * @param string $name Header name
     * @return bool
     */
    public function hasHeader(string $name): bool {
        return isset($this->headers[$name]);
    }

    /**
     * Prepare headers from raw header string.
     *
     * @param string|array $headers Raw headers
     * @return array
     */
    private function prepareHeaders($headers): array {
        if (is_array($headers)) {
            return $headers;
        }

        $result = [];
        $lines = explode("\n", $headers);
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $result[trim($parts[0])] = trim($parts[1]);
            }
        }
        return $result;
    }
}