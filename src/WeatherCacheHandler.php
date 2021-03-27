<?php


namespace App;


use App\Repository\WeatherConfigurationRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

//TODO Rename class to WeatherConfigCacheHandler
class WeatherCacheHandler {

//    public const PRUNING = 'pruning-report-';
//    public const MAIN_SENSOR_CONFIG = 'sensor-config-';
//    public const APP_CONFIG = 'application-';
//    public const READINGS_CONFIG = 'weatherReport-';


    // CONFIG TYPES
    public const CONFIG_TYPE_THRESHOLDS = 'thresholds';
    public const CONFIG_TYPE_PRUNING = 'pruning';
    public const CONFIG_TYPE_SENSOR = 'sensor-config';
    public const CONFIG_TYPE_APP = 'app-config';
    public const CONFIG_TYPE_READINGS = 'readings';

    public const CACHE_EXPIRE = 31500000;


/** @var FilesystemAdapter  */
    private $cache;
    /** @var ManagerRegistry  */
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry) {
        $this->cache = new FilesystemAdapter();
        $this->managerRegistry = $managerRegistry;

        // TODO Structure method to create all db configs.
    }

    /**
     * Find a record.
     *
     * @param string $lookupKey
     * @param string $configKey
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getConfigKey(string $lookupKey) {
        $value = $this->cache->get('cache_'.$lookupKey, function (ItemInterface $item) use ($lookupKey) {
            $item->expiresAfter(self::CACHE_EXPIRE);
            // cache expires in 365 days.
            $weatherConfigRepo = new WeatherConfigurationRepository($this->managerRegistry);
            if ($lookupKey !== '') {
                $dbValue =  $weatherConfigRepo->findBy(['configKey' => $lookupKey],[], 1);
            } else {
                $this->cache->delete('cache_'.$lookupKey);
            }
            return $dbValue;
        });
        // If config is not found, make sure cache key is also not found.
        if (empty($value)) {
            $this->clearCacheKey('cache_'.$lookupKey);
        }
        $result = !empty($value[0]) ? $value[0]->getConfigValue() : false;
        return $result;
    }

    /**
     * Clear all cache.
     */
    public function clearCache() {
        $this->cache->clear();
    }

    /**
     * Delete Cache by key.
     *
     * @param $key
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function clearCacheKey($key) {
        $this->cache->delete($key);

    }

    /**
     * Find a record.
     *
     * @param string $key
     * @param string $value
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getConfigValue(string $value = '') {
        //$this->cache->delete('cache_'.$value);
        // Gets a cache value and sets it if it doesn't already exist.
        $value = $this->cache->get('cache_'.$value, function (ItemInterface $item) use ($value) {
            // TODO should be a config.
            $item->expiresAfter(self::CACHE_EXPIRE);
            // cache expires in 41 days.
            $weatherRepo = new WeatherConfigurationRepository($this->managerRegistry);
            $dbValue = [];
            if ($value !== '') {
                $dbValue =  $weatherRepo->findBy(['configValue' => $value],[], 1);
            } else {
                $this->cache->delete('cache_'.$value);
            }
            return $dbValue;
        });
        // If config is not found, make sure cache key is also not found.
        if (empty($value)) {
            $this->clearCacheKey('cache_'.$value);
        }
        return !empty($value[0]) ? $value[0]->getConfigKey() : false;
    }

    /**
     * Get an array of cached sensor configs.
     */
    public function getSensorConfigs() {
        // Construct a cached copy of
        // ["bedroom" => "6126"
        //  "basement" => "3026"
        //   "garage" => "8166"
        //   "living_room" => "15043"
        //   "outside" => "12154"]

        //$this->cache->delete('cache_sensor_configs');
        // Gets a cache value and sets it if it doesn't already exist.
        $value = $this->cache->get('cache_sensor_configs', function (ItemInterface $item) {
            // TODO should be a config.
            $item->expiresAfter(self::CACHE_EXPIRE);
            // cache expires in 365 days.
            $weatherRepo = new WeatherConfigurationRepository($this->managerRegistry);
            $dbValue =  $weatherRepo->findBy(['config_type' => self::CONFIG_TYPE_SENSOR]);
            $dbConfigs = [];
            foreach($dbValue as $key => $value) {
                $modifiedConfig = substr($value->getConfigKey(), 14);
                $dbConfigs[$modifiedConfig]  = $value->getConfigValue();
            }
            return $dbConfigs;
        });
        // If config is not found, make sure cache key is also not found.
        if (empty($value)) {
            $this->clearCacheKey('cache_'.$value);
        }
        return $value;
    }

    /**
     * Check if a config is set.
     *
     * @param $key
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isConfigSetKey($key): bool {
        if ($key !== '') {
            $isSet = $this->getConfigKey($key) ?? false;
        }  else {
            $isSet = false;
        }
        return $isSet;
    }

    /**
     * Check if a config is set.
     *
     * @param $key
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isConfigSetValue($value = ''): bool {
        if ($value !== '') {
            $isSet = $this->getConfigValue($value) ?? false;
        }  else {
            $isSet = false;
        }
        return $isSet;
    }
}
