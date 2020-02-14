<?php

declare(strict_types=1);

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

/**
 * Delete this migration for next version as it should only delete fields that were hidden.
 * It does not need to run every time.
 */
class Version_0_0_10 extends AbstractMigration
{
    /**
     * {@inheritdoc}
     */
    protected function isApplicable(Schema $schema): bool
    {
        // There is no way how to know without the FieldTypeProvider.
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $this->addSql("DELETE FROM {$this->concatPrefix('custom_field')} WHERE type = 'checkbox_group' OR type = 'radio_group'");
    }
}
