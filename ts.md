HTTP error -1 artinya ESP32 gagal membuat koneksi TCP ke server. Mari kita cek beberapa hal:

Juga pastikan Laravel server berjalan di 0.0.0.0 (agar bisa diakses dari perangkat lain di jaringan, bukan hanya localhost):

bash
php artisan serve --host=0.0.0.0 --port=8000
Ringkasan
Masalah	Detail
IP salah	Kode mengarah ke 192.168.0.147, tapi laptop sekarang di 192.168.0.120 → sudah diperbaiki
Laravel harus listen di 0.0.0.0	php artisan serve default-nya hanya listen di 127.0.0.1 (localhost), sehingga ESP32 tidak bisa mengaksesnya. Gunakan flag --host=0.0.0.0

IMPORTANT

IP lokal bisa berubah sewaktu-waktu (DHCP). Kalau nanti error -1 muncul lagi, cek ulang ipconfig dan sesuaikan serverName. Alternatifnya, atur IP laptop kamu jadi static di router supaya tidak berubah.