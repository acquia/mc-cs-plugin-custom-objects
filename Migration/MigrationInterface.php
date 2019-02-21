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

namespace MauticPlugin\CustomObjectsBundle\Migration;

interface MigrationInterface
{
    /**
     * @return bool
     */
    public function isApplicable(): bool;

    public function up(): void;

    public function execute(): void;
}