<?php

namespace Neos\Seo\EelHelper;


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

/**
 * @Flow\Proxy(false)
 */
class ArrayHelper implements ProtectedContextAwareInterface
{

    /**
     * Removes duplicate values from an array
     *
     * @param array $array  The array
     * @param bool  $filter Filter the array defaults to `false`
     * 
     * @return array
     */
    public function unique(array $array, bool $filter = false): array
    {
        if ($filter) {
            $array = array_filter($array);
        }
        return array_unique($array);
    }
    /**
     * All methods are considered safe
     * 
     * @param string $methodName The name of the method
     * 
     * @return bool
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
