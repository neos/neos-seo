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

use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\ComponentImplementation;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

class AlternativeLanguageLinksImplementation extends ComponentImplementation
{
    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contentContextFactory;

    public function getLanguageVariantsForRelAlternate(): array
    {
        $documentNode = $this->getNode();
        if (!$documentNode) {
            return [];
        }

        $languageDimensionName = $this->getLanguageDimensionName();
        if (!$languageDimensionName) {
            return [];
        }

        $languageConfiguration = $this->contentDimensionPresetSource->getAllPresets()[$languageDimensionName] ?? [];
        if (empty($languageConfiguration)) {
            return [];
        }
        $currentContextProperties = $documentNode->getContext()->getProperties();
        $excludedPresets = $this->getExcludedPresets();
        $languageVariants = [];
        foreach ($languageConfiguration['presets'] as $language => $preset) {
            if (in_array($language, $excludedPresets)) {
                continue;
            }
            $variantContextProperties = $currentContextProperties;
            $variantContextProperties['dimensions'][$languageDimensionName] = $languageConfiguration['presets'][$language]['values'];
            $variantContextProperties['targetDimensions'][$languageDimensionName] = reset($languageConfiguration['presets'][$language]['values']);
            $variantContext = $this->contentContextFactory->create($variantContextProperties);

            $documentVariant = $variantContext->getNodeByIdentifier($documentNode->getIdentifier());
            if ($documentVariant && $this->isDocumentNodeToBeIndexed($documentVariant) && !$this->hasDocumentCustomCanonicalLink($documentVariant)) {
                $languageVariants[] = [
                    'node' => $documentVariant,
                    'language' => $language
                ];
                if ($language === $languageConfiguration['default']) {
                    $languageVariants[] = [
                        'node' => $documentVariant,
                        'language' => 'x-default'
                    ];
                }
            }
        }

        return $languageVariants;
    }

    public function getNode(): ?Node
    {
        return $this->fusionValue('node') ?? null;
    }

    public function getDoesLanguageDimensionExist(): bool
    {
        return isset($this->contentDimensionPresetSource->getAllPresets()[$this->getLanguageDimensionName()]);
    }

    public function getExcludedPresets(): array
    {
        return $this->fusionValue('excludedPresets') ?? [];
    }

    public function getLanguageDimensionName(): ?string
    {
        return $this->fusionValue('dimension');
    }

    public function isDocumentNodeToBeIndexed(NodeInterface $documentNode): bool
    {
        return !$documentNode->getProperty('metaRobotsNoindex') && !$documentNode->getNodeType()->isOfType('Neos.Seo:NoindexMixin');
    }

    public function hasDocumentCustomCanonicalLink(NodeInterface $documentNode): bool
    {
        return !empty($documentNode->getProperty('canonicalLink'));
    }
}
