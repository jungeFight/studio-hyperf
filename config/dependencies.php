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
    'dependencies' => [
        \Commune\Hyperf\Foundations\Dependencies\HyperfBotOption::class
            => \Commune\Hyperf\Foundations\Dependencies\HyperfBotOptionFactory::class,

        \Commune\Chatbot\Blueprint\Application::class
            => \Commune\Hyperf\Foundations\ChatAppFactory::class
    ],
];
