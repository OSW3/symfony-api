<?php 
namespace OSW3\Api\Enum\Template;

use OSW3\Api\Trait\EnumTrait;

enum Type: string 
{
    use EnumTrait;
    
    // -- Core templates --
    // success.yaml
    case ERROR     = 'error';
    // validation_error.yaml
    // unauthorized.yaml
    // forbidden.yaml
    case NOT_FOUND = 'not_found';
    // empty.yaml
    // created.yaml
    // updated.yaml
    // delete.yaml
    // pagination.yaml
    // meta.yaml
    
    // -- Auth templates --
    case LOGIN     = 'login';
    // logout.yaml
    // refresh_token.yaml
    // register.yaml
    // me.yaml
    case ACCOUNT   = 'account';
    // update_password.yaml
    // reset_password.yaml
    
    // -- Entities templates --
    case LIST      = 'list';
    case SINGLE    = 'single';
    // create.yaml
    // update.yaml
    case DELETE    = 'delete';
    
    // -- System templates --
    // health.yaml
    // maintenance.yaml
    // rate_limit.yaml
    // diagnostics.yaml
    // version.yaml
    
    // -- Files templates --
    // upload.yaml
    // download.yaml
    
    // -- Bulk templates --
    // bulk_action.yaml
    // bulk_update.yaml
    // bulk_delete.yaml
} 