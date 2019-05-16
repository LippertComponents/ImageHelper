<?php

use LCI\MODX\ImageHelper\ImageMaker;

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
$crop = $modx->getOption('c', $scriptProperties, $modx->getOption('crop', $scriptProperties, $crop));
$quality = $modx->getOption('q', $scriptProperties, $modx->getOption('quality', $scriptProperties, $quality));
$encode = $modx->getOption('e', $scriptProperties, $modx->getOption('encode', $scriptProperties, $encode));
$height = $modx->getOption('h', $scriptProperties, $modx->getOption('height', $scriptProperties, $height));
$width = $modx->getOption('w', $scriptProperties, $modx->getOption('width', $scriptProperties, $width));
$image_path = $modx->getOption('s', $scriptProperties, $modx->getOption('src', $scriptProperties, $image_path));


$new_image = $image_path;

if (empty($image_path) || (empty($width) && empty($height))) {
    return $image_path;
}

$imageHelper = new ImageMaker($modx, $image_path);

$new_image = $imageHelper
    ->setCropOption($crop)
    ->setQuality($quality)
    ->setWidth($width)
    ->setHeight($height)
    ->make();

if ($encode !== false && !empty(trim($encode))) {
     return $imageHelper->encode($encode);
}

return $new_image;