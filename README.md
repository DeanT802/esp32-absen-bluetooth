# 📡 Indoor Presence Detection System (ESP32 BLE & Laravel)

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![ESP32](https://img.shields.io/badge/ESP32-BLE_Scanner-E7352C?style=flat-square&logo=espressif&logoColor=white)](https://espressif.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)

Sistem presensi dan pelacakan kehadiran dalam ruangan (*Indoor Presence Tracking*) otomatis berbasis **ESP32 BLE Scanner** dan **Laravel 12**. 

ESP32 berfungsi sebagai pemindai (*dumb scanner*) yang menangkap sinyal Bluetooth di sekitarnya dan mengirimkannya ke backend Laravel untuk pemrosesan status kehadiran (Check-in/Check-out) secara otomatis dan *real-time*.

---

## 🚀 Fitur Utama

- **Hybrid BLE Tracking**: Mendeteksi perangkat berdasarkan **MAC Address** (Smartwatch/TWS) dan **iBeacon UUID** (Smartphone Android/iOS untuk mengatasi *MAC Randomization*).
- **Multi-Room Support**: Setiap ESP32 dapat dikonfigurasi dengan `ROOM_ID` berbeda untuk melacak kehadiran di berbagai ruangan.
- **Dynamic WiFi Configuration (WiFiManager)**: Tidak ada *hardcoded* SSID/password di kode ESP32. Pengaturan WiFi dilakukan melalui *Captive Portal* (`ESP32-BLE-Scanner`).
- **Real-Time Live Monitor**: Halaman live debugging untuk memantau perangkat yang terdeteksi secara langsung via polling/AJAX.
- **Smart Presence Calculation**: Otomatis mendeteksi status Masuk saat sinyal muncul dan Keluar saat sinyal hilang.
- **Attendance Reports & Export**: Laporan jam kerja harian, kalkulasi keterlambatan & jam istirahat, serta fitur ekspor ke **Excel (.xlsx)**.

---

## 🏗️ Arsitektur Sistem

```text
[ User / Smartphone / Beacon ]
              │ (BLE Broadcast)
              ▼
       [ ESP32 Scanner ]
              │ (HTTP POST JSON via Wi-Fi)
              ▼
   [ Laravel Backend & API ]
         │          │
         ▼          ▼
     [ MySQL ]  [ Web Dashboard / Reports ]
```

---

## 🛠️ Tech Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Database**: MySQL / MariaDB
- **Export Engine**: OpenSpout
- **Hardware / Firmware**: ESP32 (Arduino Framework, C++)
- **Libraries Arduino**:
  - `WiFiManager` (tzapu)
  - `ArduinoJson` (v7.x)
  - `BLEDevice` & `BLEScan` (ESP32 BLE Library)
  - `HTTPClient`

---

## 📦 Panduan Instalasi

### 1. Backend (Laravel)

```bash
# 1. Clone repository
git clone https://github.com/username/absen-bluetooth.git
cd absen-bluetooth

# 2. Install dependencies
composer install

# 3. Setup environment file
cp .env.example .env
php artisan key:generate

# 4. Konfigurasi database di file .env
# DB_DATABASE=absen_bluetooth
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Jalankan migrasi database
php artisan migrate

# 6. Jalankan server Laravel (gunakan --host=0.0.0.0 agar dapat diakses ESP32)
php artisan serve --host=0.0.0.0 --port=8000
```

> **Catatan:** Pastikan laptop/server memiliki IP lokal yang tetap (*Static IP*) dan berada dalam satu jaringan Wi-Fi dengan ESP32.

---

### 2. Hardware (ESP32 Scanner)

1. Buka folder `esp32_scanner/` dan buka file `esp32_scanner.ino` di **Arduino IDE**.
2. Install library yang dibutuhkan melalui **Library Manager** (`Ctrl+Shift+I`):
   - **WiFiManager** by *tzapu*
   - **ArduinoJson** by *Benoit Blanchon* (Versi 7.x)
3. Sesuaikan konfigurasi di `esp32_scanner.ino`:
   ```cpp
   // IP Laptop/Server Laravel Anda
   const char* serverName = "http://192.168.0.120:8000/api/scanner/report";
   const String apiKey = "secret123";

   // ID Ruangan (sesuaikan dengan ID ruangan di database Laravel)
   const int ROOM_ID = 1;
   ```
4. Upload kode ke board ESP32.
5. **Koneksi Wi-Fi Pertama Kali**:
   - Jika belum terkonfigurasi, ESP32 akan membuat Access Point bernama `ESP32-BLE-Scanner`.
   - Hubungkan HP/Laptop ke Wi-Fi tersebut, lalu buka browser untuk mengisi SSID dan password Wi-Fi ruangan.
   - Kredensial akan tersimpan otomatis di flash memory ESP32.

---

## 💻 Penggunaan & Alur Kerja

1. **Kelola Ruangan**: Tambahkan data ruangan pada menu `/rooms` dan catat ID ruangannya untuk diset pada ESP32.
2. **Daftarkan Karyawan**: Buka menu `/employees` dan daftarkan perangkat:
   - Pilih tipe **MAC Address** untuk perangkat BLE tetap (jam tangan, tag beacon, dll).
   - Pilih tipe **iBeacon (UUID)** jika menggunakan aplikasi simulator beacon di HP.
3. **Pantau Deteksi**: Buka menu **Live Monitor** (`/debug`) untuk memastikan perangkat karyawan terbaca oleh scanner.
4. **Cek Presensi & Laporan**: Status kehadiran otomatis ter-update di **Dashboard** (`/`) dan rekapitulasi dapat dilihat serta diunduh di **Laporan** (`/reports`).

---

## 📂 Struktur Direktori

```text
├── app/
│   ├── Http/Controllers/    # PresenceController (API scanner, dashboard, laporan)
│   └── Models/                # Employee, Room, PresenceLog, ScheduleSetting
├── database/migrations/       # Skema database
├── esp32_scanner/             # Kode firmware Arduino ESP32 (esp32_scanner.ino)
├── resources/views/           # Blade views (Dashboard, Employees, Rooms, Reports, Debug)
├── routes/                    # web.php & api.php
└── public/                    # Assets web
```

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah lisensi [MIT](LICENSE).
