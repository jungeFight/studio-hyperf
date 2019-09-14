<?php



$chatbot = include __DIR__ . '/demo.php';

// 替换掉系统默认的render

$chatbot['chatbotName'] = 'dueros_maze';
$chatbot['conversationProviders']['render'] = \Commune\DuerOS\Providers\RenderServiceProvider::class;

$chatbot['logger']['path'] = BASE_PATH . '/runtime/logs/dueros_maze.log';
$chatbot['host']['rootContextName'] = \Commune\Demo\App\Cases\Maze\MazeInt::class;

$chatbot['components'] = array_merge($chatbot['components'], [
    \Commune\DuerOS\DuerOSComponent::class => [
        'name' => '方向迷宫',
    ],
]);

/**
 * 默认的迷宫demo
 */
return $chatbot;

