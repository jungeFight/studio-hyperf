<?php


namespace Commune\Components\Story\Options;


use Commune\Support\Option;

/**
 * 脚本中可用的命令形式.
 *
 * @property-read string $menu
 * @property-read string $returnGame
 * @property-read string $selectEpisode
 * @property-read string $hearDescription
 * @property-read string $unlockEndings
 * @property-read string $quit
 *
 */
class CommandsOption extends Option
{
    public static function stub(): array
    {
        return [
            'menu' => '打开菜单',
            'returnGame' => '返回游戏',
            'selectEpisode' => '选择章节',
            'hearDescription' => '查看简介',
            'unlockEndings' => '解锁结局',
            'quit' => '退出游戏',
        ];
    }


}