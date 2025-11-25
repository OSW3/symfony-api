<?php 
namespace OSW3\Api\Enum;

use OSW3\Api\Trait\EnumTrait;

enum MergeStrategy: string 
{
    use EnumTrait;
    
    case APPEND  = 'append';
    case PREPEND = 'prepend';
    case REPLACE = 'replace';
} 