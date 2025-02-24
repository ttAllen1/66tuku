<?php

namespace Modules\Api\Listeners;

use Modules\Api\Events\PicDetailCreatedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class PicDetailCreatedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     * 图库详情新增一条信息后 需要同时新增
     *  1、图片投票表   => 用户投票时新建
     *  2、评论表
     *  3、点赞表
     *  4、收藏表
     * @param PicDetailCreatedEvent $event
     * @return void
     */
    public function handle(PicDetailCreatedEvent $event)
    {
        $detailImgId = $event->picDetail->id;

    }
}
