<?php

namespace Neos\Seo\Fusion;

/*
 * This file is part of the Neos.Seo package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Exception\NodeException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

class XmlSitemapUrlsImplementation extends AbstractFusionObject
{
    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @var array
     */
    protected $assetPropertiesByNodeType;

    /**
     * @var bool
     */
    protected $renderHiddenInIndex;

    /**
     * @var bool
     */
    protected $includeImageUrls;

    /**
     * @var NodeInterface
     */
    protected $startingPoint;

    /**
     * @var array
     */
    protected $items;

    /**
     * @return bool
     */
    public function getIncludeImageUrls(): bool
    {
        if ($this->includeImageUrls === null) {
            return $this->fusionValue('includeImageUrls');
        }

        return $this->includeImageUrls;
    }

    /**
     * @return bool
     */
    public function getRenderHiddenInIndex(): bool
    {
        if ($this->renderHiddenInIndex === null) {
            $this->renderHiddenInIndex = (boolean)$this->fusionValue('renderHiddenInIndex');
        }

        return $this->renderHiddenInIndex;
    }

    /**
     * @return NodeInterface
     */
    public function getStartingPoint(): NodeInterface
    {
        if ($this->startingPoint === null) {
            return $this->fusionValue('startingPoint');
        }

        return $this->startingPoint;
    }

    /**
     * @return void
     */
    public function initializeObject()
    {
        if ($this->getIncludeImageUrls()) {
            $relevantPropertyTypes = [
                'array<Neos\Media\Domain\Model\Asset>' => true,
                'Neos\Media\Domain\Model\Asset' => true,
                'Neos\Media\Domain\Model\ImageInterface' => true
            ];

            foreach ($this->nodeTypeManager->getNodeTypes(false) as $nodeType) {
                /** @var NodeType $nodeType */
                foreach ($nodeType->getProperties() as $propertyName => $propertyConfiguration) {
                    if (isset($relevantPropertyTypes[$nodeType->getPropertyType($propertyName)])) {
                        $this->assetPropertiesByNodeType[$nodeType->getName()][] = $propertyName;
                    }
                }
            }
        }
    }

    /**
     * @param array & $items
     * @param NodeInterface $node
     * @return void
     * @throws NodeException
     */
    protected function appendItems(array &$items, NodeInterface $node)
    {
        if ($this->isDocumentNodeToBeIndexed($node)) {
            $item = [
                'node' => $node,
                'lastModificationDateTime' => $node->getNodeData()->getLastModificationDateTime(),
                'priority' => $node->getProperty('xmlSitemapPriority') ?: '',
                'images' => [],
            ];
            if ($node->getProperty('xmlSitemapChangeFrequency')) {
                $item['changeFrequency'] = $node->getProperty('xmlSitemapChangeFrequency');
            }
            if ($this->getIncludeImageUrls()) {
                $this->resolveImages($node, $item);
            }
            $items[] = $item;
        }
        foreach ($node->getChildNodes('Neos.Neos:Document') as $childDocumentNode) {
            $this->appendItems($items, $childDocumentNode);
        }
    }

    /**
     * @param NodeInterface $node
     * @param array & $item
     * @return void
     * @throws NodeException
     */
    protected function resolveImages(NodeInterface $node, array &$item)
    {
        if (isset($this->assetPropertiesByNodeType[$node->getNodeType()->getName()]) && !empty($this->assetPropertiesByNodeType[$node->getNodeType()->getName()])) {

            foreach ($this->assetPropertiesByNodeType[$node->getNodeType()->getName()] as $propertyName) {
                if (is_array($node->getProperty($propertyName)) && !empty($node->getProperty($propertyName))) {
                    foreach ($node->getProperty($propertyName) as $asset) {
                        if ($asset instanceof ImageInterface) {
                            $item['images'][$this->persistenceManager->getIdentifierByObject($asset)] = $asset;
                        }
                    }
                } elseif ($node->getProperty($propertyName) instanceof ImageInterface) {
                    $item['images'][$this->persistenceManager->getIdentifierByObject($node->getProperty($propertyName))] = $node->getProperty($propertyName);
                }
            }
        }

        foreach ($node->getChildNodes('Neos.Neos:ContentCollection,Neos.Neos:Content') as $childNode) {
            $this->resolveImages($childNode, $item);
        }
    }

    /**
     * Return TRUE/FALSE if the node is currently hidden; taking the "renderHiddenInIndex" configuration
     * of the Menu Fusion object into account.
     *
     * @param NodeInterface $node
     * @return bool
     * @throws NodeException
     */
    protected function isDocumentNodeToBeIndexed(NodeInterface $node): bool
    {
        return !$node->getNodeType()->isOfType('Neos.Seo:NoindexMixin') && $node->isVisible()
            && ($this->getRenderHiddenInIndex() || !$node->isHiddenInIndex()) && $node->isAccessible()
            && $node->getProperty('metaRobotsNoindex') !== true;
    }

    /**
     * Evaluate this Fusion object and return the result
     *
     * @return array
     */
    public function evaluate(): array
    {
        if ($this->items === null) {
            $items = [];

            try {
                $this->appendItems($items, $this->getStartingPoint());
            } catch (NodeException $e) {
            }
            $this->items = $items;
        }

        return $this->items;
    }
}
