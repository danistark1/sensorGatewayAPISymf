<?php


namespace App;


use App\Repository\WeatherConfigurationRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class WeatherCacheHandler {
/** @var FilesystemAdapter  */
    private $cache;
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry) {
        $this->cache = new FilesystemAdapter();
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Find a record.
     *
     * @param string $key
     * @param string $value
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getConfigKey(string $key = '') {
        //$this->cache->delete('cache_'.$key);
        $value = $this->cache->get('cache_'.$key, function (ItemInterface $item) use ($key) {
            $item->expiresAfter(2);
            // cache expires in 41 days.
            $weatherRepo = new WeatherConfigurationRepository($this->managerRegistry);
            if ($key !== '') {
                $dbValue =  $weatherRepo->findBy(['configKey' => $key],[], 1);
            } else {
                $this->cache->delete('cache_'.$key);
            }
            return $dbValue;
        });
        return !empty($value[0]) ? $value[0]->getConfigValue() : false;
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
        $value = $this->cache->get('cache_'.$value, function (ItemInterface $item) use ($value) {
            $item->expiresAfter(2);
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
        return !empty($value[0]) ? $value[0]->getConfigKey() : false;
    }

    /**
     * Check if a config is set.
     *
     * @param $key
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isConfigSetKey($key = ''): bool {
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
