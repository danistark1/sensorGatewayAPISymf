<?php

namespace App\Controller;

use App\Repository\WeatherConfigurationRepository;
use App\WeatherCacheHandler;
use App\WeatherConfiguration;
use App\WeatherStationLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;



/**
 * Class ConfigurationController
 *
 * @package App\Controller
 */
class ConfigurationController extends AbstractController {

    /** @var WeatherConfigurationRepository|null  */
    public $weatherConfigurationRepository;

    /**
     * @var Response $response
     */
    private $response;

    private $cache;

    /** @var WeatherCacheHandler  */
    private $configCache;

    /**
     * SensorController constructor.
     *
     * @param WeatherConfigurationRepository|null $weatherConfigurationRepository
     * @param WeatherStationLogger $logger
     * @param WeatherConfiguration $config
     */
    public function __construct(WeatherConfigurationRepository $weatherConfigurationRepository, WeatherStationLogger $logger, WeatherCacheHandler $configCache) {
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
     * @Route("/configuration", name="configuration")
     */
    public function index(): Response {
        return $this->render('configuration/index.html.twig', [
            'controller_name' => 'ConfigurationController',
        ]);
    }

    /**
     * Post weatherData.
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
     * @param Request $request
     * @param $key
     * @return Response
     * @throws \Psr\Cache\InvalidArgumentException
     * @Route("/weatherstation/api/config/{key}", methods={"GET"}, name="get_config")
     */
    public function getConfig(Request $request, $key): Response {
        $value = $this->configCache->getConfigKey($key);
        $this->response->setContent($value);
        return $this->response;
    }

    /**
     * Update a config
     *
     *@Route("/weatherstation/api/config/{key}/{value}", methods={"PATCH"}, name="update_config")
     */
    public function patch($key, $value) {
        $this->weatherConfigurationRepository->update($key,$value);
        return $this->response;
    }
}
