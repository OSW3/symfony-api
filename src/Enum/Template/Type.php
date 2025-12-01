<?php 
namespace OSW3\Api\Enum\Template;

use OSW3\Api\Trait\EnumTrait;

enum Type: string 
{
    use EnumTrait;
    
    case LIST      = 'list';
    case SINGLE    = 'single';
    case DELETE    = 'delete';
    case ACCOUNT   = 'account';
    case ERROR     = 'error';
    case NOT_FOUND = 'not_found';
    case LOGIN     = 'login';
} 