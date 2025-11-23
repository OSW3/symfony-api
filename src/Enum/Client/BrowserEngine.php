<?php 
namespace OSW3\Api\Enum\Client;

use OSW3\Api\Trait\EnumTrait;

enum BrowserEngine: string 
{
    use EnumTrait;
    
    case BLINK   = 'blink';
    case WEBKIT  = 'webkit';
    case GECKO   = 'gecko';
    case TRIDENT = 'trident';
    case PRESTO  = 'presto';
    case UNKNOWN = 'unknown';
} 