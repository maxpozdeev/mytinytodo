<?php declare(strict_types=1);

class MTTVars {
    static string $requestedUsername = '';
    static int $requestedUserId = 0;
    static bool $isSessionInvalid; //readonly
    static string $userPwToken; //seems not used
    static string $username;
    static string $user;
    static string $settingsPage;
    static string $settingsPageFile;
    static bool $isRtl = false;
    static ?string $forcedLang = null;
    static string $mailerLastError = '';
}


class MTTVersion
{
    const VERSION = '2.0';
    const DB_VERSION = '2.0';
}
