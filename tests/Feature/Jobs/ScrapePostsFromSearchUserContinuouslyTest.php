<?php

namespace Tests\Feature;

use App\Jobs\ScrapeBahaPosts;
use App\Jobs\ScrapePostsFromSearchUserContinuously;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Pages\WorksWithBahaPages;
use Tests\TestCase;

class ScrapePostsFromSearchUserContinuouslyTest extends TestCase
{
    use WorksWithBahaPages;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    /** @test */
    public function it_will_resolve_and_dispatch_scrape_posts_tasks()
    {
        $this->fakeOnePageSearchUserResponse();

        $job = new ScrapePostsFromSearchUserContinuously(
            $user = 'foobar666',
            $page = 1,
            $title = '半夜歌串一人一首'
        );

        $job->handle();

        Http::assertSent(function ($request) {
            return $request->url() == 'https://forum.gamer.com.tw/Bo.php?bsn=60076&qt=6&q=foobar666&page=1';
        });

        Queue::assertPushed(ScrapeBahaPosts::class, 29);

        Queue::assertPushed(function (ScrapeBahaPosts $job) {
            return $job->url == 'https://forum.gamer.com.tw/Co.php?bsn=60076&sn=80190131';
        });
    }

    /** @test */
    public function it_will_dispatch_a_new_job_to_continue_same_process_with_next_page()
    {
        $this->fakeOnePageSearchUserResponse();

        (new ScrapePostsFromSearchUserContinuously(
            $user = 'foobar666',
            $page = 1,
            $title = '半夜歌串一人一首'
        ))->handle();

        Queue::assertPushed(
            function (ScrapePostsFromSearchUserContinuously $job)
             use ($title, $page, $user) {
                return $job->user == $user &&
                $job->page == 2 &&
                $job->title == $title;
            });
    }

    /** @test */
    public function it_wont_dispatch_a_new_job_if_there_is_no_more_page_to_scrape()
    {
        $this->fakeLastSearchUserPageResponse();

        $job = new ScrapePostsFromSearchUserContinuously('foobar666');

        $job->handle();

        Queue::assertPushed(ScrapePostsFromSearchUserContinuously::class, 0);
    }
}
