<?php

use Commune\Studio\Providers;
use Commune\Studio\SessionPipes;

/**
 * 机器人的默认配置.
 *
 * @see \Commune\Chatbot\Config\ChatbotConfig
 */
$chatbot = [

    // 系统用 chatbotName 来隔离会话. 必须要填.
    'chatbotName' => 'commune-studio-demo',

    // 会被 botOption 的 debug 覆盖.
    'debug' => env('CHATBOT_DEBUG', false),

    'server' => \Commune\Hyperf\Foundations\HyperfChatServer::class,

    // 在这里可以预先绑定一些用 Option 类封装的配置.
    // 会将该配置预绑定到worker容器上, 作为单例.
    // 有三种绑定方式:
    // 1. 只写类名, 默认使用 stub 里的配置.
    // 2. 类名 => 数组,  会用数组内的值覆盖 stub 的相关参数.
    // 3. 类名 => 子类名, 会用子类的实例来绑定父类类名.
    'configBindings' => [
    ],


    // 预加载的组件. 使用方法类似 configBindings
    // 但component 不仅会预加载配置, 而且还能注册各种组件, 进行初始化等.
    'components' => include __DIR__ .'/../configs/components.php',

    // 系统默认的服务注册.
    'baseServices' => \Commune\Chatbot\Config\Children\BaseServicesConfig::stub(),

    // 进程级别的服务注册
    'processProviders' => [
        // 注册 feel emotion 模块
        'feeling' => Providers\FeelingServiceProvider::class,
        // register chatbot event
        'event' => Providers\EventServiceProvider::class,
        // 公共的rendering
        'render' =>  Providers\RenderServiceProvider::class,
        // 权限识别
        'ability' => Providers\AbilityServiceProvider::class,
    ],

    // 在worker中注册的服务, 多个请求共享
    'conversationProviders' => [
        // hyperf client driver . redis, db
        // hyperf 的协程客户端
        'client' => \Commune\Hyperf\Foundations\Providers\ClientDriverServiceProvider::class,
        // cache adapter driver
        // 实现 chatbot 需要的 cache adapter
        'cache' => \Commune\Hyperf\Foundations\Providers\CacheServiceProvider::class,
        // oo host session driver
        'session' => \Commune\Hyperf\Foundations\Providers\SessionServiceProvider::class,
        // message request service
        'message' => \Commune\Hyperf\Foundations\Providers\MessageQueueServiceProvider::class,
    ],

    'chatbotPipes' =>
        [
            'onUserMessage' => [
                // 发送 conversation, 并且管理所有的异常
                \Commune\Chatbot\App\ChatPipe\UserMessengerPipe::class,
                // 用于锁定 chat, 避免用户输入消息太频繁导致歧义
                \Commune\Chatbot\App\ChatPipe\ChattingPipe::class,
                // 多轮对话管理内核
                \Commune\Chatbot\OOHost\OOHostPipe::class,
            ],
        ],

    // translation module
    'translation' => [
        'loader' => 'php',
        'resourcesPath' => BASE_PATH . '/resources/langs',
        'defaultLocale' => 'zh',
        'cacheDir' => NULL,
    ],

    // logger module
    'logger' => [
        'path' => BASE_PATH . '/runtime/commune_demo.log',
        'days' => 7,
        'level' => 'debug',
        'bubble' => true,
        'permission' => NULL,
        'locking' => false,
    ],

    // 系统默认的slots, 所有的reply message 都会使用
    // 多维数组会被抹平
    //  例如 self => [ 'name' => 'a'],  会变成 self_name 这样的形式
    // default reply slots
    // multi-dimension array will be flatten to dot pattern
    // such as 'self.name'
    'defaultSlots' => include __DIR__ . '/../configs/slots.php',

    'defaultMessages' => \Commune\Chatbot\Config\Children\DefaultMessagesConfig::stub(),

    'host' => [


        // 默认的根语境名
        'rootContextName' => \Commune\Hyperf\Demo\Contexts\DemoHome::class,

        // 不同场景下的根语境名.
        'sceneContextNames' => [
            'introduce' => 'sw.demo.intro',
            'special' => 'sw.demo.intro.special',
            'game' => \Commune\Components\Demo\Contexts\GameTestCases::class,
            'unheard' => 'unheard-like.episodes.who-is-lizhongwen',
            'story' => 'story.examples.sanguo.changbanpo',
            'nlu' => \Commune\Components\Demo\Contexts\NLTestCases::class,
            'maze' => \Commune\Components\Demo\Cases\Maze\MazeInt::getContextName(),
            'dev' => \Commune\Hyperf\Demo\Contexts\DevTools::class,
        ],

        'sessionPipes' => [
            // event 转 message
            // transfer curtain event messages to other messages
            \Commune\Chatbot\App\SessionPipe\EventMsgPipe::class,
            // 单纯用于测试的管道,#intentName# 模拟命中一个意图.
            // use "#intentName#" pattern to mock intent
            \Commune\Chatbot\App\SessionPipe\MarkedIntentPipe::class,
            // 用户可用的命令.
            SessionPipes\UserCommandsPipe::class,
            // 超级管理员可用的命令. for supervisor only
            SessionPipes\AnalyseCommandsPipe::class,
            // 优先级最高的意图, 通常用于导航.
            // 会优先匹配这些意图.
            // highest level intent
            SessionPipes\NavigationIntentsPipe::class,
        ],

        // hearing 模块系统默认的 defaultFallback 方法
        // 在 $dialog->hear()->end() 的时候调用.
        'hearingFallback' => \Commune\Components\SimpleChat\Callables\SimpleChatAction::class,
    ],
];


// 根据是否有 rasa 决定是否开启
$hasRasa = env('RASA_API', '');
if (!empty($hasRasa)) {
    $sessionPipes = $chatbot['host']['sessionPipes'] ?? [];
    $navigationPipe = array_pop($sessionPipes);
    $sessionPipes[] = \Commune\Components\Rasa\RasaSessionPipe::class;
    $sessionPipes[] = $navigationPipe;
    $chatbot['host']['sessionPipes'] = $sessionPipes;
}

return $chatbot;
