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

use Mautic\CoreBundle\Exception\SchemaException;
use MauticPlugin\CustomObjectsBundle\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version_0_0_9 extends AbstractMigration
{
    /**
     * @var string
     */
    private $tableObject = 'custom_object';

    /**
     * @var string
     */
    private $tableField = 'custom_field';

    /**
     * {@inheritdoc}
     */
    protected function isApplicable(Schema $schema): bool
    {
        try {
            return !$schema->getTable($this->concatPrefix($this->tableObject))->hasColumn('alias');
        } catch (SchemaException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $this->addSql("ALTER TABLE {$this->concatPrefix($this->tableObject)} ADD alias VARCHAR(255) NOT NULL, ADD INDEX (alias)");

        $this->addSql("ALTER TABLE {$this->concatPrefix($this->tableField)} ADD alias VARCHAR(255) NOT NULL, ADD INDEX (alias)");
    }
}
