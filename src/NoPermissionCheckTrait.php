<?php

declare(strict_types=1);

namespace Lucite\Model;

trait NoPermissionCheckTrait
{
    /**
     * Apply permissions to values used for creating/updating a resource
     * @param array $data
     * @return void
     */
    public function applyPermissionValues(array &$data): void
    {

    }

    /**
     * Get sql filter that applies permissions and update placeholder args
     * @param array $args
     * @return string
     */
    public function getPermissionFilter(array &$args): string
    {
        return '';
    }
}
