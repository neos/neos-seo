<?php
namespace Neos\Seo\Fusion\Helper;

/*
 * This file is part of the Neos.Seo package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Service\ThumbnailService;
use Neos\Media\Exception\ThumbnailServiceException;

class ImageHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var ThumbnailService
     */
    protected $thumbnailService;

    /**
     * @param AssetInterface $asset
     * @param string $preset Name of the preset that should be used as basis for the configuration
     * @param integer $width Desired width of the image
     * @param integer $maximumWidth Desired maximum width of the image
     * @param integer $height Desired height of the image
     * @param integer $maximumHeight Desired maximum height of the image
     * @param boolean $allowCropping Whether the image should be cropped if the given sizes would hurt the aspect ratio
     * @param boolean $allowUpScaling Whether the resulting image size might exceed the size of the original image
     * @param boolean $async Whether the thumbnail can be generated asynchronously
     * @param integer $quality Quality of the processed image
     * @param string $format Format for the image, only jpg, jpeg, gif, png, wbmp, xbm, webp and bmp are supported.
     * @return null|ImageInterface
     * @throws ThumbnailServiceException
     */
    public function createThumbnail(
        AssetInterface $asset,
        $preset = null,
        $width = null,
        $maximumWidth = null,
        $height = null,
        $maximumHeight = null,
        $allowCropping = false,
        $allowUpScaling = false,
        $async = false,
        $quality = null,
        $format = null
    )
    {
        if (!empty($preset)) {
            $thumbnailConfiguration = $this->thumbnailService->getThumbnailConfigurationForPreset($preset);
        } else {
            $thumbnailConfiguration = new ThumbnailConfiguration(
                $width,
                $maximumWidth,
                $height,
                $maximumHeight,
                $allowCropping,
                $allowUpScaling,
                $async,
                $quality,
                $format
            );
        }
        $thumbnailImage = $this->thumbnailService->getThumbnail($asset, $thumbnailConfiguration);
        if (!$thumbnailImage instanceof ImageInterface) {
            return null;
        }
        return $thumbnailImage;
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
