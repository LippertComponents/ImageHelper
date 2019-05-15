<?php

namespace LCI\MODX\ImageHelper;

use GuzzleHttp\RequestOptions;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use modX;

class ImageHelper
{
    /** @var ImageManager */
    protected $imageManger;

    /** @var array  */
    protected $cacheOptions = [
        //\xPDO::OPT_CACHE_KEY => 'image-helper'
    ];

    /** @var int $cache_life in seconds, 0 is forever */
    protected $cache_life = 3600*4;

    /** @var string  */
    protected $cache_directory = MODX_PATH.'assets/cache/';

    /** @var string */
    protected $cache_image_path;

    /** @var string the original image path */
    protected $image_path;

    /** @var string */
    protected $local_path;

    /** @var modX */
    protected $modx;

    /** @var bool  */
    protected static $package_added = false;

    /** @var array|false */
    protected static $image_cache = false;

    /** @var int  */
    protected $image_id = 0;

    /** @var string  */
    protected $crop_option = 'scale';

    /** @var int  */
    protected $quality = 60;

    /** @var int */
    protected $height = 0;

    /** @var int */
    protected $width;

    /**
     * ImageHelper constructor.
     * @param modX $modx
     * @param $image_path
     */
    public function __construct(modX $modx, $image_path)
    {
        $this->modx = $modx;
        if (!self::$package_added) {
            // the xPDO model as it does not follow PSR standards
            $this->modx->addPackage('imageHelper', __DIR__ . '/model/');
            self::$package_added = true;
        }

        $this->getImageCache();

        $this->image_path = $image_path;
    }

    /**
     * @return string
     */
    public function getCropOption(): string
    {
        return $this->crop_option;
    }

    /**
     * @return int
     */
    public function getQuality(): int
    {
        return $this->quality;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @param string $format
     * @return string
     */
    public function encode($format='data-url')
    {
        $this->make();
        if (isset($this->cache_image_path) && file_exists($this->cache_image_path)) {
            $this->loadImageManager();

            /** @var \Intervention\Image\Image $imageObject */
            $imageObject = $this->imageManger->make($this->cache_image_path);
            return (string)$imageObject->encode($format);
        }

        return '';
    }

    /**
     * @return string
     */
    public function make()
    {
        $http_path = $this->buildCachePath();

        if (file_exists($this->cache_image_path)) {
            return $http_path;
        }

        // make directories:
        $dir = pathinfo($this->cache_image_path, PATHINFO_DIRNAME);

        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $image_data = $this->loadImageFromCache();

        // need to build it now:
        $this->loadImageManager();

        $this->local_path = MODX_PATH.trim($this->image_path, '/');
        if ($image_data['remote']) {
            $this->local_path = $this->downloadRemoteFile($this->image_path);
        }

        try {
            /** @var Image $imageObject */
            $imageObject = $this->imageManger->make($this->local_path);

            if ($this->crop_option == 'pad') {
                $imageObject = $this->pad($imageObject);

            } elseif ($this->crop_option == 'fit') {
                $imageObject = $this->fit($imageObject);

            } else {
                $imageObject = $this->scale($imageObject);
            }

            $imageObject->save($this->cache_image_path);

        } catch (\Exception $exception) {
            // TODO log error message

            return $this->image_path;
        }

        return $http_path;
    }

    /**
     * @param string $crop_option - scale(default), fit or pad
     * @return ImageHelper
     */
    public function setCropOption(string $crop_option): self
    {
        $this->crop_option = $crop_option;
        return $this;
    }

    /**
     * @param int $quality
     * @return ImageHelper
     */
    public function setQuality(int $quality): self
    {
        $this->quality = $quality;
        return $this;
    }

    /**
     * @param int $height
     * @return ImageHelper
     */
    public function setHeight(int $height): self
    {
        $this->height = $height;
        return $this;
    }

    /**
     * @param int $width
     * @return ImageHelper
     */
    public function setWidth(int $width): self
    {
        $this->width = $width;
        return $this;
    }

    /**
     * @return string
     */
    protected function buildCachePath()
    {
        $image_details = $this->loadImageFromCache();

        $path = $image_details['id'].'/'.$this->crop_option.'-v_'.$image_details['version'].'-q_'.$this->quality;

        if (!empty($this->width)) {
            $path .= '-w_'.$this->width;
        }

        if (!empty($this->height)) {
            $path .= '-h_'.$this->height;
        }

        $path .= '/'.$image_details['name'];

        // TODO assets/cache/ as .env setting
        $this->cache_image_path = $this->cache_directory.$path;
        return '/assets/cache/'.$path;
    }

    /**
     * @param $remote_file
     * @param bool $force
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function downloadRemoteFile($remote_file, $force=false)
    {
        $name = pathinfo($remote_file, PATHINFO_FILENAME);
        // Need temp directory:
        $temp_file = $this->cache_directory.'temp/';
        if (!is_dir($temp_file)) {
            mkdir($temp_file, 0755, true);
        }
        $temp_file .= $name;

        if (!file_exists($temp_file) || $force) {
            /** @var  $response */
            $guzzleClient = new \GuzzleHttp\Client([
                'timeout' => 20.0,
                // http://docs.guzzlephp.org/en/latest/request-options.html#http-errors
                'http_errors' => false,
                'verify' => false,
            ]);

            $options = ['sink' => $temp_file];
            if (0) {
                $options[RequestOptions::STREAM] = true;
            }
            $guzzleClient->request('GET', urldecode($remote_file), $options);
        }

