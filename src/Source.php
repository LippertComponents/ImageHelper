<?php


namespace LCI\MODX\ImageHelper;

use modX;

class Source
{
    /** @var array  */
    protected $cacheOptions = [
        //\xPDO::OPT_CACHE_KEY => 'image-helper'
    ];

    /** @var int $cache_life in seconds, 0 is forever */
    protected $cache_life = 3600*24;

    protected $modx;

    /** @var bool|array */
    protected static $media_source_cache = false;

    /** @var int */
    protected $media_source_id = 0;

    /** @var ImageMaker */
    protected $imageHelper;

    /** @var string */
    protected $image_path;

    /** @var bool  */
    protected $remote = false;

    /**
     * Source constructor.
     * @param $modx
     * @param ImageMaker $imageHelper
     * @param string $image_path
     */
    public function __construct($modx, ImageMaker $imageHelper, string $image_path)
    {
        $this->modx = $modx;
        $this->imageHelper = $imageHelper;
        $this->image_path = $image_path;

        $this->loadMediaSourceCache();

        $this->determineFileMediaSource();
    }

    /**
     * @return int
     */
    public function getImageMediaSource()
    {
        return $this->media_source_id;
    }

    /**
     * @return bool
     */
    public function isRemote()
    {
        return $this->remote;
    }

    /**
     * @return string - NOT Functional
     */
    public function getFullLocalPath()
    {
        // @TODO
        return $this->image_path;
    }

    /**
     * @return int
     */
    protected function determineFileMediaSource()
    {
        // Filesystem has empty properties, so use MODX_PATH AND MODX_ASSET_PATH
        if (strpos($this->image_path, 'http') === 0) {
            $this->remote = true;
        }

        if (strpos($this->image_path, '//:') === 0) {
            $this->remote = true;
        }

        if ($this->remote) {
            $this->media_source_id = $this->findRemoteMediaSourceID($this->image_path);

        } else {
            $this->media_source_id = $this->findLocalMediaSourceID($this->image_path);
        }

        return $this->media_source_id;
    }

    /**
     * @param string $folders
     * @return int
     */
    protected function findLocalMediaSourceID($folders)
    {
        if (empty($folders)) {
            return 0;
        }

        $tmp = '/' . trim($folders, '/');
        $tmp = str_replace('//', '/', pathinfo($tmp,  PATHINFO_DIRNAME).'/');

        if (isset(static::$media_source_cache['local'][$folders])) {
            return static::$media_source_cache['local'][$folders]['id'];
        }

        if ($tmp == '/') {
            $tmp = '';
        }

        return $this->findLocalMediaSourceID($tmp);
    }

    /**
     * @param string $url Example: https://test.s3.amazonaws.com/uploads/backgrounds/corp/about/products.jpg
     * @return int
     */
    protected function findRemoteMediaSourceID($url)
    {
        if (empty($url)) {
            return 0;
        }

        $last_slash = strrpos($url, '/');
        if ($last_slash > 0) {
            $url = substr($url, 0, $last_slash);
        }

        if (isset(static::$media_source_cache['local'][$url])) {
            return static::$media_source_cache['local']['id'];

        } elseif (isset(static::$media_source_cache['local'][$url.'/'])) {
            return static::$media_source_cache['local'][$url.'/']['id'];

        } elseif (!$last_slash || $last_slash <= strlen('https://')) {
            return 0;
        }

        return $this->findRemoteMediaSourceID($url);
    }

    /**
     * @param bool $reload
     */
    protected function loadMediaSourceCache($reload=false)
    {
        if (!self::$media_source_cache) {
            $cache_key = 'image-helper-media-sources';
            $cacheManager = $this->modx->getCacheManager();
            $media_sources = $cacheManager->get($cache_key, $this->cacheOptions);

            if (!$media_sources || $reload) {
                // only send back valid permissions
                $media_sources = [
                    'local' => [], // sources.modFileMediaSource
                    'remote' => []
                ];

                $query = $this->modx->newQuery('sources.modMediaSource');

                $mediaSources = $this->modx->getCollection('sources.modMediaSource', $query);
                /** @var \modMediaSource $mediaSource */
                foreach ($mediaSources as $mediaSource) {
                    $data = $mediaSource->toArray();
                    if ($data['class_key'] == 'sources.modFileMediaSource') {
                        $base_key = '/';
                        if (isset($data['properties']['baseUrl']) && isset($data['properties']['baseUrl']['value'])) {
                            $base_key .= ltrim($data['properties']['baseUrl']['value'], '/');
                        }

                        $media_sources['local'][$base_key] = $data;

                    } else {
                        // AwsS3MediaSource, same for others?
                        $base_key = '';
                        if (isset($data['properties']['url']) && isset($data['properties']['url']['value'])) {
                            $base_key = $data['properties']['url']['value'];
                        }

                        if (isset($data['properties']['baseDir']) &&
                            isset($data['properties']['baseDir']['value']) &&
                            !empty($data['properties']['baseDir']['value'])
                        ) {
                            $base_key .= $data['properties']['baseDir']['value'];
                        }

                        unset($data['properties']['key'], $data['properties']['secret_key']);

                        $media_sources['remote'][$base_key] = $data;
                    }
                }

                // now cache it:
                $cacheManager->set(
                    $cache_key,
                    $media_sources,
                    $this->cache_life,
                    $this->cacheOptions
                );
            }

            self::$media_source_cache = $media_sources;
        }
    }
}