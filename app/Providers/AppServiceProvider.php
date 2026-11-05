<?php

namespace App\Providers;

use GuzzleHttp\Client;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Panther\Client as PantherClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Client::class, function ($app) {
            if (config('app.scrape_by') == 'guzzle') {
                return new Client();
            } elseif (config('app.scrape_by') == 'panther') {
                $exe = "drivers/geckodriver";

                if (DIRECTORY_SEPARATOR === '\\') {
                    $exe .= ".exe";
                }

                return PantherClient::createFirefoxClient(base_path($exe));
            }
        });

        $this->app->bind(HTMLPurifier::class, function ($app) {
            $config = HTMLPurifier_Config::createDefault();

            $config->set('Cache.SerializerPath', storage_path('framework/htmlpurifier'));

            return new HTMLPurifier($config);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Model::unguard();

        Paginator::defaultView('paginator');

        if (config('app.env') === 'production')
            URL::forceScheme('https');
    }
}
