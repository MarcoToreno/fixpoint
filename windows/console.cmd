@echo off
setlocal
cd /d "%~dp0.."
set "PHP_EXE=php"
if defined FIXPOINT_PHP set "PHP_EXE=%FIXPOINT_PHP%"
if not defined FIXPOINT_PHP if exist "..\..\book-api\.tools\php\php.exe" set "PHP_EXE=..\..\book-api\.tools\php\php.exe"
"%PHP_EXE%" -d extension=pdo_sqlite bin\console %*
exit /b %ERRORLEVEL%
