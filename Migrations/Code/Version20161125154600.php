<?php
namespace Neos\Seo\Core\Migrations;

/*
 * This file is part of the Neos.Seo package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Check for globally defined setting identifiers in Settings.yaml files
 */
class Version20161125154600 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'Neos.Seo-20161125154600';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->processConfiguration(\Neos\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            function (&$configuration) {
                if (!isset($configuration['Neos']['Neos']['Seo'])) {
                    $configuration['Neos']['Seo'] = $configuration['Neos']['Neos']['Seo'];
                    unset($configuration['Neos']['Neos']['Seo']);
                }
            }
        );

        $this->searchAndReplace('Neos.Neos.Seo:', 'Neos.Seo:');
        $this->searchAndReplace('resource://TYPO3.Neos.Seo/', 'resource://Neos.Seo/');
    }
}
