<?php

namespace App\Console\Commands;

use App\Baha\Scraper;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\DomCrawler\Crawler as PantherCrawler;

class CheckBahaPageLayout extends Command
{
    protected const PASSED = 'Passed';

    protected const WARN = 'Warn';

    protected const FAILED = 'Failed';

    protected const GUZZLE = 'guzzle';

    protected const PANTHER = 'panther';

    protected $threadPageUrl = '';

    protected Scraper $scraper;

    protected Client|PantherClient $cachedClient;

    protected $postNo = '';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check-baha
                            {client=guzzle : Use determined client to check Baha pages, support guzzle, panther, and all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Check Baha page's layout(html/css) changed or not";

    protected $reports = [
        'Search Title Page Items' => [self::GUZZLE => self::PASSED, self::PANTHER => self::PASSED],
        'Search Title Page Titles' => [self::GUZZLE => self::PASSED, self::PANTHER => self::PASSED],
        'Search Title Page Users' => [self::GUZZLE => self::PASSED, self::PANTHER => self::PASSED],
        'Thread Page Url' => [self::GUZZLE => self::PASSED, self::PANTHER => self::PASSED],
        'Thread Page Title' => [self::GUZZLE => self::PASSED, self::PANTHER => self::PASSED],
        'Thread Page Posts' => [self::GUZZLE => self::PASSED, self::PANTHER => self::PASSED],
        'Thread Page Created At' => [self::GUZZLE => self::PASSED, self::PANTHER => self::PASSED],
        'Post Section Index' => [self::GUZZLE => self::PASSED, self::PANTHER => self::PASSED],
        'Post Section Content' => [self::GUZZLE => self::PASSED, self::PANTHER => self::PASSED],
        'Post Section Created At' => [self::GUZZLE => self::PASSED, self::PANTHER => self::PASSED],
        'Post Section User Id' => [self::GUZZLE => self::PASSED, self::PANTHER => self::PASSED],
        'Post Section User Name' => [self::GUZZLE => self::PASSED, self::PANTHER => self::PASSED],
        'Search User Page Items' => [self::GUZZLE => self::PASSED, self::PANTHER => self::PASSED],
        'Paginator' => [self::GUZZLE => self::PASSED, self::PANTHER => self::PASSED],
        'Comment Request' => self::PASSED,
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $scrapeBy = $this->argument('client');

        if ($scrapeBy === self::GUZZLE || $scrapeBy === 'all') {
            $this->checkBahaPageLayoutBy(self::GUZZLE);
        }

        if ($scrapeBy === self::PANTHER || $scrapeBy === 'all') {
            $this->checkBahaPageLayoutBy(self::PANTHER);
        }
    }

    protected function checkBahaPageLayoutBy($by)
    {
        config(['app.scrape_by' => $by]);

        $this->scraper = new Scraper();

        $this->cachedClient = $this->scraper->client();

        $this->info("Checking search title page by {$by}...");

        $this->checkSearchTitlePage();

        $this->info("Search title page by {$by} checked.");

        $this->info("Checking thread page by {$by} with: {$this->threadPageUrl}");

        $firstPost = $this->checkThreadPage();

        $this->info("Thread page by {$by} checked.");

        $this->info("Checking Post Section by {$by}...");

        $this->checkPostSection($firstPost);

        $this->info("Post section by {$by} checked.");

        $this->info("Checking search user page by {$by}...");

        $this->checkSearchUserPage();

        $this->info("Search user page by {$by} checked.");

        if ($this->cachedClient instanceof Client) {
            $this->checkCommentRequest();

            $this->info('Comment request checked.');
        }

        $this->outputResults();
    }

    protected function checkSearchTitlePage()
    {
        $page = $this->scraper->getPage('https://forum.gamer.com.tw/B.php?bsn=60076&qt=1&q=半夜歌串一人一首');

        $items = $page->filter('.b-list__row');

        $this->setCountableSearchReport('Search Title Items', $items->count());

        $titles = $page->filter('.b-list__main__title');

        $this->setCountableSearchReport('Search Title Item Titles', $titles->count());

        $path = $titles->first()->attr('href');

        $this->threadPageUrl = "https://forum.gamer.com.tw/{$path}";

        $users = $page->filter('.b-list__count__user');

        $this->setCountableSearchReport('Search Title Users', $users->count());

        $this->checkPaginator($page);
    }

    protected function checkThreadPage()
    {
        $page = $this->scraper->getPage($this->threadPageUrl);

        $content = $page->filter('meta[property="al:ios:url"]')->attr('content');

        if (!$content) $this->reports['Thread Page Url'][config('app.scrape_by')] = self::FAILED;

        if (config('app.scrape_by') === self::GUZZLE) {
            $title = $page->filter('title')->text();
        } else {
            $title = $this->cachedClient->getTitle();
        }

        $this->info("Thread Page Title is {$title}");

        if (!$title) $this->reports['Thread Page Title'][config('app.scrape_by')] = self::FAILED;

        $posts = $page->filter('.c-section[id^="post_"]');

        if (!$posts->count()) $this->reports['Thread Page Posts'][config('app.scrape_by')] = self::FAILED;

        $createdAt = $page->filter('.c-post__header__info a[data-mtime]')->first()->attr('data-mtime');

        if (!$createdAt) $this->reports['Thread Page Created At'][config('app.scrape_by')] = self::FAILED;

        $this->checkPaginator($page);

        return $posts->first();
    }

    protected function checkPostSection(Crawler|PantherCrawler $post)
    {
        $id = $post->filter('.c-article')->attr('id');

        $this->postNo = Str::after($id, 'post_');

        if (!$this->postNo) $this->reports['Post Section Index'][config('app.scrape_by')] = self::FAILED;

        if ($this->cachedClient instanceof Client) {
            $content = $post->filter('.c-article__content')->html();
        } else {
            /** @var \Facebook\WebDriver\Remote\RemoteWebElement */
            $webElement = $post->filter('.c-article__content')->getElement(0);

            $content = $webElement->getDomProperty('innerHTML');
        }

        if (!$content) $this->reports['Post Section Content'][config('app.scrape_by')] = self::FAILED;

        $createdAt = $post->filter('a[data-mtime]')->attr('data-mtime');

        if (!$createdAt) $this->reports['Post Section Created At'][config('app.scrape_by')] = self::FAILED;

        $userid = $post->filter('.userid')->text();

        if (!$userid) $this->reports['Post Section User Id'][config('app.scrape_by')] = self::FAILED;

        $username = $post->filter('.username')->text();

        if (!$username) $this->reports['Post Section User Name'][config('app.scrape_by')] = self::FAILED;
    }

    protected function checkSearchUserPage()
    {
        $page = $this->scraper->getPage('https://forum.gamer.com.tw/Bo.php?bsn=60076&qt=6&q=a7752876');

        $items = $page->filter('.b-list__main > a');

        $this->setCountableSearchReport('Search User Page Items', $items->count());

        $this->checkPaginator($page);
    }

    protected function checkCommentRequest()
    {
        $url = "https://forum.gamer.com.tw/ajax/moreCommend.php?bsn=60076&snB=";

        $response = $this->scraper->requestByGuzzle("{$url}{$this->postNo}");

        $data = json_decode($response, true);

        if (!isset($data['next_snC'])) $this->reports['Comment Request'] = self::FAILED;
    }

    protected function outputResults()
    {
        $rows = collect($this->reports)
            ->except('Comment Request')
            ->map(function ($results, $key) {
                $result = $results[config('app.scrape_by')];

                $color = $result == self::PASSED ? 'green' : ($result == self::WARN ? 'yellow' : 'red');

                return [
                    $key,
                    "<fg={$color}>{$result}</>",
                ];
            });

        $commentColor = $this->reports['Comment Request'] == self::PASSED ? 'green' : 'red';
        $rows[] = ['Comment Request', "<fg={$commentColor}>{$this->reports['Comment Request']}</>"];

        $this->table(['Subject', config('app.scrape_by')], $rows);
    }

    protected function setCountableSearchReport(string $key, int $count)
    {
        if ($count === 0) {
            $this->reports[$key][config('app.scrape_by')] = self::FAILED;
        } else if ($count < 30) {
            $this->reports[$key][config('app.scrape_by')] = self::WARN;
        }
    }

    protected function checkPaginator(Crawler $page)
    {
        $pagenow = $page->filter('.pagenow');

        $this->reports['Paginator'][config('app.scrape_by')] = $pagenow->count()
            ? $this->reports['Paginator'][config('app.scrape_by')]
            : self::FAILED;
    }
}
