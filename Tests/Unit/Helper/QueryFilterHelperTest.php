<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Helper;

use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use Doctrine\DBAL\Connection;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\MultiselectType;
use Symfony\Component\Translation\TranslatorInterface;

class QueryFilterHelperTest extends \PHPUnit_Framework_TestCase
{
    private $customFieldTypeProvider;
    private $connection;
    private $queryFilterHelper;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $this->customFieldTypeProvider = $this->createMock(CustomFieldTypeProvider::class);
        $this->connection              = $this->createMock(Connection::class);
        $this->queryFilterHelper       = new QueryFilterHelper($this->customFieldTypeProvider);
    }

    public function testCreateValueQueryBuilderWithKnownFieldType(): void
    {
        $fieldId      = 34;
        $builderAlias = 'builder_alias';
        $fieldType    = 'multiselect';

        $this->customFieldTypeProvider->expects($this->once())
            ->method('getType')
            ->with($fieldType)
            ->willReturn(new MultiselectType($this->createMock(TranslatorInterface::class)));

        $queryBuilder = $this->queryFilterHelper->createValueQueryBuilder(
            $this->connection,
            $builderAlias,
            $fieldId,
            $fieldType
        );

        $expectedSql = 'SELECT * FROM custom_item_xref_contact builder_alias_contact LEFT JOIN custom_item builder_alias_item ON builder_alias_item.id=builder_alias_contact.custom_item_id LEFT JOIN custom_field_value_option builder_alias_value ON builder_alias_value.custom_item_id = builder_alias_item.id WHERE builder_alias_value.custom_field_id = :builder_alias_custom_field_id';

        $this->assertSame($expectedSql, $queryBuilder->getSql());
        $this->assertSame(['builder_alias_custom_field_id' => 34], $queryBuilder->getParameters());
    }
}
