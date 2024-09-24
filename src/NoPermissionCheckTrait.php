<?php

declare(strict_types=1);

namespace Lucite\Model;

trait NoPermissionCheckTrait
{
    public function applyPermissionValues(array &$data): void
    {

    }

    public function getPermissionFilter(array &$args): string
    {
        return '';
    }
}
