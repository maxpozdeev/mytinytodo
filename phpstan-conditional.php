<?php declare(strict_types = 1);

# All paths should be absolute

$config = [];

//if (PHP_VERSION_ID < 80500) {
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#Call to method \S+ on an unknown class Pdo\\\S+.#',
        'path' => __DIR__. '/src/includes/class.db.*',
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#Instantiated class Pdo\\S+ not found.#',
        'path' => __DIR__. '/src/includes/class.db.*',
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#Property \S+ has unknown class Pdo\\S+ as its type.#',
        'path' => __DIR__. '/src/includes/class.db.*',
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#Access to constant \S+ on an unknown class Pdo\\S+.#',
        'path' => __DIR__. '/src/includes/class.db.*',
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#Property .+ does not accept Pdo\\S+.#',
        'path' => __DIR__. '/src/includes/class.db.*',
    ];
//}

//if (PHP_VERSION_ID >= 80500) {
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#Call to deprecated method \S+ of class PDO.#',
        'path' => __DIR__. '/src/includes/class.db.*',
        'reportUnmatched' => false,
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#Fetching deprecated class constant \S+ of class PDO.#',
        'path' => __DIR__. '/src/includes/class.db.*',
        'reportUnmatched' => false,
    ];
//}

return $config;
