<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Config;
use App\Models\Siparis;
use App\Models\Urun;
use App\Models\Kategori;
use App\Models\Kullanici;
use Illuminate\Support\Facades\Cache;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        /*$bitisZamani = now()->addMinutes(10);
        $istatistikler = Cache::remember('istatistikler', $bitisZamani, function () {
            return [
                'bekleyen_siparis' => Siparis::where('durum', 'Siparişiniz alındı')->count()
            ];
        });
        View::share('istatistikler', $istatistikler); // tum viewlerde kullanilabilir*/

        View::composer(['yonetim.*'], function($view) { //hangi view klasorunde gorunmesini istiyorsak onlar yazilir
            $bitisZamani = now()->addMinutes(60000);
            $istatistikler = Cache::remember('istatistikler', $bitisZamani, function () {
                return [
                    'bekleyen_siparis' => Siparis::where('durum', 'Siparişiniz alındı')->count(),
                    'tamamlanan_siparis' => Siparis::where('durum', 'Sipariş tamamlandı')->count(),
                    'toplam_urun' => Urun::count(),
                    'toplam_kategori' => Kategori::count(),
                    'toplam_kullanici' => Kullanici::count()
                ];
            });

            $view->with('istatistikler', $istatistikler); //bu komut olmazsa kullanilmaz
        });


    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
