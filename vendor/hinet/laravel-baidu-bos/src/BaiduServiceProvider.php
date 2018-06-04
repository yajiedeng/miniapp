<?php namespace Hinet\Baidu;

use Storage;
use BaiduBce\Services\Bos\BosClient;
use Hinet\Flysystem\BaiduBos\BaiduBosAdapter;
use Hinet\Flysystem\BaiduBos\PutFilePlugin;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class BaiduServiceProvider extends ServiceProvider {

    public function boot()
    {
        Storage::extend('bos', function($app, $config)
        {
            $client = new BosClient($config['options']);
            $filesystem = new Filesystem(new BaiduBosAdapter($client, $config['bucket']));
            $filesystem->addPlugin(new PutFilePlugin);
            
            return $filesystem;
        });
    }

    public function register()
    {
        //
    }

}
