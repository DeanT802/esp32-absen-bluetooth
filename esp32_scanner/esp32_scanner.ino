#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiManager.h>
#include <BLEDevice.h>
#include <BLEUtils.h>
#include <BLEScan.h>
#include <BLEAdvertisedDevice.h>
#include <ArduinoJson.h>

// ================= PENGATURAN JARINGAN =================
const char* serverName = "http://192.168.0.120:8000/api/scanner/report";
const String apiKey = "secret123";

// =============== PENGATURAN RUANGAN ==================
// Setiap ESP32 harus memiliki ROOM_ID yang BERBEDA.
// Sesuaikan angka ini dengan ID ruangan yang sudah didaftarkan di Web Laravel.
// Contoh: Ruang A = 1, Ruang B = 2, Ruang C = 3, Ruang D = 4
const int ROOM_ID = 2;
// =======================================================

int scanTime = 5; 
BLEScan* pBLEScan;

// Struktur untuk menyimpan Device ID dan Tipenya
struct ScannedDevice {
  String id;
  String type;
};

#include <vector>
std::vector<ScannedDevice> scannedDevices;

// Callback: Dipanggil otomatis SETIAP KALI ESP32 mendeteksi sebuah perangkat Bluetooth
class MyAdvertisedDeviceCallbacks: public BLEAdvertisedDeviceCallbacks {
    void onResult(BLEAdvertisedDevice advertisedDevice) {
      String deviceMAC = advertisedDevice.getAddress().toString().c_str();
      String deviceType = "MAC";
      String deviceID = deviceMAC;

      // Cek apakah perangkat memancarkan iBeacon
      if (advertisedDevice.haveManufacturerData()) {
        String strManufacturerData = advertisedDevice.getManufacturerData();
        int dataLen = strManufacturerData.length();

        // Standar iBeacon Manufacturer Data biasanya memiliki panjang 25 byte
        if (dataLen >= 25) {
            const uint8_t* cData = (const uint8_t*) strManufacturerData.c_str();

            // Cek Apple Company ID (0x4C 0x00) dan tipe iBeacon (0x02 0x15)
            if (cData[0] == 0x4C && cData[1] == 0x00 && cData[2] == 0x02 && cData[3] == 0x15) {
                char uuidStr[37];
                sprintf(uuidStr, "%02X%02X%02X%02X-%02X%02X-%02X%02X-%02X%02X-%02X%02X%02X%02X%02X%02X",
                        cData[4], cData[5], cData[6], cData[7],
                        cData[8], cData[9],
                        cData[10], cData[11],
                        cData[12], cData[13],
                        cData[14], cData[15], cData[16],
                        cData[17], cData[18], cData[19]);
                
                // Jika dia iBeacon, kita pakai UUID-nya sebagai ID utama, bukan MAC-nya
                deviceID = String(uuidStr);
                deviceType = "BEACON";
            }
        }
      }
      
      // Hindari duplikasi dalam 1 siklus
      bool exists = false;
      for(auto dev : scannedDevices) {
        if(dev.id == deviceID) {
          exists = true;
          break;
        }
      }

      if(!exists) {
        scannedDevices.push_back({deviceID, deviceType});
      }
    }
};

void setup() {
  Serial.begin(115200);
  delay(1000);
  
  Serial.println("\n====================================");
  Serial.println("   ESP32 BLE Scanner - Booting...");
  Serial.println("====================================");

  // WiFiManager: membuat captive portal untuk konfigurasi WiFi secara dinamis
  WiFiManager wifiManager;
  
  // Uncomment baris di bawah ini jika ingin MENGHAPUS kredensial WiFi yang tersimpan
  // agar captive portal "ESP32-BLE-Scanner" muncul kembali untuk ganti WiFi.
  wifiManager.resetSettings();

  // autoConnect akan mencoba menghubungkan ke WiFi yang tersimpan.
  // Jika gagal, akan membuat AP bernama "ESP32-BLE-Scanner" dengan captive portal.
  Serial.println("[WiFi] Mencoba menghubungkan ke WiFi tersimpan...");
  
  if (!wifiManager.autoConnect("ESP32-BLE-Scanner")) {
    Serial.println("[WiFi] GAGAL terhubung! Restarting dalam 3 detik...");
    delay(3000);
    ESP.restart();
  }
  
  // Tampilkan info koneksi WiFi
  Serial.println("[WiFi] TERHUBUNG!");
  Serial.print("[WiFi] SSID      : ");
  Serial.println(WiFi.SSID());
  Serial.print("[WiFi] IP Address: ");
  Serial.println(WiFi.localIP());
  Serial.print("[WiFi] RSSI      : ");
  Serial.print(WiFi.RSSI());
  Serial.println(" dBm");
  Serial.println("====================================");

  BLEDevice::init("");
  pBLEScan = BLEDevice::getScan();
  pBLEScan->setAdvertisedDeviceCallbacks(new MyAdvertisedDeviceCallbacks());
  pBLEScan->setActiveScan(true); 
  pBLEScan->setInterval(100);
  pBLEScan->setWindow(99);
}

void loop() {
  scannedDevices.clear();
  
  Serial.println("\n=== Memulai proses scan (" + String(scanTime) + " detik) ===");
  pBLEScan->start(scanTime, false);
  pBLEScan->clearResults(); 

  Serial.println("Total perangkat terdeteksi unik: " + String(scannedDevices.size()));

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("[WiFi] Terhubung ke: " + WiFi.SSID() + " | IP: " + WiFi.localIP().toString());
    sendDataToServer();
  } else {
    Serial.println("[WiFi] TIDAK TERHUBUNG! Data tidak dikirim.");
    Serial.println("[WiFi] Restart ESP32 untuk membuka captive portal.");
  }
  
  delay(2000); 
}

void sendDataToServer() {
  HTTPClient http;
  http.begin(serverName);
  http.addHeader("Content-Type", "application/json");

  JsonDocument doc; 
  doc["api_key"] = apiKey;
  doc["room_id"] = ROOM_ID;
  
  JsonArray deviceArray = doc["devices"].to<JsonArray>();
  
  for(auto dev : scannedDevices) {
    JsonObject obj = deviceArray.add<JsonObject>();
    obj["id"] = dev.id;
    obj["type"] = dev.type;
  }

  String jsonPayload;
  serializeJson(doc, jsonPayload);

  Serial.println("Mengirim payload: ");
  Serial.println(jsonPayload);

  int httpResponseCode = http.POST(jsonPayload);

  if (httpResponseCode > 0) {
    Serial.print("   [BERHASIL] API merespons Code: ");
    Serial.println(httpResponseCode);
  } else {
    Serial.print("   [GAGAL] Error saat mengirim POST: ");
    Serial.println(httpResponseCode);
  }
  
  http.end();
}