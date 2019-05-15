<?php

use LCI\MODX\ImageHelper\ImageHelper;

// Defaults:
$crop = 'scale';
$quality = 60;
$encode = false;
$height = 0;
$width = 0;
$image_path = '';

/**
 * Output Filter:
 * https://docs.modx.com/revolution/2.x/making-sites-with-modx/customizing-content/input-and-output-filters-(output-modifiers)/custom-output-filter-examples
 *
 *
 */

if (isset($input)) {
    $image_path = $input;
}

if (isset($options)) {
    if (!isset($scriptProperties)) {
        $scriptProperties = [];
    }

    $ptOptions = array();

    $output_filter_options = is_array($options) ? $options : explode('&', $options);

    foreach ($output_filter_options as $option) {
        if (empty($option)) {
            continue;
        }

        list($key, $value) = explode('=', $option);

        $scriptProperties[$key] = $value;
    }
}

// Normal Snippet:
$crop = $modx->getOption('crop', $scriptProperties, $modx->getOption('c', $scriptProperties, $crop));
$quality = $modx->getOption('quality', $scriptProperties, $modx->getOption('q', $scriptProperties, $quality));
$encode = $modx->getOption('encode', $scriptProperties, $modx->getOption('e', $scriptProperties, $encode));
$height = $modx->getOption('height', $scriptProperties, $modx->getOption('h', $scriptProperties, $height));
$width = $modx->getOption('width', $scriptProperties, $modx->getOption('w', $scriptProperties, $width));
$image_path = $modx->getOption('src', $scriptProperties, $modx->getOption('s', $scriptProperties, $image_path));


$new_image = $image_path;

if (empty($image_path) || (empty($width) && empty($height))) {
    return $image_path;
}

$imageHelper = new ImageHelper(new modX(), $image_path);

$new_image = $imageHelper
    ->setCropOption($image_path)
    ->setQuality($quality)
    ->setWidth($width)
    ->setHeight($height)
    ->make();

if ($encode !== false) {
     return $imageHelper->encode($encode);
}

return $new_image;