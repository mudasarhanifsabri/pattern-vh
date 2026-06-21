@echo off
cd /d "%~dp0"
php artisan queue:work database --queue=default --tries=3 --timeout=120
