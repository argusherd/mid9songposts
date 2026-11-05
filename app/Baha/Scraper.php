<?php

namespace App\Baha;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\Panther\DomCrawler\Crawler as PantherCrawler;

class Scraper
{
    protected Client|PantherClient $cachedClient;

    protected bool $pantherInitiated = false;

    public function __construct()
    {
        $this->cachedClient = app(Client::class);
    }

    public function client(): Client|PantherClient
    {
        return $this->cachedClient;
    }

    public function getPage(string $url): Crawler|PantherCrawler
    {
        if ($this->cachedClient instanceof PantherClient) {
            return $this->requestByPanther($url);
        }

        return new Crawler($this->requestByGuzzle($url));
    }

    public function requestByGuzzle(string $url): string
    {
        if ($this->cachedClient instanceof Client == false) {
            $this->cachedClient = new Client();
        }

        $jar = CookieJar::fromArray(['BAHARUNE' => cache('BAHARUNE')], '.gamer.com.tw');

        $response = $this->cachedClient->request('GET', $url, [
            'cookies' => $jar,
            'http_errors' => false,
        ]);

        return (string) $response->getBody();
    }

    public function requestByPanther(string $url): PantherCrawler
    {
        if ($this->cachedClient instanceof PantherClient == false) {
            $exe = "drivers/geckodriver";

            if (DIRECTORY_SEPARATOR === '\\') {
                $exe .= ".exe";
            }

            $this->cachedClient = PantherClient::createFirefoxClient(base_path($exe));
            $this->pantherInitiated = false;
        }

        $this->initiatePantherClient();

        return $this->cachedClient->request('GET', $url);
    }

    protected function initiatePantherClient()
    {
        if ($this->pantherInitiated) {
            return;
        }

        $this->cachedClient->request('GET', 'https://forum.gamer.com.tw');

        $jar = new Cookie('BAHARUNE', cache('BAHARUNE', ''), null, null, '.gamer.com.tw');

        $this->cachedClient->getCookieJar()->set($jar);

        $this->pantherInitiated = true;
    }
}
