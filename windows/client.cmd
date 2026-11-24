@echo off
setlocal
chcp 65001 >nul
cd /d "%~dp0.."
if defined FIXPOINT_PYTHON goto selected_python
where python >nul 2>&1
if not errorlevel 1 goto python_path
where py >nul 2>&1
if not errorlevel 1 goto py_launcher
set "FIXPOINT_PYTHON=%USERPROFILE%\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe"
if exist "%FIXPOINT_PYTHON%" goto selected_python
if exist ".tools\python-path.txt" for /f "usebackq delims=" %%i in (".tools\python-path.txt") do set "FIXPOINT_PYTHON=%%i"
if exist "%FIXPOINT_PYTHON%" goto selected_python
echo Python 3.10+ was not found. See README.md.
exit /b 1
:python_path
python -X utf8 client\fixpoint.py %*
exit /b %ERRORLEVEL%
:py_launcher
py -3 -X utf8 client\fixpoint.py %*
exit /b %ERRORLEVEL%
:selected_python
"%FIXPOINT_PYTHON%" -X utf8 client\fixpoint.py %*
exit /b %ERRORLEVEL%