        return $temp_file;
    }

    /**
     * @param bool $reload
     */
    protected function getImageCache($reload=false)
    {
        if (!self::$image_cache || $reload) {
            $cache_key = 'image-helper-images';
            $images = $this->modx->cacheManager->get($cache_key, $this->cacheOptions);

            if (!$images || $reload) {
                // only send back valid permissions
                $images = [];

                $query = $this->modx->newQuery('ImageHelperImages');

                $imageHelperImages = $this->modx->getCollection('ImageHelperImages', $query);
                /** @var \ImageHelperImages $image */
                foreach ($imageHelperImages as $image) {
                    $images[$image->get()] = $image->toArray();
                }

                // now cache it:
                $this->modx->cacheManager->set(
                    $cache_key,
                    $images,
                    $this->cache_life,
                    $this->cacheOptions
                );
            }

            self::$image_cache = $images;
        }
    }

    protected function loadImageFromCache()
    {
        if (is_array(self::$image_cache) && isset(self::$image_cache[$this->image_path])) {
            return self::$image_cache[$this->image_path];
        }

        // create new record:
        $source = new Source($this->modx, $this, $this->image_path);
        $mediaSource = $source->getImageMediaSource();

        if ($mediaSource->isRemote()) {
            $this->local_path = $this->downloadRemoteFile($this->image_path);
        } else {
            $this->local_path = MODX_PATH.trim($this->image_path, '/');
        }

        $file_exists = 0;
        $width = $height = $size = 0;
        $version = 1;
        $date = date("Y-m-d H:i:s");
        if (file_exists($this->local_path)) {
            $size = filesize($this->local_path);
            list($width, $height) = getimagesize($this->local_path);
            $date = date("Y-m-d H:i:s", filemtime($this->local_path));
        }

        $imageHelperImage = $this->modx->getObject('ImageHelperImages', ['image' => $this->image_path]);
        if ($imageHelperImage) {
            $imageHelperImage = $this->modx->newObject('ImageHelperImages');
            $imageHelperImage->set('image', $this->image_path);
            $imageHelperImage->set('name', $name = pathinfo($this->image_path, PATHINFO_FILENAME));
        }

        $imageHelperImage->set('media_source_id', '');
        $imageHelperImage->set('remote', $mediaSource->isRemote());
        $imageHelperImage->set('extension', '');
        $imageHelperImage->set('width', $width);
        $imageHelperImage->set('height', $height);
        $imageHelperImage->set('size', $size);
        $imageHelperImage->set('date', $date);
        $imageHelperImage->set('file_exists', $file_exists);
        $imageHelperImage->set('version', $version);

        $imageHelperImage->save();

        // @TODO review
        $this->getImageCache(true);

        return $imageHelperImage->toArray();
    }

    /**
     *
     */
    protected function loadImageManager()
    {
        $driver = 'gd';
        if (extension_loaded('imagick')) {
            $driver = 'imagick';
        };

        /** @var ImageManager $manager */
        $this->imageManger = new ImageManager(['driver' => $driver]);
    }

    /**
     * @param Image $imageObject
     * @return Image
     */
    protected function fit(Image $imageObject)
    {
        if ($this->getHeight() > 0) {
            $imageObject->fit($this->getWidth(), $this->getHeight());
        } else {
            $imageObject->fit($this->getWidth());
        }

        return $imageObject;
    }

    /**
     * @param Image $imageObject
     * @return Image
     */
    protected function pad(Image $imageObject)
    {
        $height = $imageObject->height();
        if ($this->getHeight() > 0) {
            $height = $this->getHeight();
        }
        $thumb_sizes = $this->resizeDimensions($imageObject->width(), $imageObject->height(), $this->getWidth(), $this->getHeight());

        // if image height and width do not equal passed params:
        if ($thumb_sizes['width'] !== $this->getWidth() || $thumb_sizes['height'] !== $height) {
            // make canvas with background??
            $backgroundImage = $this->imageManger->canvas($this->getWidth(), $height);

            // insert:
            $backgroundImage->insert($this->scale($imageObject), 'center');

            return $backgroundImage;

        }

        return $this->scale($imageObject);
    }

    /**
     * @param Image $imageObject
     * @return Image
     */
    protected function scale(Image $imageObject)
    {
        $height = $imageObject->height();
        if ($this->getHeight() > 0) {
            $height = $this->getHeight();
        }

        $thumb_sizes = $this->resizeDimensions($imageObject->width(), $imageObject->height(), $this->getWidth(), $height);

        $imageObject->resize($thumb_sizes['width'], $thumb_sizes['height'], function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        return $imageObject;
    }

    /**
     * @param int $org_width
     * @param int $org_height
     * @param int $max_width
     * @param int $max_height
     * @param bool $upscale
     *
     * @return array
     */
    protected function resizeDimensions($org_width, $org_height, $max_width, $max_height, $upscale=false)
    {
        $ratio = $org_width/$org_height;
        $max_ratio = $max_width/$max_height;

        $height = $org_height;
        $width = $org_width;
        if ($ratio >= $max_ratio && ($org_width > $max_width || $upscale)) {
            //width is the largest for resizing
            $width = $max_width;
            $height = round($max_width/$ratio);

        } elseif ($ratio < $max_ratio && ($org_height > $max_height || $upscale)) {
            //height is the largest for resizing
            $height = $max_height;
            $width = round($ratio*$height);

        }

        return [
            'width' => $width,
            'height' => $height
        ];
    }
}