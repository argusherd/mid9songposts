<?php

namespace Tests\Unit\Baha;

use App\Baha\ThreadPage;
use App\Exceptions\NotExpectedPageException;
use App\Links\UrlString;
use App\Models\Post;
use App\Models\Thread;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ThreadPageTest extends TestCase
{
    /** @test */
    public function it_needs_provide_a_expected_url_in_order_to_fetch_data_correctly()
    {
        $this->expectException(NotExpectedPageException::class);

        new ThreadPage('http://example.foo.bar');
    }

    /** @test */
    public function it_can_save_thread_related_data_into_database()
    {
        $this->mockClientWithThreadFirstPage();

        $threadPage = new ThreadPage($this->bahaUrl);

        $this->assertCount(0, Thread::all());

        $threadPage->save();

        $this->assertCount(1, Thread::all());

        $this->assertDatabaseHas('threads', [
            'no' => '6004847',
            'title' => '【情報】11/7 半夜歌串一人一首',
            'date' => '2020-11-07',
        ]);
    }

    /** @test */
    public function it_wont_create_two_row_of_same_thread_into_database()
    {
        $this->mockClientWithThreadFirstPage();

        $threadPage = new ThreadPage($this->bahaUrl);

        $threadPage->save();

        $this->assertCount(1, Thread::all());

        $threadPage->save();

        $this->assertCount(1, Thread::all());
    }

    /** @test */
    public function it_can_get_unique_index_own_by_the_thread_from_scrape_page()
    {
        $this->mockClientWithThreadFirstPage();

        $threadPage = new ThreadPage($this->bahaUrl);

        $this->assertEquals('6004847', $threadPage->index());
    }

    /** @test */
    public function it_can_also_get_thread_unique_index_from_single_post_page()
    {
        $this->mockClientWithSinglePost();

        $threadPage = new ThreadPage($this->singlePostUrl);

        $this->assertEquals('6055013', $threadPage->index());
    }

    /** @test */
    public function it_will_throw_exception_if_thread_index_no_longer_available()
    {
        $this->mockClientWithThreadUnavailable();

        $this->expectException(NotExpectedPageException::class);

        $threadPage = new ThreadPage($this->bahaUrl);

        $threadPage->index();
    }

    /** @test */
    public function it_can_get_title_from_scraped_page()
    {
        $this->mockClientWithThreadFirstPage();

        $threadPage = new ThreadPage($this->bahaUrl);

        $this->assertEquals('【情報】11/7 半夜歌串一人一首', $threadPage->title());
    }

    /** @test */
    public function it_can_also_scrape_title_from_single_post_page()
    {
        $this->mockClientWithSinglePost();

        $post = new ThreadPage($this->bahaUrl);

        $this->assertEquals('【情報】12/8 半夜歌串一人一首', $post->title());
    }

    /** @test */
    public function it_can_get_published_date_from_scrape_page()
    {
        $this->mockClientWithThreadFirstPage();

        $threadPage = new ThreadPage($this->bahaUrl);

        $this->assertEquals('2020-11-07', $threadPage->date());
    }

    /** @test */
    public function it_use_first_post_published_date_if_title_not_provide_a_date()
    {
        $this->mockClientWithThreadNoDateInTitle();

        $threadPage = new ThreadPage($this->bahaUrl);

        $this->assertEquals('2021-01-02', $threadPage->date());
    }

    /** @test */
    public function thread_date_and_post_published_date_wont_pass_far_too_long()
    {
        $this->mockClientWithSinglePostFromDifferentYear();

        $threadPage = new ThreadPage($this->bahaUrl);

        $this->assertEquals('2020-12-31', $threadPage->date());
    }

    /** @test */
    public function it_can_fetch_all_posts_from_current_page()
    {
        $this->mockClientWithThreadFirstPage();

        $threadPage = new ThreadPage($this->bahaUrl);

        $this->assertCount(20, $threadPage->posts());

        $this->assertInstanceOf(Collection::class, $threadPage->posts());
    }

    /** @test */
    public function it_only_get_one_post_from_single_post_page()
    {
        $this->mockClientWithSinglePost();

        $threadPage = new ThreadPage($this->bahaUrl);

        $this->assertCount(1, $threadPage->posts());
    }

    /** @test */
    public function it_throws_exception_when_there_are_no_posts()
    {
        $this->mockClientWithThreadUnavailable();

        $this->expectException(NotExpectedPageException::class);

        $threadPage = new ThreadPage($this->bahaUrl);

        $threadPage->posts();
    }

    /** @test */
    public function it_can_check_there_is_more_page_after_this_one_or_not()
    {
        $this->mockClientWithThreadAll3Pages();

        $threadPage = new ThreadPage($this->bahaUrl);

        $this->assertTrue($threadPage->hasNextPage());

        $threadPage = $threadPage->nextPage();

        $this->assertTrue($threadPage->hasNextPage());

        $threadPage = $threadPage->nextPage();

        $this->assertFalse($threadPage->hasNextPage());
    }

    /** @test */
    public function it_can_generate_a_new_instance_with_next_page_url()
    {
        $this->mockClientWithThreadAll3Pages();

        $threadPage = new ThreadPage($this->bahaUrl);

        $nextPage = $threadPage->nextPage();

        $this->assertEquals($this->bahaUrl . "&page=2", (string) $nextPage->url());
    }

    /** @test */
    public function if_html_indicate_there_is_no_more_page_nextPage_will_return_null()
    {
        $this->mockClientWithSinglePost();

        $threadPage = new ThreadPage($this->bahaUrl);

        $this->assertNull($threadPage->nextPage());
    }

    /** @test */
    public function it_delegate_UrlString_object_to_fetch_url_params()
    {
        $this->mockClientWithThreadFirstPage();

        $threadPage = new ThreadPage($this->bahaUrl);

        $this->assertInstanceOf(UrlString::class, $threadPage->url());
    }

    /** @test */
    public function it_can_persist_the_thread_and_all_posts_from_current_page_into_the_database_in_one_action()
    {
        $this->mockClientWithThreadFirstPage();

        $this->assertEquals(0, Thread::count());
        $this->assertEquals(0, Post::count());

        $threadPage = new ThreadPage($this->bahaUrl);

        $threadPage->handle();

        $this->assertEquals(1, Thread::count());
        $this->assertEquals(20, Post::count());

        $this->assertEquals(Thread::first()->id, Post::first()->thread_id);
    }
}
