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
            COMMUNE_PATH . '/Hyperf/Preloads',
            COMMUNE_PATH . '/Hyperf/Commands',
        ],
        'ignore_annotations' => [
            'mixin',
        ],
    ],
];
