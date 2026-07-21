<?php

namespace App\Console;

use App\Jobs\RefreshBahaToken;
use App\Jobs\ScrapePostsFromSearchTitle;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(fn() => (new RefreshBahaToken)->handle())
            ->dailyAt(config('app.refresh_baha_token_at'))
            ->when(fn() => config('app.refresh_baha_token'));

        $schedule->call(fn() => (new ScrapePostsFromSearchTitle)->handle())
            ->dailyAt(config('app.scrape_posts_at'))
            ->when(fn() => (bool) cache('BAHARUNE'));

        $schedule->command('queue:work', ['--stop-when-empty', '--rest=3', '--timeout=120'])
            ->dailyAt(config('app.queue_work_at'))
            ->when(fn() => (bool) cache('BAHARUNE'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
