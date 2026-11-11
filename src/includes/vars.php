<?php declare(strict_types=1);

class MTTVars {
    static array $info = [];
    static string $requestedUsername = '';  # the user who owns the requested lists (own lists or public lists of another user)
    static int $requestedUserId = 0;        # id of $requestedUsername
    static bool $isSessionInvalid;          # readonly
    static bool $isStateless;               # readonly
    static string $userPwToken; //TODO: check if not used
    static string $username;                # logged-in username
    static string $user;                    # human title of logged-in user
    static int $userId = 0;                 # id of logged-in user
    static string $settingsPage;
    static string $settingsPageFile;
    static string $settingsPageArgument;
    static bool $isRtl = false;
    static ?string $forcedLang = null;
    static string $mailerLastError = '';
}


class MTTVersion
{
    const VERSION = '2.0-dev';
    const DB_VERSION = '2.0';
}
