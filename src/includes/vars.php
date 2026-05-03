<?php

class MTTVars {
    static string $requestedUsername = '';
    static int $requestedUserId = 0;
    static string $userPwToken;
    static string $settingsPage;
    static string $settingsPageFile;
    static bool $isRtl = false;
    static ?string $forcedLang = null;
}


class MTTVersion
{
    const VERSION = '2.0';
    const DB_VERSION = '2.0';
}
