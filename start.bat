@echo off
REM ============================================================
REM  PickleBall: start the app with everything up to date.
REM  Double-click this file, or run:  start.bat
REM  (VS Code also runs it automatically when you open the folder,
REM   with --no-browser so it doesn't open a new tab every time.)
REM ============================================================
title PickleBall
cd /d "%~dp0backend"

if not exist vendor\ (
    echo Installing PHP packages...
    call composer install || goto :error
)
if not exist node_modules\ (
    echo Installing Node packages...
    call npm install || goto :error
)

echo.
echo Updating the database...
php artisan migrate --force || goto :error

echo.
echo Building styles...
call npm run build || goto :error
php artisan view:clear >nul

if /i not "%~1"=="--no-browser" (
    REM Open the browser once the server has had a few seconds to start.
    start "" /b powershell -NoProfile -WindowStyle Hidden -Command "Start-Sleep -Seconds 5; Start-Process 'http://127.0.0.1:8000'"
)

echo.
echo Starting PickleBall at http://127.0.0.1:8000  (press Ctrl+C to stop)
echo.
php artisan dev
goto :eof

:error
echo.
echo Something went wrong above. Fix it and run start.bat again.
pause
exit /b 1
