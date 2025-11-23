<?php 
namespace OSW3\Api\Enum\Client;

use OSW3\Api\Trait\EnumTrait;

enum Device: string 
{
    use EnumTrait;
    
    case MOBILE     = 'mobile';
    case TABLET     = 'tablet';
    case DESKTOP    = 'desktop';
} 