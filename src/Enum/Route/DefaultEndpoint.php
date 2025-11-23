<?php 
namespace OSW3\Api\Enum\Route;

use OSW3\Api\Trait\EnumTrait;

enum DefaultEndpoint: string 
{
    use EnumTrait;
    
    case INDEX   = 'index';
    case LIST   = 'list';
    case SHOW   = 'show';
    case ADD   = 'add';
    case CREATE   = 'create';
    case POST   = 'post';
    case EDIT   = 'edit';
    case DELETE = 'delete';
    case PATCH  = 'patch';
    case PUT    = 'put';
    case READ   = 'read';
    case UPDATE = 'update';
} 