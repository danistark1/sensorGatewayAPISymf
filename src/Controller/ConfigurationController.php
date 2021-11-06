<?php

namespace App\Controller;

use App\Repository\SensorConfigurationRepository;
use App\GatewayCache\SensorCacheHandler;
use App\GatewayCache\SensorConfiguration;
use App\Logger\SensorGatewayLogger;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ConfigurationController
 *
 * @package App\Controller
 */
class ConfigurationController extends AbstractController {

    // Status Codes
    const STATUS_OK = 200;
    const STATUS_RESOURCE_CREATED = 201;
    const STATUS_NO_CONTENT = 204;
    const STATUS_VALIDATION_FAILED = 400;
    const STATUS_NOT_FOUND = 404;
    const STATUS_EXCEPTION = 500;
    const CACHE_CLEARED = 'Cache cleared.';
    const NO_RESPONSE = 'Empty response.';

    public const VALIDATION_INVALID_KEY = 'Config key could not be found.';
    public const VALIDATION_INVALID_VALUE = 'Config value could not be found.';

    /** @var SensorConfigurationRepository|null  */
    public $weatherConfigurationRepository;

    /**
     * @var Response $response
     */
    private $response;

    private $cache;

    /** @var SensorCacheHandler  */
    private $configCache;

    /** @var SensorGatewayLogger  */
    private $logger;

    /**
     * SensorController constructor.
     *
     * @param SensorConfigurationRepository|null $weatherConfigurationRepository
     * @param SensorGatewayLogger $logger
     * @param SensorConfiguration $config
     */
    public function __construct(
        SensorConfigurationRepository $weatherConfigurationRepository,
        SensorGatewayLogger $logger,
        SensorCacheHandler $configCache
    ) {
        $this->response  = new Response();
        $this->response->headers->set('Content-Type', 'application/json');
        $this->request  = new Request();
        $this->logger = $logger;
        $this->weatherConfigurationRepository = $weatherConfigurationRepository;
        $this->configCache = $configCache;
        $this->time_start = microtime(true);
        $this->cache = new FilesystemAdapter();
    }

    /**
     * Get all available config Keys.
     * This will be used for a drop-down ui to update configs.
     *
     * @Route("/weatherstation/api/config/keys", name="get_all_config_keys")
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getAllKeys(): Response {
        $allConfigs = $this->configCache->getAllConfigKeys();
        $this->validateResponse($allConfigs, __CLASS__.__FUNCTION__);
        return $this->response;
    }

    /**
     * Get all configs.
     *
     * @Route("/weatherstation/api/configs", name="get_all_configs")
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getAllConfigs(): Response {
        $allConfigs = $this->configCache->getAllConfigs();
        $this->validateResponse($allConfigs, __CLASS__.__FUNCTION__);
        return $this->response;
    }

    /**
     * Validate API response.
     *
     * @param array $response
     * @param string $sensorIdentifier
     */
    private function validateResponse(array $response, $configIdentifier = '') {
        $responseJson = $this->json($response)->getContent();
        if (empty($responseJson)){
            $this->response->setStatusCode(self::STATUS_NO_CONTENT);
            $this->logger->log(self::NO_RESPONSE, ['id' => $configIdentifier], Logger::INFO);
        } else {
            $this->response->setContent($responseJson);
            $this->response->setStatusCode(self::STATUS_OK);
        }
    }

    /**
     * Post a config.
     *
     * @Route("/weatherstation/api/config",  methods={"POST"}, name="post_config")
     * @param string $key
     * @param string $value
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function post(Request $request): Response {
        $parameters = json_decode($request->getContent(), true);
        // TODO Validation.
        $this->weatherConfigurationRepository->save($parameters);
        $this->configCache->clearCache();
        return $this->response;
    }

    /**
     * Get a config.
     *
     * @param Request $request
     * @param $key
     * @return Response
     * @throws \Psr\Cache\InvalidArgumentException
     * @Route("/weatherstation/api/config/{key}", methods={"GET"}, name="get_config")
     */
    public function getConfig(Request $request, $key): Response {
        $value = $this->configCache->getConfigKey($key);
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $this->response->setContent($value);

        return $this->response;
    }

    /**
     * Update a config
     *
     * @Route("/weatherstation/api/config/{key}/{value}", methods={"PATCH"}, name="update_config")
     * @param $key
     * @param $value
     * @return Response
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function patch($key, $value): Response {
        $isValidKey = $this->validateKey($key, __CLASS__.__FUNCTION__);
        if ($isValidKey) {
            $this->weatherConfigurationRepository->update($key, $value);
            $this->configCache->clearCacheKey('cache_'.$key);
            $this->response->setStatusCode(self::STATUS_RESOURCE_CREATED);
        }
        return $this->response;
    }

    /**
     * Kill cache.
     *
     * @Route("/weatherstation/api/config/deletecache", methods={"DELETE"}, name="delete_cache")
     * @return Response
     */
    public function deleteCache(): Response {
        try {
            $this->configCache->clearCache();
        } catch (\Exception $e) {
            $this->logger->log("Cache clear failed", ['errorMsg' => $e->getMessage()],Logger::CRITICAL);
        }

        $this->updateResponse(
            self::CACHE_CLEARED,
            self::STATUS_OK,
            [
                'loggerMsg' => 'Cache cleared.',
                'loggerLevel' => Logger::INFO,
                'loggerContext' => [
                    'method' => __CLASS__.__FUNCTION__,
                ],
            ]
        );
        return $this->response;
    }

    /**
     * Validate config key.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function validateKey(string $key, $sender): bool {
        $valid = true;
        $isValidKey = $this->configCache->isConfigSetKey($key);
        if (!$isValidKey) {
            $this->updateResponse(
                self::VALIDATION_INVALID_KEY,
                self::STATUS_VALIDATION_FAILED,
                ['loggerMsg' =>  self::VALIDATION_INVALID_KEY,
                    'loggerContext' => [
                        'method' => $sender,
                        'key' => $key
                    ],
                    'loggerLevel' => Logger::ALERT
                ]
            );
        }
        return $valid;
    }

    /**
     * Update Response object and return.
     *
     * @param string $message
     * @param int $statusCode
     * @param $loggerParams
     */
    private function updateResponse(string $message, int $statusCode, $loggerParams = []) {
        $this->response->setContent($message);
        $this->response->setStatusCode($statusCode);
        if (!empty($loggerParams)) {
            $this->logger->log($loggerParams['loggerMsg'], $loggerParams['loggerContext'],$loggerParams['loggerLevel']);
        }
    }

    /**
     * Validate config value.
     *
     * @param string $value
     * @param string $sender
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function validateValue(string $value, string $sender): bool {
        $valid = true;
        $isValidValue = $this->configCache->isConfigSetValue($value);
        if (!$isValidValue) {
            $this->updateResponse(
                self::VALIDATION_INVALID_VALUE,
                self::STATUS_VALIDATION_FAILED,
                ['loggerMsg' =>  self::VALIDATION_INVALID_VALUE,
                    'loggerContext' => [
                        'method' => $sender,
                        'value' => $value
                    ],
                    'loggerLevel' => Logger::ALERT
                ]
            );
        }
        return $valid;
    }
}
