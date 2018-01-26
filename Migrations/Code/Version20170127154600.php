<?php
namespace Neos\Flow\Core\Migrations;

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
 * Migrate to new namespace
 */
class Version20170127154600 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getIdentifier()
    {
        return 'Neos.Seo-20170127154600';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->moveSettingsPaths('Neos.Neos.Seo', 'Neos.Seo');
        $this->searchAndReplace('Neos.Neos.Seo:', 'Neos.Seo:');
        $this->searchAndReplace('resource://TYPO3.Neos.Seo/', 'resource://Neos.Seo/');
    }
}
