# ImageHelper for MODX

ImageHelper is a simple extra to scale, fit, pad or encode your images to an exact size. Can be used as a snippet or output filter.  
It is using the popular [Intervention Image](http://image.intervention.io/) package.

Alternative to [pThumb](https://github.com/modxcms/pThumb), [phpThumbsUp](https://github.com/darkstardigital/phpThumbsUp) and
even older [phpThumbOf](https://github.com/modxcms/phpthumbof/)

## Install via Composer

If you have not already installed [Orchestrator](https://github.com/LippertComponents/Orchestrator), please install first. 

Run the following command:
```
composer install lci/modx-image-helper
```

Or add to your composer.json file as in the require section and then you can run ```composer update```.

And then to install within MODX
```
$ cd core/
$ php vender/bin/orchestrator orch-package lci/modx-image-helper
```
 
## Snippet Properties

| Property | Short Cut | Description | Default |
|---|---|---|---|
| crop | c | Crop strategy fit, pad or scale. [Fit](http://image.intervention.io/api/fit) will scale the to fit the height and width but if the ratio does not match it will be cropped to match.
Pad will scale but if the ratio does not match will pad the image. Scale will always keep the ratio and will not pad or crop to make it fit. | scale |
| encode | e | [Encode image format](http://image.intervention.io/api/encode), Set value to *data-url* for encoding image data in data URI scheme (RFC 2397)  |  |
| height | h | Set the desired height in pixels |  |
| width | w | Set the desired width in pixels |  |
| quality | q | Set from 1 to 100, with 100 as the best | 60 |
| src | s | The image path required when used as a snippet |  |


## Examples

### As a snippet

Scale image to fit within a 300x200 box:
```
<img src="[[imageHelper? &src=`[[*myTV]]` &width=`300` &height=`200` &quality=`60`]]" alt="Scale My Image">
```

Pad image to make it exactly 300x200 with no trimming of the image:
```
<img src="[[imageHelper? &src=`[[*myTV]]` &crop=`pad` &width=`300` &height=`200` &quality=`60`]]" alt="Pad My Image">
```

Encode:
```
<img src="[[imageHelper? &src=`[[*myTV]]` &crop=`pad` &encode=`data-url` &width=`300` &height=`200` &quality=`60`]]'" width="300" height="300" alt="Encoded image test">
```

### Output modifier filter 
```
<img src="[[*myTV:imageHelper=`&width=300&height=200&quality=60`]]" alt="My Image">
```
