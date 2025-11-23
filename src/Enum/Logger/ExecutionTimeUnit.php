<?php 
namespace OSW3\Api\Enum\Logger;

use OSW3\Api\Trait\EnumTrait;

enum ExecutionTimeUnit: string 
{
    use EnumTrait;
    
    case SECOND      = 'second';
    case MILLISECOND = 'millisecond';
    case MICROSECOND = 'microsecond';
    case NANOSECOND  = 'nanosecond';
    case FEMTOSECOND  = 'femtosecond';
} 