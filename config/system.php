<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Role Names
    |--------------------------------------------------------------------------
    |
    | Role names that automatically bypass granular permission checks, both
    | on the backend (EnsureUserHasPermission middleware) and the frontend
    | (src/lib/permissions.ts ADMIN_ROLE_NAMES). Keep both lists in sync.
    |
    */

    'admin_role_names' => ['Administrator', 'System Administrator', 'pageistrator'],

];
