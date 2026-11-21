<?php

declare(strict_types=1);

namespace Sendzly\Laravel;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Sendzly\SendzlyClient;

class SendzlyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/sendzly.php', 'sendzly');

        $this->app->singleton(SendzlyClient::class, function ($app) {
            $cfg = $app['config']->get('sendzly', []);
            return new SendzlyClient(
                (string) ($cfg['mailserver_key'] ?? ''),
                (string) ($cfg['campaign_key'] ?? ''),
                (string) ($cfg['base_url'] ?? SendzlyClient::DEFAULT_BASE_URL),
                (int) ($cfg['timeout'] ?? 15)
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/sendzly.php' => config_path('sendzly.php'),
            ], 'sendzly-config');
        }

        Mail::extend('sendzly', function (array $config = []) {
            $mailserverKey = (string) ($config['mailserver_key'] ?? config('sendzly.mailserver_key', ''));
            $campaignKey = (string) ($config['campaign_key'] ?? config('sendzly.campaign_key', ''));
            $baseUrl = (string) ($config['base_url'] ?? config('sendzly.base_url', SendzlyClient::DEFAULT_BASE_URL));
            $timeout = (int) ($config['timeout'] ?? config('sendzly.timeout', 15));

            return new SendzlyTransport(
                new SendzlyClient($mailserverKey, $campaignKey, $baseUrl, $timeout)
            );
        });
    }
}
