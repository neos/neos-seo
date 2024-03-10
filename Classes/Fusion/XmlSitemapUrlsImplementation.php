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
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Neos\Utility\NodeTypeWithFallbackProvider;
use Neos\Utility\Exception\PropertyNotAccessibleException;

class XmlSitemapUrlsImplementation extends AbstractFusionObject
{
    use NodeTypeWithFallbackProvider;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject(lazy: true)]
    protected PersistenceManager $persistenceManager;

    /**
     * @var array<string, array<int, string>>
     */
    protected array $assetPropertiesByNodeType = [];

    protected ?bool $renderHiddenInMenu = null;

    protected ?bool $includeImageUrls = null;

    protected ?Node $startingPoint = null;

    /**
     * @var array|null
     */
    protected ?array $items = null;

    public function getIncludeImageUrls(): bool
    {
        if ($this->includeImageUrls === null) {
            return $this->fusionValue('includeImageUrls');
        }

        return $this->includeImageUrls;
    }

    public function getRenderHiddenInMenu(): bool
    {
        if ($this->renderHiddenInMenu === null) {
            $this->renderHiddenInMenu = (boolean)$this->fusionValue('renderHiddenInMenu');
        }

        return $this->renderHiddenInMenu;
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
     * @throws PropertyNotAccessibleException
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
                FindSubtreeFilter::create(nodeTypes: NodeTypeCriteria::create($nodeTypeNames, NodeTypeNames::createEmpty()))
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

    /**
     * @throws PropertyNotAccessibleException
     */
    protected function collectItems(array &$items, Subtree $subtree): void
    {
        $node = $subtree->node;

        if ($this->isDocumentNodeToBeIndexed($node)) {
            $item = [
                'node' => $node,
                'lastModificationDateTime' => $node->timestamps->lastModified ?: $node->timestamps->created,
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
                    FindSubtreeFilter::create(nodeTypes: NodeTypeCriteria::create($nodeTypeNames, NodeTypeNames::createEmpty()))
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
        $assetPropertiesForNodeType = $this->getAssetPropertiesForNodeType($this->getNodeType($node));

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
     * Return TRUE/FALSE if the node is currently hidden; taking the "renderHiddenInMenu" configuration
     * of the Menu Fusion object into account.
     */
    protected function isDocumentNodeToBeIndexed(Node $node): bool
    {
        return !$this->getNodeType($node)->isOfType('Neos.Seo:NoindexMixin')
            && ($this->getRenderHiddenInMenu() || $node->getProperty('hiddenInMenu') !== true)
            && $node->getProperty('metaRobotsNoindex') !== true
            && (
                (string)$node->getProperty('canonicalLink') === ''
                || substr($node->getProperty('canonicalLink'), 7) === $node->nodeAggregateId->value
            );
    }
}
