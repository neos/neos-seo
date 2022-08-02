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

use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraintParser;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Media\Domain\Model\ImageInterface;

class XmlSitemapUrlsImplementation extends AbstractFusionObject
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * @Flow\Inject
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @var array
     */
    protected $assetPropertiesByNodeType = null;

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

    private function getAssetPropertiesForNodeType(NodeType $nodeType): array
    {
        if ($this->assetPropertiesByNodeType[$nodeType->getName()] === null) {
            $this->assetPropertiesByNodeType[$nodeType->getName()] = [];
            if ($this->getIncludeImageUrls()) {
                $relevantPropertyTypes = [
                    'array<Neos\Media\Domain\Model\Asset>' => true,
                    'Neos\Media\Domain\Model\Asset' => true,
                    'Neos\Media\Domain\Model\ImageInterface' => true
                ];

                foreach ($nodeType->getProperties() as $propertyName => $propertyConfiguration) {
                    if (isset($relevantPropertyTypes[$nodeType->getPropertyType($propertyName)])) {
                        $this->assetPropertiesByNodeType[$nodeType->getName()][] = $propertyName;
                    }
                }
            }
        }

        return $this->assetPropertiesByNodeType[$nodeType->getName()];
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
                //'lastModificationDateTime' => $node->getNodeData()->getLastModificationDateTime(),
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
        $assetPropertiesForNodeType = $this->getAssetPropertiesForNodeType($node->getNodeType());

        foreach ($assetPropertiesForNodeType as $propertyName) {
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

        $contentRepository = $this->contentRepositoryRegistry->get($node->getSubgraphIdentity()->contentRepositoryIdentifier);
        $nodeTypeConstraintParser = NodeTypeConstraintParser::create($contentRepository->getNodeTypeManager());

        $nodeAccessor = $this->nodeAccessorManager->accessorFor($node->getSubgraphIdentity());
        $childNodes = $nodeAccessor->findChildNodes(
            $node,
            $nodeTypeConstraintParser->parseFilterString('Neos.Neos:ContentCollection,Neos.Neos:Content')
        );

        foreach ($childNodes as $childNode) {
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
        return !$node->getNodeType()->isOfType('Neos.Seo:NoindexMixin')// TODO?? && $node->isVisible()
            && ($this->getRenderHiddenInIndex())// TODO?? || !$node->isHiddenInIndex()) && $node->isAccessible()
            && $node->getProperty('metaRobotsNoindex') !== true
            && ((string)$node->getProperty('canonicalLink') === '' || substr($node->getProperty('canonicalLink'), 7) === $node->getNodeAggregateIdentifier()->getValue());
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
