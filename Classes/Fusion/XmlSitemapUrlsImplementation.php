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

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Utility\Exception\PropertyNotAccessibleException;

class XmlSitemapUrlsImplementation extends AbstractFusionObject
{
    #[Flow\Inject(lazy: false)]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * @var PersistenceManager
     */
    #[Flow\Inject(lazy: true)]
    protected $persistenceManager;

    /**
     * @var array<string, array<int, string>>
     */
    protected $assetPropertiesByNodeType = [];

    /**
     * @var bool
     */
    protected $renderHiddenInIndex;

    /**
     * @var bool
     */
    protected $includeImageUrls;

    /**
     * @var Node
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
     * @return Node
     */
    public function getStartingPoint(): Node
    {
        if ($this->startingPoint === null) {
            return $this->fusionValue('startingPoint');
        }

        return $this->startingPoint;
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

            $startingPoint = $this->getStartingPoint();
            $subgraph = $this->contentRepositoryRegistry->subgraphForNode($startingPoint);

            $nodeTypeManager = $this->contentRepositoryRegistry->get($startingPoint->subgraphIdentity->contentRepositoryId)->getNodeTypeManager();
            $nodeTypeNames = NodeTypeNames::fromArray(array_map(
                fn(NodeType $nodeType): NodeTypeName => $nodeType->name,
                $nodeTypeManager->getSubNodeTypes('Neos.Neos:Document', false)
            ));

            $subtree = $subgraph->findSubtree(
                $startingPoint->nodeAggregateId,
                FindSubtreeFilter::create(NodeTypeConstraints::create($nodeTypeNames, NodeTypeNames::createEmpty()))
            );

            $this->collectItems($items, $subtree);
            $this->items = $items;
        }

        return $this->items;
    }

    private function getAssetPropertiesForNodeType(NodeType $nodeType): array
    {
        if (!array_key_exists($nodeType->name->value, $this->assetPropertiesByNodeType)) {
            $this->assetPropertiesByNodeType[$nodeType->name->value] = [];
            if ($this->getIncludeImageUrls()) {
                $relevantPropertyTypes = [
                    'array<Neos\Media\Domain\Model\Asset>' => true,
                    'Neos\Media\Domain\Model\Asset' => true,
                    'Neos\Media\Domain\Model\ImageInterface' => true
                ];

                foreach ($nodeType->getProperties() as $propertyName => $propertyConfiguration) {
                    if (isset($relevantPropertyTypes[$nodeType->getPropertyType($propertyName)])) {
                        $this->assetPropertiesByNodeType[$nodeType->name->value][] = $propertyName;
                    }
                }
            }
        }

        return $this->assetPropertiesByNodeType[$nodeType->name->value];
    }

    protected function collectItems(array &$items, Subtree $subtree): void
    {
        $node = $subtree->node;

        if ($this->isDocumentNodeToBeIndexed($node)) {
            $item = [
                'node' => $node,
                'lastModificationDateTime' => $node->timestamps->lastModified,
                'priority' => $node->getProperty('xmlSitemapPriority') ?: '',
                'images' => [],
            ];
            if ($node->getProperty('xmlSitemapChangeFrequency')) {
                $item['changeFrequency'] = $node->getProperty('xmlSitemapChangeFrequency');
            }

            if ($this->getIncludeImageUrls()) {
                $nodeTypeManager = $this->contentRepositoryRegistry->get($node->subgraphIdentity->contentRepositoryId)->getNodeTypeManager();
                $collectionNodeTypeNames = array_map(
                    fn(NodeType $nodeType): NodeTypeName => $nodeType->name,
                    $nodeTypeManager->getSubNodeTypes('Neos.Neos:ContentCollection', false)
                );
                $collectionNodeTypeNames['Neos.Neos:ContentCollection'] = NodeTypeName::fromString('Neos.Neos:ContentCollection');
                $contentNodeTypeNames = array_map(
                    fn(NodeType $nodeType): NodeTypeName => $nodeType->name,
                    $nodeTypeManager->getSubNodeTypes('Neos.Neos:Content', false)
                );
                $nodeTypeNames = NodeTypeNames::fromArray(array_merge($collectionNodeTypeNames, $contentNodeTypeNames));

                $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);
                $contentSubtree = $subgraph->findSubtree(
                    $node->nodeAggregateId,
                    FindSubtreeFilter::create(NodeTypeConstraints::create($nodeTypeNames, NodeTypeNames::createEmpty()))
                );

                $this->resolveImages($contentSubtree, $item);
            }

            $items[] = $item;
        }

        foreach ($subtree->children as $childSubtree) {
            $this->collectItems($items, $childSubtree);
        }
    }

    /**
     * @param Subtree $subtree
     * @param array & $item
     * @return void
     * @throws PropertyNotAccessibleException
     */
    protected function resolveImages(Subtree $subtree, array &$item): void
    {
        $node = $subtree->node;
        $assetPropertiesForNodeType = $this->getAssetPropertiesForNodeType($node->nodeType);

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

        foreach ($subtree->children as $childSubtree) {
            $this->resolveImages($childSubtree, $item);
        }
    }

    /**
     * Return TRUE/FALSE if the node is currently hidden; taking the "renderHiddenInIndex" configuration
     * of the Menu Fusion object into account.
     */
    protected function isDocumentNodeToBeIndexed(Node $node): bool
    {
        return !$node->nodeType->isOfType('Neos.Seo:NoindexMixin')
            && ($this->getRenderHiddenInIndex() || $node->getProperty('hiddenInIndex') !== true)
            && $node->getProperty('metaRobotsNoindex') !== true
            && (
                (string)$node->getProperty('canonicalLink') === ''
                || substr($node->getProperty('canonicalLink'), 7) === $node->nodeAggregateId->value
            );
    }
}
