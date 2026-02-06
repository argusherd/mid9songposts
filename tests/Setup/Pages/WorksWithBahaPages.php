<?php

namespace Tests\Setup\Pages;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\File;

trait WorksWithBahaPages
{
    protected $bahaUrl = 'https://forum.gamer.com.tw/C.php?bsn=60076&snA=6004847';

    protected $singlePostUrl = 'https://forum.gamer.com.tw/Co.php?bsn=60076&sn=38564976';

    protected $pagesFilePath = __DIR__ . '/html';

    protected $jsonTextPath = __DIR__ . '/json';

    protected function postSectionHtml()
    {
        return File::get($this->pagesFilePath . '/post_section.html');
    }

    protected function mockSingleCommentResponse()
    {
        $singleCommentJson = File::get($this->jsonTextPath . '/single_comment.json');

        $this->mockClient([new Response(200, [], $singleCommentJson)]);
    }

    protected function mockMultipleCommentsResponse()
    {
        $multipleCommentsJson = File::get($this->jsonTextPath . '/multiple_comments.json');

        $this->mockClient([new Response(200, [], $multipleCommentsJson)]);
    }

    protected function mockClientWithSinglePost()
    {
        $singlePostHTML = File::get($this->pagesFilePath . '/single_post.html');

        $this->mockClient([new Response(200, [], $singlePostHTML)]);
    }

    protected function mockClientWithThreadFirstPage()
    {
        $threadHTML = File::get($this->pagesFilePath . '/thread_p1.html');

        $this->mockClient([new Response(200, [], $threadHTML)]);
    }

    protected function mockClientWithThreadUnavailable()
    {
        $unavailableHTML = File::get($this->pagesFilePath . '/thread_unavailable.html');

        $this->mockClient([new Response(200, [], $unavailableHTML)]);
    }

    protected function mockClientWithThreadNoDateInTitle()
    {
        $unavailableHTML = File::get($this->pagesFilePath . '/no_date_title.html');

        $this->mockClient([new Response(200, [], $unavailableHTML)]);
    }

    protected function mockClientWithSinglePostFromDifferentYear()
    {
        $unavailableHTML = File::get($this->pagesFilePath . '/different_year_post.html');

        $this->mockClient([new Response(200, [], $unavailableHTML)]);
    }

    protected function mockClientWithThreadAll3Pages()
    {
        $threadP1 = File::get($this->pagesFilePath . '/thread_p1.html');
        $threadP2 = File::get($this->pagesFilePath . '/thread_p2.html');
        $threadP3 = File::get($this->pagesFilePath . '/thread_p3.html');

        $this->mockClient([
            new Response(200, [], $threadP1),
            new Response(200, [], $threadP2),
            new Response(200, [], $threadP3),
        ]);
    }

    protected function mockClientWithSearchUserPageClassTagChanged()
    {
        $html = File::get($this->pagesFilePath . '/search_user_p1.html');
        $classTagChanged = str_replace('b-list__main', 'changed_class', $html);

        $this->mockClient([new Response(200, [], $classTagChanged)]);
    }

    protected function mockClientWithSearchUserNoResult()
    {
        $noResult = File::get($this->pagesFilePath . '/search_user_no_result.html');

        $this->mockClient([new Response(200, [], $noResult)]);
    }

    protected function mockClientWithSearchUserFirstPage()
    {
        $searchUserP1 = File::get($this->pagesFilePath . '/search_user_p1.html');

        $this->mockClient([new Response(200, [], $searchUserP1)]);
    }

    protected function mockClientWithSearchUserAll2Pages()
    {
        $searchUserP1 = File::get($this->pagesFilePath . '/search_user_p1.html');
        $searchUserP2 = File::get($this->pagesFilePath . '/search_user_p2.html');

        $this->mockClient([
            new Response(200, [], $searchUserP1),
            new Response(200, [], $searchUserP2),
        ]);
    }

    protected function mockClientWithSearchUserLastPage()
    {
        $lastPage = File::get($this->pagesFilePath . '/search_user_p2.html');

        $this->mockClient([new Response(200, [], $lastPage)]);
    }

    protected function mockClientWithSearchTitleFirstPage()
    {
        $searchTitleP1 = File::get($this->pagesFilePath . '/search_title_p1.html');

        $this->mockClient([new Response(200, [], $searchTitleP1)]);
    }

    protected function mockClientWithSearchTitleClassTagChanged()
    {
        $html = File::get($this->pagesFilePath . '/search_title_p1.html');
        $classTagChanged = str_replace('b-list__main', 'changed_class', $html);

        $this->mockClient([new Response(200, [], $classTagChanged)]);
    }

    protected function mockClientWithSearchTitleNoResult()
    {
        $noResult = File::get($this->pagesFilePath . '/search_title_no_result.html');

        $this->mockClient([new Response(200, [], $noResult)]);
    }

    protected function mockClientWithSearchTitleAll2Pages()
    {
        $searchTitleP1 = File::get($this->pagesFilePath . '/search_title_p1.html');
        $searchTitleP2 = File::get($this->pagesFilePath . '/search_title_p2.html');

        $this->mockClient([
            new Response(200, [], $searchTitleP1),
            new Response(200, [], $searchTitleP2),
        ]);
    }

    protected function mockClientWithSearchTitleLastPage()
    {
        $lastPage = File::get($this->pagesFilePath . '/search_title_p2.html');

        $this->mockClient([new Response(200, [], $lastPage)]);
    }

    protected function mockClient(array $responses)
    {
        $mock = new MockHandler($responses);

        $handlerStack = HandlerStack::create($mock);

        $client = new Client(['handler' => $handlerStack]);

        app()->instance(Client::class, $client);
    }
}
