<?php

return [
    // Pannello tecnico (/tecnico). Se user o password sono vuoti il pannello è disabilitato (404).
    'panel_user' => env('TECH_PANEL_USER', ''),
    'panel_password' => env('TECH_PANEL_PASSWORD', ''),
];
