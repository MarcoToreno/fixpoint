@echo off
setlocal
cd /d "%~dp0.."
set "PHP_EXE=php"
if defined FIXPOINT_PHP set "PHP_EXE=%FIXPOINT_PHP%"
if not defined FIXPOINT_PHP if exist "..\..\book-api\.tools\php\php.exe" set "PHP_EXE=..\..\book-api\.tools\php\php.exe"
if not exist "vendor\autoload.php" (
    echo Run composer install first. See README.md.
    pause
    exit /b 1
)
echo FixPoint is available at http://127.0.0.1:8001/
echo Keep this console open. Ctrl+C stops the server.
"%PHP_EXE%" -d extension=pdo_sqlite -S 127.0.0.1:8001 -t public
pause
