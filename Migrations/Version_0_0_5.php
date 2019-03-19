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

use MauticPlugin\CustomObjectsBundle\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version_0_0_5 extends AbstractMigration
{
    /**
     * @var string
     */
    private $table = 'custom_field_value_option';

    /**
     * {@inheritdoc}
     */
    protected function isApplicable(Schema $schema): bool
    {
        $table = $schema->getTable($this->concatPrefix($this->table));

        return $table && $table->hasColumn('option_id');
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $this->addSql("ALTER TABLE {$this->concatPrefix($this->table)} 
            CHANGE option_id value VARCHAR(255) NOT NULL");
    }
}
