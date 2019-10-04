<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

return [
    'scan' => [
        'paths' => [
            BASE_PATH . '/app',
            BASE_PATH . '/commune/Hyperf/Preloads/',
            BASE_PATH . '/commune/Hyperf/Commands',
        ],
        'ignore_annotations' => [
            'mixin',
        ],
    ],
];
