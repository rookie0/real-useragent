<?php

namespace Rookie0\RealUserAgent;

use Cache\Adapter\PHPArray\ArrayCachePool;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class UserAgent
 * @package Rookie0\RealUserAgent
 *
 * @property string chrome
 * @property string safari
 * @property string firefox
 * @property string ucBrowser
 * @property string opera
 * @property string samsungBrowser
 * @property string edge
 * @property string internetExplorer
 * @property string wechat
 *
 * @method string chrome(bool | array $filter, bool $refresh = false)
 * @method string safari(bool | array $filter, bool $refresh = false)
 * @method string firefox(bool | array $filter, bool $refresh = false)
 * @method string ucBrowser(bool | array $filter, bool $refresh = false)
 * @method string opera(bool | array $filter, bool $refresh = false)
 * @method string samsungBrowser(bool | array $filter, bool $refresh = false)
 * @method string edge(bool | array $filter, bool $refresh = false)
 * @method string internetExplorer(bool | array $filter, bool $refresh = false)
 * @method string wechat(bool | array $filter, bool $refresh = false)
 */
class UserAgent
{

    /**
     * where get user agent information
     */
    const REQUEST_BASE_URI = 'https://developers.whatismybrowser.com/useragents/explore/';

    /**
     * user config array
     * @var array
     */
    protected $config;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var int
     */
    protected $pageNum = 1;

    /**
     * request timeout sec
     * @var int
     */
    protected $timeout = 5;

    /**
     * cache expired sec
     * @var int
     */
    protected $cacheTtl = 60 * 60 * 24;

    /**
     * @var string
     */
    protected $cacheKeyPrefix = 'realuseragent';

    /**
     * @var Client
     */
    protected static $httpClient;

    public function __construct(array $config = [], CacheInterface $cache = null)
    {
        $this->parseConfig($config);
        $this->cache = $cache ?: new ArrayCachePool();
    }

    /**
     * grab user agent info
     * @param string $category
     * @param string $name
     * @param string $orderBy
     * @param bool   $refresh
     * @return array
     */
    public function collect($category, $name, $orderBy = '-times_seen', $refresh = false)
    {
        if (null === self::$httpClient) {
            self::$httpClient = new Client([
                'base_uri' => self::REQUEST_BASE_URI,
                'timeout'  => $this->timeout,
                'headers'  => [
                    'User-Agent' => '',
                ],
            ]);
        }

        $cacheKey = "{$this->cacheKeyPrefix}_{$category}_{$name}_{$this->pageNum}_{$orderBy}";
        if (! $refresh && $data = $this->cache->get($cacheKey)) {
            return $data;
        }

        $data = [];
        for ($i = 1; $i < $this->pageNum + 1; $i++) {
            $resp = self::$httpClient->get("{$category}/{$name}/{$i}?order_by={$orderBy}");
            $data = array_merge($data, $this->extractUserAgent($resp->getBody()->getContents()));
        }

//        // concurrency request
//        $requests = function ($total) use ($category, $name, $orderBy) {
//            for ($i = 1; $i < $total + 1; $i++) {
//                yield function () use ($i, $category, $name, $orderBy) {
//                    return self::$httpClient->getAsync("{$category}/{$name}/{$i}?order_by={$orderBy}");
//                };
//            }
//        };
//
//        $data = [];
//        $pool = new Pool(self::$httpClient, $requests($this->pageNum), [
//            // concurrency limit
//            'concurrency' => 3,
//            'fulfilled'   => function (Response $response, $index) use (&$data) {
//                if ($response->getStatusCode() == 200) {
//                    $data = array_merge($data, $this->extractUserAgent($response->getBody()->getContents()));
//                } else {
//                    // todo log
//                }
//            },
//            'rejected'    => function ($reason, $index) {
//                // todo log reason
//                throw $reason;
//            },
//        ]);
//        $pool->promise()->wait();

        $this->cache->set($cacheKey, $data, $this->cacheTtl);

        return $data;
    }

    /**
     * random pick a user agent from ['chrome', 'safari', 'firefox', 'opera', 'edge']
     * @param array $filter
     * @param bool  $refresh
     * @return bool
     */
    public function random($filter = [], $refresh = false)
    {
        $filter = array_merge([
            'category'         => 'software_name',
            'name'             => ['chrome', 'safari', 'firefox', 'opera', 'edge'][rand(0, 4)],
            'order_by'         => '-times_seen',
            'software_version' => '',
            'operating_system' => '',
            'hardware_type'    => '',
        ], $filter);

        $data = $this->collect($filter['category'], $filter['name'], $filter['order_by'], $refresh);
        if ($filter['software_version'] || $filter['operating_system'] || $filter['hardware_type']) {
            $data = array_values(array_filter($data, function ($item) use ($filter) {
                if ($filter['software_version'] && $item['software_version'] !== $filter['software_version']) {
                    return false;
                }
                if ($filter['operating_system'] && $item['operating_system'] !== $filter['operating_system']) {
                    return false;
                }
                if ($filter['hardware_type'] && $item['hardware_type'] !== $filter['hardware_type']) {
                    return false;
                }
                return true;
            }));
        }

        return $data ? $data[array_rand($data)]['user_agent'] : false;
    }

    /**
     * get user agent by software_name
     * @param $name
     * @return string
     */
    public function __get($name)
    {
        return $this->random(['name' => self::kebabCase($name)]);
    }

    /**
     * get user agent by software_name and filter by software_version,operating_system,hardware_type
     * @param $name
     * @param $args
     * @return bool
     */
    public function __call($name, $args)
    {
        $count  = count($args);
        $filter = ['name' => self::kebabCase($name)];

        if ($count === 1 && $args[0] === true) {
            $refresh = true;
        } else {
            $params  = isset($args[0]) && is_array($args[0]) ? $args[0] : [];
            $refresh = isset($args[1]) ? (bool)$args[1] : false;
            $filter  = array_merge($filter, array_intersect_key($params, array_flip([
                'software_version',
                'operating_system',
                'hardware_type',
            ])));
        }

        return $this->random($filter, $refresh);
    }

    protected static function kebabCase($name)
    {
        return ctype_lower($name) ? $name : strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1-', $name));
    }

    protected function extractUserAgent($html)
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);

        $data = [];
        $crawler->filterXPath('//table[contains(@class,"table-useragents")]/tbody/tr')->each(function (Crawler $crawler) use (&$data) {
            $data[] = [
                'user_agent'       => $crawler->filterXPath('//td[1]')->text(),
                'software_version' => $crawler->filterXPath('//td[2]')->text(),
                'operating_system' => $crawler->filterXPath('//td[3]')->text(),
                'hardware_type'    => $crawler->filterXPath('//td[4]')->text(),
                'popularity'       => $crawler->filterXPath('//td[5]')->text(),
            ];
        });

        return $data;
    }

    protected function parseConfig(array $config)
    {
        if (isset($config['timeout']) && $config['timeout'] > 0) {
            $this->timeout = (int)$config['timeout'];
        }

        if (isset($config['cache_ttl'])) {
            $this->cacheTtl = (int)$config['cache_ttl'];
        }

        if (isset($config['cache_key_prefix'])) {
            $this->cacheKeyPrefix = $config['cache_key_prefix'];
        }

        if (isset($config['page_num']) && $config['page_num'] < 12 && $config['page_num'] > 0) {
            $this->pageNum = (int)$config['page_num'];
        }

        $this->config = $config;
    }

}