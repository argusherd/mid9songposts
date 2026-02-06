<?php

namespace App\Baha;

use App\Exceptions\NotExpectedPageException;
use App\Jobs\ScrapeBahaPostsContinuously;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;

class SearchTitle extends Page
{
    /**
     * store all searched result
     *
     * @property array
     */
    protected $lists;

    /**
     * it scrape page for further usage from given url
     *
     * then extract all searchable result
     *
     * @param string $url
     */
    public function __construct($url)
    {
        parent::__construct($url);

        $this->lists = $this->getResultList();
    }

    /**
     * get all available links and dispatch corresponsive job to scrape posts
     */
    public function handle($user = null)
    {
        $this->filterByUser($user)
            ->getLinks()
            ->each(function ($link) {
                ScrapeBahaPostsContinuously::dispatch($link);
            });
    }

    /**
     * return all links from searchable result
     *
     * @return \Illuminate\Support\Collection wrap links array into Collection
     */
    public function getLinks()
    {
        $links = $this->lists
            ->each(function (Crawler $node) {
                $path = $node->filter('.b-list__main__title')->first()->attr('href');

                return "https://forum.gamer.com.tw/{$path}";
            });

        return Collection::make($links);
    }

    /**
     * filter down searched result by specific user
     *
     * @param string $user
     *
     * @return \App\Baha\SearchTitle
     */
    public function filterByUser($user = null)
    {
        if (!$user) {
            return $this;
        }

        $this->lists = $this->lists
            ->reduce(function (Crawler $node) use ($user) {
                return $node->filter('.b-list__count__user')->text('') === $user;
            });

        return $this;
    }

    /**
     * get a new instance with next page url if there has one
     *
     * @return \App\Baha\SearchTitle|null
     */
    public function nextPage()
    {
        return $this->hasNextPage() ? new self($this->url->nextPage()) : null;
    }

    /**
     * ensure url/page is expected target in order to fetch data correctly
     *
     * @throws NotExpectedPageException
     */
    protected function ensureIsExpectedUrl()
    {
        if (
            $this->url->domain() === 'forum.gamer.com.tw' &&
            $this->url->path() === '/B.php' &&
            $this->url->hasQuery('q') &&
            $this->url->query('qt') == '1'
        ) {
            return;
        }

        throw new NotExpectedPageException();
    }

    /**
     * extract all searchable result from scraped html
     *
     * add filter get rid of ad row
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function getResultList()
    {
        $lists = $this->html->filter('.b-list__row')->reduce(function (Crawler $node) {
            return $node->filter('.b-list__main')->count() > 0;
        });

        if (
            !$lists->count() &&
            !Str::contains($this->html->text(), '很抱歉，無法搜尋到有關')
        ) {
            throw new NotExpectedPageException('Has the html structure or CSS changed?');
        }

        return $lists;
    }

    /**
     * check is there has more page can go or not
     *
     * @return bool
     */
    public function hasNextPage()
    {
        try {
            return !!$this->html->filter('.pagenow')->first()->nextAll()->text('');
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }
}
