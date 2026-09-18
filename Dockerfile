# ใช้ PHP 8.2 (หรือปรับตามเวอร์ชัน Laravel ของคุณ เช่น 8.1 หรือ 8.3)
FROM php:8.2-fpm

# ติดตั้ง System Dependencies และ PHP Extensions ที่ Laravel ต้องใช้
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# เคลียร์แคช apt
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# ติดตั้ง PHP Extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# ติดตั้ง Composer (ตัวจัดการ package ของ PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# กำหนด Working Directory
WORKDIR /app

# คัดลอกไฟล์โปรเจกต์ทั้งหมดเข้าไป
COPY . /app

# ติดตั้ง Composer Dependencies
RUN composer install --no-dev --optimize-autoloader

# กำหนดสิทธิ์โฟลเดอร์ storage และ bootstrap/cache (ถ้ามี)
# RUN chmod -R 777 storage bootstrap/cache

# เปิด Port และรัน Laravel Server
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
