<?php declare(strict_types = 1);

namespace Apitin;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(string $route, array $methods, string $name = null)
    {
        
    }
}