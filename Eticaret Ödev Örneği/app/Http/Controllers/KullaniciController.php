<?php

namespace App\Http\Controllers;

use App\Mail\KullaniciKayitMail;
use App\Models\Kategori;
use App\Models\Kullanici;
use App\Models\KullaniciDetay;
use App\Models\Sepet;
use App\Models\SepetUrun;
use Cart;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Auth;


class KullaniciController extends Controller
{
    public function giris_form()
    {
        $kategoriler=Kategori::whereRaw('ust_id is null')->get();

        return view('kullanici.oturumac',compact('kategoriler'));
    }
    public function giris()
    {
        $this->validate(request(), [
            'email' => 'required|email',
            'sifre' => 'required'
        ]);
        if (auth()->attempt(['email' => request('email'), 'password' => request('sifre')], request()->has('benihatirla'))) {

            request()->session()->regenerate();
            $aktif_sepet_id =Sepet::aktif_sepet_id();
            if (!is_null($aktif_sepet_id))
            {
                $aktif_sepet=Sepet::create(['kullanici_id'=>auth()->id()]);
                $aktif_sepet_id=$aktif_sepet->id;
            }
            session()->put('aktif_sepet_id', $aktif_sepet_id);
            if (Cart::count() > 0) {
                foreach (Cart::content() as $cartItem) {
                    $sepetUrun = SepetUrun::updateOrCreate(
                        ['sepet_id' => $aktif_sepet_id, 'urun_id' => $cartItem->id]);
                    $sepetUrun->adet += $cartItem->qty;
                    $sepetUrun->fiyati = $cartItem->price;
                    $sepetUrun->durum = "Beklemede";
                    $sepetUrun->save();
                }
            }
            Cart::destroy();
            $sepetUrunler = SepetUrun::with('urun')->where('sepet_id', $aktif_sepet_id)->get();
            foreach ($sepetUrunler as $sepetUrun) {
                Cart::add($sepetUrun->urun->id, $sepetUrun->urun->urun_adi, $sepetUrun->adet, $sepetUrun->fiyati, ['slug' => $sepetUrun->urun->slug]);
            }

            return redirect()->intended('/');
        } else {
            $errors = ['email' => 'Hatalı giriş'];

            return back()->withErrors($errors);
        }
    }

    public function kaydol_form()
    {
        $kategoriler=Kategori::whereRaw('ust_id is null')->get();

        return view('kullanici.kaydol',compact('kategoriler'));
    }

    public function kaydol()
    {
        $this->validate(request(), [ //dogrulama islemleri
            'adsoyad' => 'required|min:5|max:60',
            'email' => 'required|email|unique:kullanici',
            'sifre' => 'required|confirmed|min:5|max:15'
        ]);
        $kullanici = Kullanici::create([
            'adsoyad' => request('adsoyad'), //request formdan gelen degeri alir
            'email' => request('email'),//formdan gelen degeri alir
            'sifre' => Hash::make(request('sifre')),//sifre hashlenerek saklanir
            'aktivasyon_anahtari' => Str::random(60), //rastgele metin olusturur
            'aktif_mi' => 0
        ]);
        $kullanici->detay()->save(new KullaniciDetay());
        Mail::to(request('email'))->send(new KullaniciKayitMail($kullanici));

        auth()->login($kullanici); //veritabanina eklendikten sonra sisteme giris otomatiklesir

        return redirect()->route('anasayfa');
    }

    public function aktiflestir($anahtar)
    {
        $kullanici = Kullanici::where('aktivasyon_anahtari', $anahtar)->first();
        if (!is_null($kullanici)) {
            $kullanici->aktivasyon_anahtari = null;
            $kullanici->aktif_mi = 1;
            $kullanici->save();

            return redirect()->to('/')
                ->with('mesaj', 'Kullanıcı kaydınız aktifleştirildi')
                ->with('mesaj_tur', 'success');
        } else {
            return redirect()->to('/')
                ->with('mesaj', 'Kullanıcı kaydınız aktifleştirilemedi')
                ->with('mesaj_tur', 'warning');
        }
    }

    public function oturumukapat()
    {
        auth()->logout();
        return redirect()->route('anasayfa');
    }

    public function form($id = 0)
    {
        $kullanici = new Kullanici;
        if ($id > 0) {
            $kullanici = Kullanici::find($id);
        }
        $kategoriler=Kategori::whereRaw('ust_id is null')->get();

        return view('kullanici.form', compact('kullanici','kategoriler'));
    }
    public function kaydet($id = 0)
    {
        $this->validate(request(), [
            'adsoyad' => 'required',
            'email'   => 'required|email'
        ]);

        $data = request()->only('adsoyad', 'email');
        if (request()->filled('sifre')) { //sifre yeniden girilmisse guncellemeye dahil edilir
            $data['sifre'] = Hash::make(request('sifre'));
        }
        if ($id > 0) {
            $kullanici = Kullanici::where('id', $id)->firstOrFail();
            $kullanici->update($data);
        } else {
            $kullanici = Kullanici::create($data);
        }

        KullaniciDetay::updateOrCreate(
            ['kullanici_id' => $kullanici->id],
            [
                'adres'       => request('adres'),
                'telefon'     => request('telefon'),
            ]
        );

        return redirect()
            ->route('kullanici.duzenle', $kullanici->id)
            ->with('mesaj', ($id > 0 ? 'Güncellendi' : 'Kaydedildi'))
            ->with('mesaj_tur', 'success');
    }
    public function sil($id)
    {
        Kullanici::destroy($id);

        return redirect()
            ->route('anasayfa')
            ->with('mesaj', 'Kayıt silindi')
            ->with('mesaj_tur', 'success');
    }
}
