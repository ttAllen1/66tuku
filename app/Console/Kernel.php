<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Bus;
use Modules\Admin\Console\Commands\AmIndexPic;
use Modules\Admin\Console\Commands\AmYearPic;
use Modules\Admin\Console\Commands\AutoVote;
use Modules\Admin\Console\Commands\AutoXgAmMaxIssues;
use Modules\Admin\Console\Commands\CacheIndexPic;
use Modules\Admin\Console\Commands\CheckTransfer;
use Modules\Admin\Console\Commands\ClearCorpusDomain;
use Modules\Admin\Console\Commands\CorpusCommand;
use Modules\Admin\Console\Commands\DelComments;
use Modules\Admin\Console\Commands\DiagramCommand;
use Modules\Admin\Console\Commands\FiveVirtualNumber;
use Modules\Admin\Console\Commands\ForecastBetFive;
use Modules\Admin\Console\Commands\ForecastBetFour;
use Modules\Admin\Console\Commands\ForecastBetOne;
use Modules\Admin\Console\Commands\ForecastBetSeven;
use Modules\Admin\Console\Commands\ForecastBetSix;
use Modules\Admin\Console\Commands\ForecastBetThree;
use Modules\Admin\Console\Commands\ForecastBetTwo;
use Modules\Admin\Console\Commands\GenGamePeriods;
use Modules\Admin\Console\Commands\GuessIsWin;
use Modules\Admin\Console\Commands\HistoryDataToRecommends;
use Modules\Admin\Console\Commands\HkLive;
use Modules\Admin\Console\Commands\HumorousGuess;
use Modules\Admin\Console\Commands\HumorousGuessAm;
use Modules\Admin\Console\Commands\HumorousGuessOldAm;
use Modules\Admin\Console\Commands\IndexGuess;
use Modules\Admin\Console\Commands\Kl8IndexPic;
use Modules\Admin\Console\Commands\Kl8YearPic;
use Modules\Admin\Console\Commands\LiuheOpenDate;
use Modules\Admin\Console\Commands\MemberLimit;
use Modules\Admin\Console\Commands\MysteryTips;
use Modules\Admin\Console\Commands\MysteryTipsAm;
use Modules\Admin\Console\Commands\MysteryTipsOldAm;
use Modules\Admin\Console\Commands\NewLotteryToChat;
use Modules\Admin\Console\Commands\NextOpenDate;
use Modules\Admin\Console\Commands\OldAmIndexPic;
use Modules\Admin\Console\Commands\OldAmYearPic;
use Modules\Admin\Console\Commands\PicFav;
use Modules\Admin\Console\Commands\PicInfoAssociate;
use Modules\Admin\Console\Commands\PicInfoOther;
use Modules\Admin\Console\Commands\RealOpenLottery;
use Modules\Admin\Console\Commands\RealOpenLotteryV;
use Modules\Admin\Console\Commands\RedPacketAutoOpen;
use Modules\Admin\Console\Commands\RedPacketOpen;
use Modules\Admin\Console\Commands\Smart;
use Modules\Admin\Console\Commands\SmartChat;
use Modules\Admin\Console\Commands\Spider;
use Modules\Admin\Console\Commands\SpiderComments;
use Modules\Admin\Console\Commands\SpiderNumbers;
use Modules\Admin\Console\Commands\SpiderNumbersByAC;
use Modules\Admin\Console\Commands\SyncUserWithdraw;
use Modules\Admin\Console\Commands\TgSend;
use Modules\Admin\Console\Commands\TongbuDb;
use Modules\Admin\Console\Commands\UpdateChatAvatar;
use Modules\Admin\Console\Commands\VideoCommand;
use Modules\Admin\Console\Commands\WriteRecommend;
use Modules\Admin\Console\Commands\XgAI;
use Modules\Admin\Console\Commands\YearPicIssues;
use Modules\Admin\Console\Commands\ZanMoney;
use Modules\Admin\Jobs\LastMonthViews;
use Modules\Admin\Jobs\OpenEdLottery;
use Modules\Admin\Jobs\OpenEdLottery2;
use Modules\Admin\Jobs\OpenEdLottery5;
use Modules\Admin\Jobs\YesterdayViews;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Spider::class,
        SpiderNumbers::class,
        LiuheOpenDate::class,
        HistoryDataToRecommends::class,
        PicInfoAssociate::class,
        PicInfoOther::class,
        CorpusCommand::class,
        DiagramCommand::class,
        VideoCommand::class,
        HumorousGuess::class,
        MysteryTips::class,
        GuessIsWin::class,
        YearPicIssues::class,
        RealOpenLottery::class,
        NextOpenDate::class,
        SpiderNumbersByAC::class,
        AmIndexPic::class,
        AmYearPic::class,
        HumorousGuessAm::class,
        MysteryTipsAm::class,
        CacheIndexPic::class,
        WriteRecommend::class,
        UpdateChatAvatar::class,
        TongbuDb::class,
        ForecastBetOne::class,
        ForecastBetTwo::class,
        ForecastBetThree::class,
        ForecastBetFour::class,
        ForecastBetFive::class,
        ForecastBetSix::class,
        ForecastBetSeven::class,
