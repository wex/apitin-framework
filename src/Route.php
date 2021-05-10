<?php declare(strict_types = 1);

namespace Apitin;

use Attribute;

#[Attribute]
class Route
{
    public function __construct(string $route, array|string $methods)
    {
        
    }
}