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

        $this->apiKey   = $apiKey;
        $this->username = $username;
        $this->token    = base64_encode(hash_hmac(
            "sha256",
            $this->apiKey,
            $this->username . ":" . gmdate("y-m-d H")
        ));

        $this->client = new Client([
            'base_uri' => self::API_BASE_URL,
            'headers'  => [
                "token"    => $this->token,
                "username" => $this->username,
            ],
        ]);
    }

    /**
     * Test the connection to the Virtualine API
     *
     * @return bool True if connection is successful, false otherwise
     * @throws RuntimeException If API request fails
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->client->get("testConnection");
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data['result'] === "success";
        } catch (Exception $e) {
            throw new RuntimeException('Failed to test connection: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the installed API version
     *
     * @return string API version string
     * @throws RuntimeException If API request fails
     */
    public function getVersion(): string
    {
        try {
            $response = $this->client->get("version");
            $data     = json_decode($response->getBody(), true);

            return $data['data'] ?? '';
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get version: ' . $e->getMessage(), 0, $e);
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
            $data     = json_decode($response->getBody(), true);

            return isset($data['data']) ? (float)$data['data'] : 0.0;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get credit: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get available products
     *
     * @param bool $withPricing Include pricing data in the response
     * @return array List of available products
     * @throws RuntimeException If API request fails
     */
    public function getProducts(bool $withPricing = false): array
    {
        try {
            $options  = $withPricing ? ['query' => ['withpricing' => 1]] : [];
            $response = $this->client->get("products", $options);
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return [];
            }

            return $data['data'] ?? [];
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get products: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get custom field values for all services under a given product
     *
     * @param string $productId Product ID
     * @return array Map of serviceId => { fieldName => value }
     * @throws RuntimeException If API request fails
     */
    public function getCustomFieldsValues(string $productId): array
    {
        try {
            $response = $this->client->get("customFieldsValues/{$productId}");
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return [];
            }

            return $data['data'] ?? [];
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get custom field values: ' . $e->getMessage(), 0, $e);
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
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return [];
            }

            return $data['data'] ?? [];
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get service details: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get service information (polls reinstall/rebuild progress)
     *
     * @param string $serviceId Service ID
     * @return array Service information with success, warning and progress keys
     * @throws RuntimeException If API request fails
     */
    public function getInfo(string $serviceId): array
    {
        try {
            $response = $this->client->get("services/{$serviceId}/getInfo");
            $data     = json_decode($response->getBody(), true);

            if (isset($data['result']) && $data['result'] === "success") {
                return ['success' => true];
            }

            if (isset($data['warning'])) {
                return [
                    'success'  => false,
                    'warning'  => $data['warning'],
                    'progress' => $data['progress'] ?? 0,
                ];
            }

            return ['success' => false, 'warning' => "An error occurred while processing the request."];
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get service info: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get resource usage graphs for a service
     *
     * @param string $serviceId Service ID
     * @param string $timeframe Optional time range for the graphs
     * @return array Graph data (shape depends on server type)
     * @throws RuntimeException If API request fails
     */
    public function getGraphs(string $serviceId, string $timeframe = ''): array
    {
        try {
            $options  = $timeframe !== '' ? ['query' => ['timeframe' => $timeframe]] : [];
            $response = $this->client->get("services/{$serviceId}/graphs", $options);
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return [];
            }

            return $data ?? [];
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get graphs: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a new service
     *
     * @param string $productId Product ID
     * @param array $params Service parameters (cycle, hostname, username, password, nsprefix, fields, configurations)
     * @return array|false Service creation response or false on error
     * @throws RuntimeException If API request fails
     */
    public function createService(string $productId, array $params)
    {
        try {
            $request = new Request("POST", "order/products/{$productId}");
            $request->setBody($params);
            $response = $this->client->send($request);
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to create service: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Upgrade or downgrade a service to another product or billing cycle
     *
     * @param string $serviceId Service ID
     * @param string $newProductId New product ID
     * @param string $newCycle New billing cycle
     * @param array $configurations Optional configurable options
     * @return array|false Upgrade response or false on error
     * @throws RuntimeException If API request fails
     */
    public function upgradeService(string $serviceId, string $newProductId, string $newCycle, array $configurations = [])
    {
        try {
            $body = ['newProductId' => $newProductId, 'newCycle' => $newCycle];
            if (!empty($configurations)) {
                $body['configurations'] = $configurations;
            }
            $request = new Request("POST", "services/{$serviceId}/upgrade");
            $request->setBody($body);
            $response = $this->client->send($request);
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to upgrade service: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Start a service
     *
     * @param string $serviceId Service ID
     * @return array|false Service start response or false on error
     * @throws RuntimeException If API request fails
     */
    public function start(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/start");
            $data     = json_decode($response->getBody(), true);

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
     * @return array|false Service stop response or false on error
     * @throws RuntimeException If API request fails
     */
    public function stop(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/stop");
            $data     = json_decode($response->getBody(), true);

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
     * @return array|false Service reboot response or false on error
     * @throws RuntimeException If API request fails
     */
    public function reboot(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/reboot");
            $data     = json_decode($response->getBody(), true);

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
     * @return array|false Password change response or false on error
     * @throws RuntimeException If API request fails
     */
    public function changePassword(string $serviceId, string $password)
    {
        try {
            $request = new Request("POST", "services/{$serviceId}/changepassword");
            $request->setBody(['password' => $password]);
            $response = $this->client->send($request);
            $data     = json_decode($response->getBody(), true);

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
     * @return array|false Service termination response or false on error
     * @throws RuntimeException If API request fails
     */
    public function terminate(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/terminate");
            $data     = json_decode($response->getBody(), true);

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
     * @return array|false Service suspension response or false on error
     * @throws RuntimeException If API request fails
     */
    public function suspend(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/suspend");
            $data     = json_decode($response->getBody(), true);

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
     * @return array|false Service unsuspension response or false on error
     * @throws RuntimeException If API request fails
     */
    public function unsuspend(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/unsuspend");
            $data     = json_decode($response->getBody(), true);

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
     * @return array|false Service renewal response or false on error
     * @throws RuntimeException If API request fails
     */
    public function renew(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/renew");
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to renew service: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get reinstall templates for a service
     *
     * @param string $serviceId Service ID
     * @return array List of available OS templates
     * @throws RuntimeException If API request fails
     */
    public function reinstallTemplates(string $serviceId): array
    {
        try {
            $response = $this->client->get("services/{$serviceId}/reinstall");
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return [];
            }

            return $data['data']['osTemplates'] ?? [];
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get reinstall templates: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Reinstall a service with the given OS template
     *
     * @param string $serviceId Service ID
     * @param string $templateId Template ID
     * @param string $password New password
     * @return array|false Reinstall response or false on error
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
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to reinstall service: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Open a noVNC console session
     *
     * @param string $serviceId Service ID
     * @param string $type Optional console type hint
     * @return array|false Console data (proxy, url) or false on error
     * @throws RuntimeException If API request fails
     */
    public function noVncConsole(string $serviceId, string $type = '')
    {
        try {
            $body    = $type !== '' ? ['type' => $type] : [];
            $request = new Request("POST", "services/{$serviceId}/noVncConsole");
            $request->setBody($body);
            $response = $this->client->send($request);
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to open noVNC console: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Open an xTerm console session
     *
     * @param string $serviceId Service ID
     * @param string $type Optional console type hint
     * @return array|false Console data (proxy, url) or false on error
     * @throws RuntimeException If API request fails
     */
    public function xTermConsole(string $serviceId, string $type = '')
    {
        try {
            $body    = $type !== '' ? ['type' => $type] : [];
            $request = new Request("POST", "services/{$serviceId}/xTermConsole");
            $request->setBody($body);
            $response = $this->client->send($request);
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to open xTerm console: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Open a SPICE console session
     *
     * @param string $serviceId Service ID
     * @param string $type Optional console type hint
     * @return array|false Console data (proxy, url) or false on error
     * @throws RuntimeException If API request fails
     */
    public function spiceConsole(string $serviceId, string $type = '')
    {
        try {
            $body    = $type !== '' ? ['type' => $type] : [];
            $request = new Request("POST", "services/{$serviceId}/spiceConsole");
            $request->setBody($body);
            $response = $this->client->send($request);
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to open SPICE console: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get WMKS console URL
     *
     * @param string $serviceId Service ID
     * @return string|false WMKS console URL or false on error
     * @throws RuntimeException If API request fails
     */
    public function getWMKSUrl(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/actionWmksConsole");
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data['url'] ?? false;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get WMKS URL: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * SSO Login — generate a single sign-on URL for the service control panel
     *
     * @param string $serviceId Service ID
     * @return string|false SSO login URL or false on error
     * @throws RuntimeException If API request fails
     */
    public function ssoLogin(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/ssologin");
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data['data']['url'] ?? false;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to get SSO login URL: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Run SSL configuration wizard step one
     *
     * @param string $serviceId Service ID
     * @return array|false Step response or false on error
     * @throws RuntimeException If API request fails
     */
    public function sslStepOne(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/sslStepOne");
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to run SSL step one: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Run SSL configuration wizard step two
     *
     * @param string $serviceId Service ID
     * @return array|false Step response or false on error
     * @throws RuntimeException If API request fails
     */
    public function sslStepTwo(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/sslStepTwo");
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to run SSL step two: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Run SSL configuration wizard step three
     *
     * @param string $serviceId Service ID
     * @return array|false Step response or false on error
     * @throws RuntimeException If API request fails
     */
    public function sslStepThree(string $serviceId)
    {
        try {
            $response = $this->client->post("services/{$serviceId}/sslStepThree");
            $data     = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return false;
            }

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to run SSL step three: ' . $e->getMessage(), 0, $e);
        }
    }
}