//        SyncUserWithdraw::class,
        RedPacketOpen::class,
        FiveVirtualNumber::class,
        HkLive::class,
        ZanMoney::class,
        RealOpenLotteryV::class,
        RedPacketAutoOpen::class,
//        NewLotteryToChat::class
        Kl8IndexPic::class,
        Kl8YearPic::class,
        Smart::class,
        AutoVote::class,
        SmartChat::class,
        PicFav::class,
        OldAmIndexPic::class,
        OldAmYearPic::class,
        MysteryTipsOldAm::class,
        HumorousGuessOldAm::class,
        DelComments::class,
        CheckTransfer::class,
        ClearCorpusDomain::class,
        SpiderComments::class,
        TgSend::class,
        MemberLimit::class,
        GenGamePeriods::class,
        XgAI::class,
        IndexGuess::class,
        AutoXgAmMaxIssues::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        $schedule->command('module:red-packet')->everyMinute()->withoutOverlapping()->runInBackground();

        $schedule->job(new OpenEdLottery())->everyMinute()->withoutOverlapping()->runInBackground();

        $schedule->job(new YesterdayViews())->daily()->withoutOverlapping()->runInBackground();

        $schedule->job(new LastMonthViews())->monthlyOn(1, '00:01')->withoutOverlapping()->runInBackground();

        $schedule->command('module:open-date')->monthlyOn(1, '00:05')->withoutOverlapping();

        $schedule->command('module:mystery-tips')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('module:mystery-tips-old-am')->everyTenMinutes()->withoutOverlapping();

        $schedule->command('module:humorous-guess')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('module:humorous-guess-old-am')->everyTenMinutes()->withoutOverlapping();

        $schedule->command('module:humorous-guess-am')->everyTenMinutes()->withoutOverlapping();

        $schedule->command('module:cache-index-pic')->everyTenMinutes()->withoutOverlapping();

        $schedule->command('module:write-recommend')->everyMinute()->withoutOverlapping()->runInBackground();

        $schedule->command('module:auto-vote')->everyMinute()->withoutOverlapping()->runInBackground();
        $schedule->command('module:smart')->hourly()->withoutOverlapping()->runInBackground();

        $schedule->command('module:smart-chat')->everyFiveMinutes()->withoutOverlapping()->runInBackground();
        $schedule->command('module:zan-money')->dailyAt('22:00')->withoutOverlapping()->runInBackground();
        $schedule->command('module:pic-fav')->dailyAt('22:00')->withoutOverlapping()->runInBackground();
        $schedule->command('module:spider-comment')->everyThirtyMinutes()->withoutOverlapping()->runInBackground();

        $schedule->command('module:gen-game-periods')->dailyAt('23:30')->withoutOverlapping()->runInBackground();

        $schedule->command('module:xg-ai')->everyFiveMinutes()->withoutOverlapping()->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
