<?php

return [
    'debug' => true,
    'configBindings' => [
        \Commune\Hyperf\Servers\Tinker\TinkerOption::class,
    ],
    'components' => [
        \Commune\Demo\App\DemoOption::class,
    ],
    'processProviders' => [
        // 基础service
        \Commune\Studio\Providers\ProcessServiceProvider::class,
    ],
    'conversationProviders' => [
        // 权限识别
        \Commune\Studio\Providers\AbilityServiceProvider::class,
        \Commune\Chatbot\App\Drivers\Demo\CacheServiceProvider::class,
        \Commune\Chatbot\App\Drivers\Demo\SessionServiceProvider::class,
    ],
    'chatbotPipes' =>
        [
            'onUserMessage' => [
                \Commune\Chatbot\App\ChatPipe\MessengerPipe::class,
                \Commune\Chatbot\App\ChatPipe\ChattingPipe::class,
                \Commune\Chatbot\OOHost\OOHostPipe::class,
            ],
        ],
    'translation' =>
        [
            'loader' => 'php',
            'resourcesPath' => COMMUNE_PATH . '/langs',
            'defaultLocale' => 'zh',
            'cacheDir' => NULL,
        ],
    'logger' =>
        [
            'name' => 'chatbot',
            'path' => BASE_PATH . '/runtime/tinker.log',
            'days' => 0,
            'level' => 'debug',
            'bubble' => true,
            'permission' => NULL,
            'locking' => false,
        ],
    'defaultMessages' =>
        [
            'platformNotAvailable' => 'system.platformNotAvailable',
            'chatIsTooBusy' => 'system.chatIsTooBusy',
            'systemError' => 'system.systemError',
            'farewell' => 'dialog.farewell',
            'messageMissMatched' => 'dialog.missMatched',
        ],
    'eventRegister' =>[

    ],

    'host' => [
            'rootContextName' => \Commune\Demo\App\Contexts\TestCase::class,
            'navigatorIntents' => [
                \Commune\Chatbot\App\Components\Predefined\Navigation\BackwardInt::class,
                \Commune\Chatbot\App\Components\Predefined\Navigation\QuitInt::class,
                \Commune\Chatbot\App\Components\Predefined\Navigation\CancelInt::class,
                \Commune\Chatbot\App\Components\Predefined\Navigation\RepeatInt::class,
                \Commune\Chatbot\App\Components\Predefined\Navigation\RestartInt::class,
            ],
            'sessionPipes' => [
                \Commune\Chatbot\App\SessionPipe\DefaultReplyPipe::class,
                \Commune\Chatbot\App\SessionPipe\EventMsgPipe::class,
                \Commune\Chatbot\App\Commands\UserCommandsPipe::class,
                \Commune\Chatbot\App\Commands\AnalyserPipe::class,
                \Commune\Chatbot\App\SessionPipe\MarkedIntentPipe::class,
                \Commune\Chatbot\App\SessionPipe\NavigationPipe::class,
            ],
            'hearingFallback' => null,
        ] + \Commune\Chatbot\Config\Host\OOHostConfig::stub(),

];
