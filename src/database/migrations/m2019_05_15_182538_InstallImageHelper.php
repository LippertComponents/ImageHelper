<?php

/**
 * Auto Generated from Blender
 * Date: 2019/05/15 at 18:25:38 UTC +00:00
 */

use LCI\Blend\Helpers\ElementProperty;
use \LCI\Blend\Migrations;

class m2019_05_15_182538_InstallImageHelper extends Migrations
{
    protected $xpdo_classes = [
        'ImageHelperImages'
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->blendSnippets();

        $this->modx->addPackage('imageHelper', dirname(dirname(__DIR__)). '/model/');

        // DB model:
        // Create tables:
        $xPDOManager = $this->modx->getManager();

        foreach ($this->xpdo_classes as $class_name) {
            if ($xPDOManager->createObjectContainer($class_name)) {
                $this->blender->outSuccess('Created the xPDO class table: '.$class_name);
            }
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->blendSnippets('revert');

        $this->modx->addPackage('imageHelper', dirname(dirname(__DIR__)). '/model/');

        $xPDOManager = $this->modx->getManager();

        foreach ($this->xpdo_classes as $class_name) {
            if ($xPDOManager->removeObjectContainer($class_name)) {
                $this->blender->outSuccess('Removed the xPDO class table: '.$class_name);
            }
        }

    }

    protected function blendSnippets($method='blend')
    {
        $snippet_name = 'imageHelper';

        /** @var \LCI\Blend\Blendable\Snippet $blendableSnippet */
        $blendableSnippet = $this->blender->getBlendableLoader()->getBlendableSnippet($snippet_name);

        $blendableSnippet
            ->setSeedsDir($this->getSeedsDir())
            ->setFieldDescription('ImageHelper allows you to quickly resize your web page images')
            ->setAsStatic('lci/modx-image-helper/src/elements/snippets/'.$snippet_name.'.php', 'orchestrator')
            ->setFieldCategory('LCI=>Image Helper')
            ->setElementProperty(
                (new ElementProperty('crop'))
                    ->setArea('Image')
                    ->setDescription('Crop strategy fit, pad or scale. Fit will scale the to fit the height and width but if the ratio does not match it will be cropped to match.
Pad will scale but if the ratio does not match will pad the image. Scale will always keep the ratio and will not pad or crop to make it fit.')
                    //->setLexicon()
                    ->setOptions([
                        ['text' => 'scale', 'value' => 'scale'],
                        ['text' => 'fit', 'value' => 'fit'],
                        ['text' => 'pad', 'value' => 'pad']
                    ])
                    ->setType('list')
                    ->setValue('scale')
                )
            ->setElementProperty(
                (new ElementProperty('encode'))
                    ->setArea('Image')
                    ->setDescription('Encode image format, see: http://image.intervention.io/api/encode, set value to data-url for encoding image data in data URI scheme (RFC 2397)')
                    //->setLexicon()
                    ->setType('textfield')
                    ->setValue('')
            )
            ->setElementProperty(
                (new ElementProperty('height'))
                    ->setArea('Image')
                    ->setDescription('Set the desired height in pixels')
                    //->setLexicon()
                    ->setType('numberfield')
                    ->setValue('')
            )
            ->setElementProperty(
                (new ElementProperty('width'))
                    ->setArea('Image')
                    ->setDescription('Set the desired width in pixels')
                    //->setLexicon()
                    ->setType('numberfield')
                    ->setValue('')
            )
            ->setElementProperty(
                (new ElementProperty('quality'))
                    ->setArea('Image')
                    ->setDescription('Set from 1 to 100, with 100 as the best')
                    //->setLexicon()
                    ->setType('numberfield')
                    ->setValue(60)
            );

        if ($method=='revert') {
            if ($blendableSnippet->delete()) {
                $this->blender->out($snippet_name.' snippet was deleted successfully!');
            } else {
                $this->blender->outError($snippet_name.' snippet was not deleted successfully!');
            }

        } else {
            if ($blendableSnippet->blend(true)) {
                $this->blender->out($snippet_name . ' snippet was created successfully!');
            } else {
                $this->blender->outError($snippet_name . ' snippet was not created successfully!');
            }
        }

        return $this;
    }

    /**
     * Method is called on construct, please fill me in
     */
    protected function assignDescription()
    {
        $this->description = 'Install ImageHelper package';
    }

    /**
     * Method is called on construct, please fill me in
     */
    protected function assignVersion()
    {

    }

    /**
     * Method is called on construct, can change to only run this migration for those types
     */
    protected function assignType()
    {
        $this->type = 'master';
    }

    /**
     * Method is called on construct, Child class can override and implement this
     */
    protected function assignSeedsDir()
    {
        $this->seeds_dir = 'm2019_05_15_182538_InstallImageHelper';
    }
}