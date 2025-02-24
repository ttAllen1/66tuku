<?php

namespace Modules\Admin\Listeners;

use Modules\Admin\Events\TestEvent3;
use Modules\Admin\Events\TestEvent1;
use Modules\Admin\Events\TestEvent2;

class TestEventSubscriber
{
    public function handleTest1($event)
    {
        echo 'handleTest1:'.$event->a.PHP_EOL;
    }

    public function handleTest2($event)
    {
        echo 'handleTest2:'.$event->a.PHP_EOL;
    }

    public function handleTest3($event)
    {
        echo 'handleTest3:'.$event->a.PHP_EOL;
    }

    public function handleTestAll($event)
    {
        echo 'handleTestAll:'.$event->a.PHP_EOL;
    }

    public function subscribe($event)
    {
        $event->listen(
            [TestEvent1::class], // 事件列表
            [TestEventSubscriber::class, 'handleTest1']  // 处理列表
        );
        $event->listen(
            [TestEvent2::class], // 事件列表
            [TestEventSubscriber::class, 'handleTest2']  // 处理列表
        );
        $event->listen(
            [TestEvent3::class], // 事件列表
            [TestEventSubscriber::class, 'handleTest3']  // 处理列表
        );
        $event->listen(
            [TestEvent1::class,TestEvent2::class, TestEvent3::class], // 事件列表
            [TestEventSubscriber::class, 'handleTestAll']  // 处理列表
        );
    }

}
