# Otobüs Bileti Satın Alma Platformu

Modern ve kullanıcı dostu otobüs bileti satış platformu.

##  Özellikler

### Genel Kullanıcı
-  Sefer arama ve listeleme
-  Koltuk seçimi
-  İndirim kuponu kullanma
-  Bilet satın alma
-  Bilet iptal etme (1 saat kuralı)
-  PDF bilet indirme
-  Profil ve bakiye yönetimi

### Admin
-  Firma yönetimi
-  Kullanıcı yönetimi
-  Kupon yönetimi
-  İstatistikler ve raporlar

### Firma Admin
-  Sefer eklemedüzenlemesilme
-  Sefer raporları
-  Satış istatistikleri

## 🛠️ Teknolojiler

- Backend PHP 8.1
- Veritabanı SQLite
- Frontend HTML5, CSS3, JavaScript
- Container Docker

## 📦 Kurulum

### Docker ile Kurulum (Önerilen)
```bash
# Projeyi klonla
git clone httpsgithub.comkullaniciadibilet-satin-alma.git
cd bilet-satin-alma

# Docker container'ı başlat
docker-compose up -d

# Tarayıcıda aç
localhost:8080
```

### Manuel Kurulum
```bash
# XAMPPWAMP kurulu olmalı

# Projeyi htdocs klasörüne kopyala
cp -r bilet-satin-alma Cxampphtdocs

# Tarayıcıda kurulum scriptini çalıştır
localhostbilet-satin-alma/install.php

# Ana sayfayı aç
localhostbilet-satin-alma/index.php
```

## 👤 Test Hesapları

 Rol  Kullanıcı Adı  Şifre 
---------------------------
 Admin  admin  Dejavu123 
 Firma Admin  metro_admin  123456 


## 📁 Proje Yapısı
```
bilet-satin-alma
├── admin              # Admin paneli
├── assets             # CSS, JS dosyaları
├── auth               # GirişKayıt
├── config             # Veritabanı bağlantısı
├── database           # SQLite veritabanı
├── firm-admin         # Firma admin paneli
├── includes           # Header, Footer, Functions
├── user               # Kullanıcı sayfaları
├── index.php           # Ana sayfa
├── search.php          # Sefer arama
├── trip-details.php    # Sefer detayları
├── install.php         # Kurulum scripti
├── Dockerfile          # Docker yapılandırması
└── docker-compose.yml  # Docker Compose
```

## 🎫 Kupon Kodları

- `SİBERVATAN` - %25 indirim
- `YAVUZLAR` - %50 indirim
- `ALTAYLAR` - %90 indirim

## 👨‍💻 Geliştirici

[EMRAH TUSUN]
