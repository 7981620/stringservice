<?php

namespace Agenta\StringService;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Agenta\StringService\Skeleton\SkeletonClass
 */
class StringServiceFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'stringservice';
    }
}
