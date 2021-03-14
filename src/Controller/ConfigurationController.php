<?php

namespace App\Controller;

use App\Repository\WeatherConfigurationRepository;
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

    /**
     * SensorController constructor.
     *
     * @param WeatherConfigurationRepository|null $weatherConfigurationRepository
     * @param WeatherStationLogger $logger
     * @param WeatherConfiguration $config
     */
    public function __construct(WeatherConfigurationRepository $weatherConfigurationRepository, WeatherStationLogger $logger, WeatherConfiguration $config) {
        $this->response  = new Response();
        $this->response->headers->set('Content-Type', 'application/json');

        $this->request  = new Request();
        $this->logger = $logger;
        $this->weatherConfigurationRepository = $weatherConfigurationRepository;
        $this->config = $config;
        $this->response->headers->set('weatherStation-version', $this->config->getConfigValue('application.version'));
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
     * @Route("/weatherstation/api/config/{key}/{value}",  methods={"POST"}, name="post_config")
     * @param string $key
     * @param string $value
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function post(string $key, string $value, Request $request): Response {
        $result = $this->weatherConfigurationRepository->save($key, $value);
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
//        // The callable will only be executed on a cache miss.
//        $value = $this->cache->get('cache_'.$key, function (ItemInterface $item) use ($key) {
//            $item->expiresAfter(3600000);
//            $response = $this->weatherConfigurationRepository->findByQuery(['configKey' => $key]);
//            $computedValue = $this->json($response)->getContent();
//            return $computedValue;
//        });
        $value = $this->weatherConfigurationRepository->getConfigValue($key);
        $computedValue = $this->json($value)->getContent();
        $this->response->setContent($computedValue);
        return $this->response;
    }

    /**
     *
     *@Route("/weatherstation/api/config/{key}/{value}", methods={"PATCH"}, name="update_config")
     */
    public function patch($key,$value) {
        $value = $this->weatherConfigurationRepository->update($key,$value);
        return $this->response;
    }
}
