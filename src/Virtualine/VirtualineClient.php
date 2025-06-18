<?php

namespace Virtualine;

use Virtualine\Http\Client;
use Virtualine\Http\Request;
use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * VirtualineClient - Main client class for interacting with Virtualine API
 *
 * This class provides methods to interact with the Virtualine API for managing
 * virtual servers and services.
 */
class VirtualineClient
{
    /**
     * API base URL
     */
    private const API_BASE_URL = 'https://client.virtualine.net/modules/addons/ProductsReseller/api/index.php/';

    /**
     * @var string Authentication token
     */
    private string $token;

    /**
     * @var string Username for authentication
     */
    private string $username;

    /**
     * @var string API key for authentication
     */
    private string $apiKey;

    /**
     * @var Client HTTP client instance
     */
    private Client $client;

    /**
     * Constructor
     *
     * @param string $apiKey API key for authentication
     * @param string $username Username for authentication
     * @throws InvalidArgumentException If apiKey or username is empty
     */
    public function __construct(string $apiKey, string $username)
    {
        if (empty($apiKey) || empty($username)) {
            throw new InvalidArgumentException('API key and username are required');
        }

        $this->apiKey = $apiKey;
        $this->username = $username;

        // Generate token using HMAC hash
        $this->token = base64_encode(hash_hmac(
            "sha256",
            $this->apiKey,
            $this->username . ":" . gmdate("y-m-d H")
        ));

        $this->client = new Client([
            'base_uri' => self::API_BASE_URL,
            'headers' => [
                "token" => $this->token,
                "username" => $this->username,
            ]
        ]);
    }

    /**
     * Test the connection to the Virtualine API
     *
     * @return bool True if connection is successful, false otherwise
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->client->get("testConnection");
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data['result'] === "success";
        } catch (Exception $e) {
            throw new RuntimeException('Failed to test connection: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get current credit balance
     *
     * @return float Current credit balance
     * @throws RuntimeException If API request fails
     */
    public function getCredit(): float
    {
        try {
            $response = $this->client->get("billing/credit");
            $body = trim($response->getBody(), "\" \t\n\r\0\x0B");

            $floatValue = (float)$body;
            return $floatValue ? $floatValue : 0.0;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get credit: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get available products
     *
     * @return array List of available products
     * @throws RuntimeException If API request fails
     */
    public function getProducts(): array
    {
        try {
            $response = $this->client->get("products");
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return [];
            }

            return isset($data['data']) ? $data['data'] : [];
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get products: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get service details
     *
     * @param string $serviceId Service ID
     * @return array Service details
     * @throws RuntimeException If API request fails
     */
    public function getServiceDetails(string $serviceId): array
    {
        try {
            $response = $this->client->get("services/{$serviceId}");
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return [];
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get service details: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get service information
     *
     * @param string $serviceId Service ID
     * @return array Service information
     * @throws RuntimeException If API request fails
     */
    public function getInfo(string $serviceId): array
    {
        try {
            $response = $this->client->get("services/{$serviceId}/getInfo");
            $data = json_decode($response->getBody(), true);

            if (isset($data['result']) && $data['result'] === "success") {
                return ['success' => true];
            }

            if (isset($data['warning'])) {
                return [
                    'success' => false,
                    'warning' => $data['warning'],
                    'progress' => $data['progress'] ?? 0
                ];
            }

            return ['success' => false, 'warning' => "An error occurred while processing the request."];
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get service info: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a new service
     *
     * @param string $productId Product ID
     * @param array $params Service parameters
     * @return array|false Service creation response
     * @throws RuntimeException If API request fails
     */
    public function createService(string $productId, array $params)
    {
        try {
            $request = new Request("POST", "order/products/{$productId}");
            $request->setBody($params);
            $response = $this->client->send($request);
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to create service: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Start a service
     *
     * @param string $serviceId Service ID
     * @return array|false Service start response
     * @throws RuntimeException If API request fails
     */
    public function start(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/start");
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to start service: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Stop a service
     *
     * @param string $serviceId Service ID
     * @return array|false Service stop response
     * @throws RuntimeException If API request fails
     */
    public function stop(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/stop");
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to stop service: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Reboot a service
     *
     * @param string $serviceId Service ID
     * @return array|false Service reboot response
     * @throws RuntimeException If API request fails
     */
    public function reboot(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/reboot");
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to reboot service: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Change service password
     *
     * @param string $serviceId Service ID
     * @param string $password New password
     * @return array|false Password change response
     * @throws RuntimeException If API request fails
     */
    public function changePassword(string $serviceId, string $password)
    {
        try {
            $request = new Request("POST", "services/{$serviceId}/changepassword");
            $request->setBody(['password' => $password]);
            $response = $this->client->send($request);
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to change password: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Terminate a service
     *
     * @param string $serviceId Service ID
     * @return array|false Service termination response
     * @throws RuntimeException If API request fails
     */
    public function terminate(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/terminate");
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to terminate service: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Suspend a service
     *
     * @param string $serviceId Service ID
     * @return array|false Service suspension response
     * @throws RuntimeException If API request fails
     */
    public function suspend(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/suspend");
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to suspend service: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Unsuspend a service
     *
     * @param string $serviceId Service ID
     * @return array|false Service unsuspension response
     * @throws RuntimeException If API request fails
     */
    public function unsuspend(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/unsuspend");
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to unsuspend service: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Renew a service
     *
     * @param string $serviceId Service ID
     * @return array|false Service renewal response
     * @throws RuntimeException If API request fails
     */
    public function renew(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/renew");
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to renew service: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get reinstall templates
     *
     * @param string $serviceId Service ID
     * @return array List of available templates
     * @throws RuntimeException If API request fails
     */
    public function reinstallTemplates(string $serviceId): array
    {
        try {
            $response = $this->client->get("services/{$serviceId}/reinstall");
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return [];
            }

            return isset($data['osTemplates']) ? $data['osTemplates'] : [];
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get reinstall templates: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Reinstall a service
     *
     * @param string $serviceId Service ID
     * @param string $templateId Template ID
     * @param string $password New password
     * @return array|false Reinstall response
     * @throws RuntimeException If API request fails
     */
    public function reinstall(string $serviceId, string $templateId, string $password)
    {
        try {
            $request = new Request("POST", "services/{$serviceId}/reinstall");
            $request->setBody([
                'actionid' => $templateId,
                'password' => $password,
            ]);
            $response = $this->client->send($request);
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to reinstall service: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get WMKS console URL
     *
     * @param string $serviceId Service ID
     * @return string|false WMKS console URL
     * @throws RuntimeException If API request fails
     */
    public function getWMKSUrl(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/actionWmksConsole");
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return isset($data['url']) ? $data['url'] : false;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get WMKS URL: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * SSO Login
     * @param string $serviceId Service ID
     * @return string|false SSO login URL
     * @throws RuntimeException If API request fails
     */
    public function ssoLogin(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/ssologin");
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return isset($data['data']['url']) ? $data['data']['url'] : false;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get SSO login URL: ' . $e->getMessage(), 0, $e);
        }
    }


}