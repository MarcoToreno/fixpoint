# FixPoint — каталог послуг майстерні

Атестація № 2 з дисципліни «Серверні вебтехнології». FixPoint показує послуги з ремонту техніки, ціни, тривалість і доступність. Адміністратор керує каталогом через EasyAdmin. Консольний Python-клієнт читає дані через JSON API.

## Локальний запуск після клонування

Потрібні PHP 8.4+ із розширеннями PDO SQLite та mbstring, Composer 2, Symfony CLI і Python 3.10+. Для встановлення пакетів PHP також має підтримувати HTTPS. Symfony 8.1 і EasyAdmin 5 зафіксовані у composer.lock. SQLite зберігає файл у var/ і не потребує окремого сервера БД.

У корені репозиторію виконай по черзі.

~~~bash
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:demo:load
php bin/console app:admin:create
symfony serve --no-tls --port=8001
~~~

Команда створення адміністратора запитає email і двічі запросить пароль прихованим вводом. Обери власний пароль від 12 символів. Вхід працює за email і хешем пароля у SQLite. Для локальної розробки за потреби задай власний APP_SECRET у .env.local. Зберігати .env.local у Git не потрібно.

Після запуску відкрий [каталог](http://127.0.0.1:8001/), [сторінку послуги](http://127.0.0.1:8001/services/1) та [адмінку](http://127.0.0.1:8001/admin). Без входу адмінка відкриває форму за адресою /login. Увійди email і паролем, які були введені в команді. Повторний запуск app:demo:load зберігає наявні записи, якщо в базі вже є категорії.

### Запуск на навчальному ПК з Windows

Якщо є підготовлена локальна копія, запусти windows\start-server.cmd. Він використовує PHP із попередньої роботи book-api або системний PHP. Демо-дані вже створено міграцією та командою завантаження. Для створення власного адміністратора в PowerShell (у корені FixPoint) виконай:

~~~powershell
.\windows\console.cmd app:admin:create
.\windows\client.cmd list
~~~

Щоб запустити сервер через підготовлену Symfony CLI, виконай `.\windows\symfony.cmd serve --no-tls --port=8001` замість запуску `start-server.cmd`.

windows\client.cmd також підтримує параметри з прикладів нижче. Якщо порт 8001 зайнятий, можна запустити Symfony на іншому порту і передати адресу через --url. Symfony CLI можна встановити за [офіційною інструкцією](https://symfony.com/download).

## Маршрути та клієнт

| Запит | Результат |
|---|---|
| GET / | HTML-каталог із пошуком та фільтром |
| GET /services/{id} | HTML-сторінка однієї послуги |
| GET /api/services | JSON-список у полі data і кількість у count |
| GET /api/services?q=акумулятор&category=2 | Пошук за назвою плюс фільтр за ID категорії |
| GET /api/services/{id} | JSON однієї послуги або HTTP 404 |
| GET /api/categories | JSON-список категорій |
| GET /admin | EasyAdmin з роллю ROLE_ADMIN |

Пошук працює без урахування регістру, зокрема для українських назв. Щоб дізнатися доступні ID категорій, виконай команду categories. Неправильний параметр фільтра повертає HTTP 400 з поясненням у полі error.

~~~bash
python client/fixpoint.py list
python client/fixpoint.py list --search "акумулятор"
python client/fixpoint.py list --category 2
python client/fixpoint.py get 1
python client/fixpoint.py categories
python client/fixpoint.py --json list
python client/fixpoint.py admin-check
~~~

Команда admin-check завантажує форму входу, читає її CSRF-поле, надсилає пошту й пароль із заголовком Origin і зберігає cookie в пам’яті. Далі та сама сесія відкриває список послуг EasyAdmin. Пароль вводиться приховано. Для локального HTTP використовуй 127.0.0.1 або localhost.

## Модель даних

- Category — назва й опис категорії. Пов'язана з багатьма послугами через OneToMany.
- RepairService — назва, опис, ціна в гривнях, тривалість у хвилинах і доступність. Кожна послуга має одну категорію через ManyToOne та зовнішній ключ category_id. Категорію з послугами можна видалити після їх перенесення або видалення.
- User — email, ролі та хеш пароля. Сутність створено make:user, форму входу — make:security:form-login.

Doctrine Migration створює всі три таблиці. Схему можна перевірити командою php bin/console doctrine:schema:validate. Публічний каталог та API використовують один запит у RepairServiceRepository. Він використовує параметри QueryBuilder і підвантажує категорію одним запитом.

## Перевірки та захист

~~~bash
php bin/phpunit
php bin/console lint:twig templates
php bin/console lint:yaml config
php bin/console doctrine:schema:validate
~~~

Функціональні тести використовують окремі тимчасові SQLite-бази. Вони запускають міграцію, перевіряють HTML та JSON, вхід, ролі, обидва CRUD і валідацію форми.

На захисті відкрий каталог і картку послуги. Покажи, як /admin переадресовує до входу. Після входу створи категорію й послугу, відредагуй ціну та отримай той самий запис командою Python. За потреби покажи admin-check і файли міграції, сутностей та security.yaml.

## Публікація

Створи порожній публічний репозиторій GitHub або GitLab. У корені цієї локальної Git-копії виконай, замінивши USER своїм логіном.

~~~bash
git log --oneline
git remote add origin https://github.com/USER/fixpoint.git
git push -u origin main
~~~

Push зберігає історію комітів. У Git відстежуються composer.lock, symfony.lock, міграції та код. Локальні .env.local, .tools, vendor, var і база з обліковими записами ігноруються.

У Classroom прикріпи посилання на публічний репозиторій і [конспект лекцій у PDF](docs/lecture-notes.pdf).
