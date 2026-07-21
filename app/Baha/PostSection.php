<?php

namespace App\Baha;

use App\Models\Post;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panther\DomCrawler\Crawler as PantherCrawler;

class PostSection
{
    /**
     * @property \Symfony\Component\DomCrawler\Crawler for html interact
     */
    protected $html;

    /**
     * @property \App\Models\Post
     */
    protected $post;

    public function setHTMLCrawler(Crawler $crawler)
    {
        $this->html = $crawler;
    }

    /**
     * save post's related data into database
     * or retrieve a exists row from it
     * and it won't create same post twice
     *
     * @param int $thread_id
     * pass this param for relationship connection
     * but its nullable in database scheme
     *
     * @return Post
     */
    public function save($thread_id = null)
    {
        return $this->post ??
            $this->post = Post::updateOrCreate(
                [
                    'poster_id' => $this->poster()->save()->id,
                    'thread_id' => $thread_id,
                    'no' => $this->index(),
                ],
                [
                    'content' => $this->content(),
                    'created_at' => $this->createdAt(),
                    'inserted_at' => now()->toDateTimeString(),
                ]
            );
    }

    /**
     * get post id from outer html attribute
     *
     * add trimmer get rid of 'post_', only needs the number after it
     *
     * @return string
     *
     * @throws \InvalidArgumentException if somehow can't get expected html
     */
    public function index()
    {
        return Str::after($this->html->filter('.c-article')->attr('id'), 'cf');
    }

    /**
     * get post content's raw html
     *
     * @return string
     */
    public function content()
    {
        /** @var \Symfony\Component\DomCrawler\Crawler|\Symfony\Component\Panther\DomCrawler\Crawler */
        $crawler = $this->html->filter('.c-article__content');

        if ($crawler instanceof PantherCrawler) {
            /** @var \Facebook\WebDriver\Remote\RemoteWebElement */
            $webElement = $crawler->getElement(0);

            $content = $webElement->getDomProperty('innerHTML');
        } else {
            $content = $crawler->html();
        }

        return urldecode($content);
    }

    /**
     * get post published time
     *
     * @return string
     */
    public function createdAt()
    {
        return $this->html->filter('a[data-mtime]')->attr('data-mtime');
    }

    /**
     * extract poster data into its own instance
     *
     * @return \App\Baha\PosterData
     */
    public function poster()
    {
        return new PosterData(
            $account = $this->html->filter('.userid')->text(),
            $name = $this->html->filter('.username')->text(),
        );
    }
}
