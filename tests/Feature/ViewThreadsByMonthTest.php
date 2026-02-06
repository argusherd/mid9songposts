<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Thread;
use Carbon\Carbon;
use Tests\TestCase;

class ViewThreadsByMonthTest extends TestCase
{
    /** @test */
    public function it_shows_current_month_and_all_days_in_current_month_by_default()
    {
        $response = $this->get(route('threads.month'));

        $daysInMonth = today()->daysInMonth;

        $response->assertSee(today()->format('Y-m'));
        $response->assertSee(range(1, $daysInMonth));
    }

    /** @test */
    public function it_also_lists_previous_and_next_month_days_to_make_calendar_full_weeks()
    {
        /**
         * in carlendar, 2022-11 has day 30, 31 from previous month (2022-10)
         * and day 1, 2, 3 from next month (2022-12)
         */
        $currently = Carbon::parse('2022-11-01');

        $response = $this->get(route('threads.month', ['month' =>  "2022-11"]));

        $response->assertSee([
            ...[30, 31],
            ...range(1, $currently->daysInMonth),
            ...[1, 2, 3],
        ]);
    }

    /** @test */
    public function it_provides_links_that_let_you_go_to_previous_and_next_month()
    {
        $response = $this->get(route('threads.month'));

        $ofMonth = today()->toImmutable();

        $response->assertSee([
            route('threads.month', ['month' => $ofMonth->subMonthWithoutOverflow()->format('Y-m')]),
            route('threads.month', ['month' => $ofMonth->addMonthWithoutOverflow()->format('Y-m')]),
        ]);
    }

    /** @test */
    public function it_lists_all_threads_in_that_month()
    {
        $inMonth = Thread::factory()->create([
            'date' => today()->format('Y-m-d'),
        ]);

        $notInMonth = Thread::factory()->create([
            'date' => today()->subMonthWithoutOverflow()->format('Y-m-d'),
        ]);

        $response = $this->get(route('threads.month'));

        $response->assertSee($inMonth->date);
        $response->assertDontSee($notInMonth->date);

        $this->assertCount(1, $response->viewData('threads'));
    }

    /** @test */
    public function it_lists_all_threads_with_posters_count()
    {
        Thread::factory()->has(Post::factory(3))->create();

        $response = $this->get(route('threads.month'));

        $postersCount = $response->viewData('threads')[0]->posters_count;

        $this->assertEquals(3, $postersCount);
    }

    /** @test */
    public function it_can_view_other_threads_by_provide_different_year_and_different_month()
    {
        $thisMonth = Thread::factory()->create([
            'date' => today()->format('Y-m-d'),
        ]);

        $otherMonth = Thread::factory()->create([
            'date' => today()->subMonthWithoutOverflow()->format('Y-m-d'),
        ]);

        $response = $this->get(route('threads.month', [
            'month' => today()->subMonthWithoutOverflow()->format('Y-m'),
        ]));

        $response->assertSee($otherMonth->date);
        $response->assertDontSee($thisMonth->date);
    }

    /** @test */
    public function it_must_provide_a_valid_month()
    {
        $response = $this->get(route('threads.month', [
            'month' => "invalid-month-string",
        ]));

        $response->assertNotFound();
    }

    /** @test */
    public function it_lists_threads_in_current_month_by_default()
    {
        Thread::factory()->create([
            'date' => today()->subMonthWithoutOverflow()->format('Y-m-d'),
        ]);

        $response = $this->get(route('threads.month'));

        $this->assertCount(0, $response->viewData('threads'));
        $response->assertSee(today()->format('Y-m'));

        $response = $this->get(route('threads.month', [
            'month' => today()->subMonthWithoutOverflow()->format('Y-m'),
        ]));

        $this->assertCount(1, $response->viewData('threads'));
    }

    /** @test */
    public function it_provides_links_that_can_let_you_see_specific_thread()
    {
        Thread::factory()->create([
            'date' => $date = today()->format('Y-m-d'),
        ]);

        $response = $this->get(route('threads.month'));

        $response->assertSee(route('threads.show', [
            'thread' => $date,
        ]));
    }

    /** @test */
    public function it_shows_default_page_title_if_no_month_specified()
    {
        $response = $this->get(route('threads.month'));

        $response->assertSee('半夜歌串一人一首 - mid9songposts', false);
    }

    /** @test */
    public function it_shows_which_month_threads_you_are_viewing_if_month_is_specified()
    {
        $response = $this->get(route('threads.month', [
            'month' => $ofMonth = today()->format('Y-m'),
        ]));

        $response->assertSee($ofMonth . ' 歌串列表');
    }
}
