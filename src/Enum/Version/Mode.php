<?php 
namespace OSW3\Api\Enum\Version;

use OSW3\Api\Trait\EnumTrait;

enum Mode: string 
{
    use EnumTrait;
    
    case AUTO   = 'auto';
    case MANUAL = 'manual';
} 