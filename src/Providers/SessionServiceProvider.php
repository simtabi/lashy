<?php

namespace Simtabi\Lashy\pProviders;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Simtabi\Lashy\Supports\LashySessionHandler;

class SessionServiceProvider extends ServiceProvider
{

    private $driverName = 'lashy';

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        Session::extend($this->driverName, function ($app) {
            $table      = $app['config']['session.table'];
            $lifetime   = $app['config']['session.lifetime'];
            $connection = $app['db']->connection($app['config']['session.connection']);
            return new LashySessionHandler($connection, $table, $lifetime, $app);
        });

        config()->set([
            'session.driver'     => $this->driverName,
        ]);

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

}
