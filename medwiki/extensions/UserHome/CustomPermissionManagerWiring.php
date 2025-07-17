<?php
// Wiring file for CustomPermissionManager
return [
    'PermissionManager' => function( $services ) {
        return new CustomPermissionManager();
    },
];
