<?php

namespace Spatie\Analytics;

use DateTime;
use Google_Service_Analytics;
use Illuminate\Contracts\Cache\Repository;

class AnalyticsClient
{
    /** @var \Google_Service_Analytics */
    protected $service;

    /** @var \Illuminate\Contracts\Cache\Repository */
    protected $cache;

    /** @var */
    protected $cacheLifeTimeInMinutes = 0;

    public function __construct(Google_Service_Analytics $service, Repository $cache)
    {
        $this->service = $service;

        $this->cache = $cache;
    }

    /**
     * Set the cache time.
     *
     * @param $cacheLifeTimeInMinutes
     *
     * @return self
     */
    public function setCacheLifeTimeInMinutes($cacheLifeTimeInMinutes)
    {
        $this->cacheLifeTimeInMinutes = $cacheLifeTimeInMinutes;

        return $this;
    }

    /**
     * Query the Google Analytics Service with given parameters.
     *
     * @param    $viewId
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param    $metrics
     * @param array     $others
     *
     * @return array|null
     */
    public function performQuery($viewId, DateTime $startDate, DateTime $endDate, $metrics, array $others = [])
    {
        $cacheName = $this->determineCacheName(func_get_args());

        if ($this->cacheLifeTimeInMinutes == 0) {
            $this->cache->forget($cacheName);
        }

        return $this->cache->remember($cacheName, $this->cacheLifeTimeInMinutes, function () use ($viewId, $startDate, $endDate, $metrics, $others) {

           return $this->service->data_ga->get(
               "ga:{$viewId}",
               $startDate->format('Y-m-d'),
               $endDate->format('Y-m-d'),
               $metrics,
               $others
           );
        });
    }

    public function getAnalyticsService()
    {
        return $this->service;
    }

    /*
     * Determine the cache name for the set of query properties given.
     */
    protected function determineCacheName(array $properties)
    {
        return 'spatie.laravel-analytics.'.md5(serialize($properties));
    }
}
