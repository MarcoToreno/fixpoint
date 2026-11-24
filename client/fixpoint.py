"""Консольний клієнт FixPoint. Достатньо стандартної бібліотеки Python."""

import argparse
import getpass
import json
import sys
from html.parser import HTMLParser
from http.cookiejar import CookieJar
from urllib.error import HTTPError, URLError
from urllib.parse import urlencode, urlsplit
from urllib.request import HTTPRedirectHandler, HTTPCookieProcessor, Request, build_opener


class ClientError(Exception):
    pass


class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, request, response, code, message, headers, new_url):
        return None


class LoginForm(HTMLParser):
    def __init__(self):
        super().__init__()
        self.csrf_token = None

    def handle_starttag(self, tag, attributes):
        fields = dict(attributes)
        if tag == "input" and fields.get("name") == "_csrf_token":
            self.csrf_token = fields.get("value")


class Client:
    def __init__(self, base_url):
        self.base_url = base_url
        self.cookies = CookieJar()
        self.opener = build_opener(HTTPCookieProcessor(self.cookies), NoRedirect())

    def request(self, path, data=None, accept="application/json"):
        headers = {"Accept": accept}
        if data is not None:
            headers["Origin"] = self.base_url
            data = urlencode(data).encode("ascii")
        request = Request(self.base_url + path, data=data, headers=headers)
        try:
            response = self.opener.open(request, timeout=8)
        except HTTPError as exception:
            response = exception
        except (URLError, TimeoutError) as exception:
            raise ClientError(f"Не вдалося під’єднатися до {self.base_url}. Перевірте, чи запущений сервер.") from exception
        with response:
            return response.status, response.headers, response.read()

    def api(self, path):
        status, headers, body = self.request(path)
        if headers.get_content_type() != "application/json":
            raise ClientError(f"HTTP {status}. Сервер повернув відповідь у форматі, відмінному від JSON.")
        try:
            payload = json.loads(body)
        except ValueError as exception:
            raise ClientError("Сервер повернув пошкоджений JSON.") from exception
        if not isinstance(payload, dict):
            raise ClientError("API повернув несподівану структуру даних.")
        if status != 200:
            raise ClientError(f"HTTP {status}. {payload.get('error', 'Помилка API.')}")
        return payload

    def admin_check(self, email, password):
        status, _, page = self.request("/login", accept="text/html")
        if status != 200:
            raise ClientError(f"Форма входу недоступна. HTTP {status}.")
        parser = LoginForm()
        parser.feed(page.decode("utf-8"))
        if not parser.csrf_token:
            raise ClientError("У формі входу відсутнє поле CSRF.")

        status, _, _ = self.request(
            "/login",
            {"_username": email, "_password": password, "_csrf_token": parser.csrf_token},
            accept="text/html",
        )
        if status != 302:
            raise ClientError(f"Не вдалося надіслати форму входу. HTTP {status}.")

        status, headers, _ = self.request("/admin", accept="text/html")
        location = urlsplit(headers.get("Location", ""))
        if status == 302 and location.path == "/login":
            raise ClientError("Вхід не виконано. Перевірте email і пароль.")
        if status == 403:
            raise ClientError("Користувач увійшов, але не має ролі ROLE_ADMIN.")
        base = urlsplit(self.base_url)
        if (
            status != 302
            or (location.netloc and (location.netloc != base.netloc or location.scheme != base.scheme))
            or location.path != "/admin/repair-service"
            or not any(True for _ in self.cookies)
        ):
            raise ClientError(f"Адмінка повернула несподівану відповідь HTTP {status}.")
        status, _, page = self.request(location.path, accept="text/html")
        if status != 200 or "Послуги" not in page.decode("utf-8"):
            raise ClientError(f"Не вдалося прочитати сторінку послуг. HTTP {status}.")
        print("Вхід підтверджено: та сама сесія відкрила список послуг EasyAdmin (HTTP 200).")


def main():
    parser = argparse.ArgumentParser(description="Каталог послуг майстерні FixPoint")
    parser.add_argument("--url", default="http://127.0.0.1:8001", help="Адреса Symfony")
    parser.add_argument("--json", action="store_true", help="Показати JSON API")
    commands = parser.add_subparsers(dest="command")
    listing = commands.add_parser("list", help="Послуги")
    listing.add_argument("--search", default="")
    listing.add_argument("--category", type=int)
    detail = commands.add_parser("get", help="Послуга за id")
    detail.add_argument("id", type=int)
    commands.add_parser("categories", help="Категорії")
    admin = commands.add_parser("admin-check", help="Перевірка входу в адмінку")
    admin.add_argument("--email")
    options = parser.parse_args()

    address = urlsplit(options.url.rstrip("/"))
    if (
        address.scheme not in {"http", "https"}
        or not address.hostname or address.username
        or address.path or address.query or address.fragment
    ):
        parser.error("--url має бути адресою сервера на зразок http://127.0.0.1:8001")
    client = Client(address.geturl())

    try:
        if options.command == "admin-check":
            if address.scheme == "http" and address.hostname not in {"localhost", "127.0.0.1", "::1"}:
                raise ClientError("Для віддаленого сервера ввімкніть HTTPS.")
            if not sys.stdin.isatty():
                raise ClientError("Запустіть вхід в інтерактивному терміналі.")
            email = options.email or input("Email адміністратора: ")
            password = getpass.getpass("Пароль: ")
            client.admin_check(email.strip(), password)
            return 0

        command = options.command or "list"
        path = "/api/services"
        if command == "get":
            if options.id < 1:
                parser.error("id має бути додатним числом")
            path += f"/{options.id}"
        elif command == "categories":
            path = "/api/categories"
        else:
            filters = {"q": getattr(options, "search", "")}
            category = getattr(options, "category", None)
            if category is not None:
                if category < 1:
                    parser.error("категорія має бути додатним числом")
                filters["category"] = category
            path += "?" + urlencode(filters)

        payload = client.api(path)
        if options.json:
            print(json.dumps(payload, ensure_ascii=False, indent=2))
        elif command == "categories":
            for category in payload["data"]:
                print(f"[{category['id']}] {category['name']} — {category['description']}")
        else:
            items = [payload["data"]] if command == "get" else payload["data"]
            print(f"Знайдено послуг: {len(items)}")
            for service in items:
                status = "доступно" if service["available"] else "тимчасово недоступно"
                category = service["category"]["name"]
                print(f"[{service['id']}] {service['title']} — {category}")
                print(f"    {service['price_uah']} грн · {service['duration_minutes']} хв · {status}")
                if command == "get":
                    print(f"    {service['description']}")
    except (ClientError, KeyError, TypeError, OSError) as exception:
        print(f"Помилка. {exception}", file=sys.stderr)
        return 1
    except (EOFError, KeyboardInterrupt):
        print("\nКоманду перервано.", file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
