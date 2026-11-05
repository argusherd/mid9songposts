<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Panther\Client;

class RefreshBahaToken implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Client $client;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->initiateBaha();

        if (!$this->isLogin()) {
            dump('logining...');

            $this->loginBaha();

            if (!$this->isLogin()) {
                dump('login failed');

                $this->revokeToken();

                return;
            }
        }

        dump('login successed');

        $this->preserveToken();
    }

    protected function initiateBaha()
    {
        $exe = "drivers/geckodriver";

        if (DIRECTORY_SEPARATOR === '\\') {
            $exe .= ".exe";
        }

        $this->client = Client::createFirefoxClient(base_path($exe));

        $this->client->request('GET', 'https://forum.gamer.com.tw/B.php?bsn=60076');

        if (cache('cookies')) {
            collect(cache('cookies'))->each(
                fn(Cookie $cookie) => $this->client->getCookieJar()->set($cookie)
            );
        }
    }

    protected function isLogin()
    {
        try {
            $page = $this->client->request('GET', 'https://forum.gamer.com.tw/B.php?bsn=60076');

            $this->client->waitFor('#BH-wrapper');

            $hasNoLogin = $page->filter('.TOP-nologin')->count();

            return !(bool) $hasNoLogin;
        } catch (\Throwable $th) {
            return false;
        }
    }

    protected function loginBaha()
    {
        $loginPage = $this->client->request('GET', 'https://user.gamer.com.tw/login.php');

        $this->client->waitFor('#btn-login');

        $form = $loginPage->filter('#form-login')->form([
            'userid' => env('BAHA_USERID'),
            'password' => env('BAHA_PASSWORD'),
            'autoLogin' => 'T',
        ]);

        sleep(5);

        $this->client->submit($form);

        sleep(5);
    }

    protected function revokeToken()
    {
        cache(['cookies' => null]);

        cache(['BAHARUNE' => null]);
    }

    protected function preserveToken()
    {
        cache(['cookies' => $this->client->getCookieJar()->all()]);

        $rune = $this->client->getCookieJar()->get('BAHARUNE');

        cache(['BAHARUNE' => $rune ? $rune->getValue() : null]);
    }
}
