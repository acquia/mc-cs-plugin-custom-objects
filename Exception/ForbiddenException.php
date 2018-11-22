<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 *
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Exception;

use Exception;
use Throwable;
use MauticPlugin\CustomObjectsBundle\Entity\UniqueEntityInterface;

class ForbiddenException extends Exception
{
    /**
     * @param string                     $permission
     * @param UniqueEntityInterface|null $entity
     * @param int                        $code
     * @param Throwable|null             $previous
     */
    public function __construct(
        string $permission,
        ?UniqueEntityInterface $entity = null,
        int $code = 403,
        ?Throwable $throwable = null
    ) {
        $message = "You do not have permission to {$permission}";
        
        if ($entity) {
            $message .= " item with ID {$entity->getId()}";
        }
        
        parent::__construct($message, $code, $throwable);
    }
}
