@echo off
setlocal
cd /d "%~dp0.."
if exist "..\..\book-api\.tools\php\php.exe" (
    set "PATH=%CD%\..\..\book-api\.tools\php;%PATH%"
    set "PHP_INI_SCAN_DIR=%CD%\.tools\php-extra"
)
if exist ".tools\symfony.exe" (
    ".tools\symfony.exe" %*
) else (
    symfony %*
)
exit /b %ERRORLEVEL%
