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

    /** @var ImageHelper */
    protected $imageHelper;

    /** @var string */
    protected $image_path;

    /**
     * Source constructor.
     * @param $modx
     * @param ImageHelper $imageHelper
     * @param string $image_path
     */
    public function __construct($modx, ImageHelper $imageHelper, string $image_path)
    {
        $this->modx = $modx;
        $this->imageHelper = $imageHelper;
        $this->image_path = $image_path;

        self::loadMediaSourceCache();

        //
    }

    /**
     * @return int
     */
    public function getImageMediaSource()
    {
        // TODO return int

        return 0;
    }

    public function isMediaSourceRemote()
    {
        // @TODO

        return false;
    }

    public static function getFullLocalPath($source_id, $image)
    {
        self::loadMediaSourceCache();
    }

    protected function determineFileMediaSource()
    {

    }

    protected static function loadMediaSourceCache()
    {
        if (!self::$media_source_cache) {
            // TODO load
        }
    }

    protected function getImageCache($reload=false)
    {
        if (!self::$media_source_cache || $reload) {
            $cache_key = 'image-helper-media-sources';
            $media_sources = $this->modx->cacheManager->get($cache_key, $this->cacheOptions);

            if (!$media_sources || $reload) {
                // only send back valid permissions
                $media_sources = [];

                $query = $this->modx->newQuery('modMediaSources');

                $mediaSources = $this->modx->getCollection('modMediaSources', $query);
                /** @var \ImageHelperImages $image */
                foreach ($mediaSources as $image) {
                    // @TODO:
                    $media_sources[$image->get()] = $image->toArray();
                }

                // now cache it:
                $this->modx->cacheManager->set(
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