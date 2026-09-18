@echo off
chcp 65001 >nul
title ROOC AUCTION CHECKLIST FUNRAIRANKGOLD (DADDY)

echo ======================================================================
echo   🏆 ROOC AUCTION CHECKLIST FUNRAIRANKGOLD (DADDY) 🏆
echo ======================================================================
echo.
echo [*] กำลังตั้งค่าระบบและเริ่มเซิร์ฟเวอร์...
set "PATH=%PATH%;C:\Users\Plai\AppData\Local\Programs\php"

echo [*] ตรวจสอบความพร้อมของ PHP...
php -v >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [!] ไม่พบ PHP กรุณาตรวจสอบว่ามี PHP อยู่ในเครื่องหรือไม่
    pause
    exit /b 1
)

echo [*] ระบบพร้อมทำงาน!
echo.
echo --------------------------------------------------------
echo   🌐 เว็บไซต์: http://127.0.0.1:8000
echo --------------------------------------------------------
echo.
echo [*] กำลังเปิดเว็บเบราว์เซอร์...
start "" "http://127.0.0.1:8000"

echo [*] กำลังเปิดเซิร์ฟเวอร์ (กด Ctrl+C เพื่อหยุดการทำงาน)...
php artisan serve --host=127.0.0.1 --port=8000
pause
