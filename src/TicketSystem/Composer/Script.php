<?php

namespace TicketSystem\Composer;

class Script
{
    public static function install()
    {
        chmod('resources/cache', 0777);
        chmod('resources/log', 0777);
        chmod('public/assets', 0777);
        chmod('data', 0777);
        exec('php console assetic:dump');
    }
}
