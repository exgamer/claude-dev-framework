
# Скилл: qa-writer

Конвенции и структура для UI-автотестов на Python + Playwright + pytest (Page Object Model).

При вызове `/qa-writer` — применяй все правила ниже к текущему проекту.

---

## Структура проекта

```
project/
├── conftest.py               # Фикстуры: base_url, configure_page (таймауты)
├── pytest.ini                # Конфиг: браузер, скриншоты, трейсы, папка артефактов
├── requirements.txt
├── .env                      # Переменные окружения (не коммитить)
├── config/settings.py        # Все настройки из .env
├── data/constants.py         # URL-пути и таймауты — никаких магических значений
├── framework/base_page.py    # BasePage — базовый класс для всех page objects
├── components/               # Переиспользуемые UI-элементы
├── pages/                    # Page objects — один файл на страницу
└── tests/
    ├── smoke/                # @pytest.mark.smoke
    └── regression/           # @pytest.mark.regression
```

Каждая папка `tests/smoke/`, `tests/regression/`, `tests/` должна содержать `__init__.py` —
иначе pytest не различает одноимённые файлы в разных папках.

---

## Правила компонентов (`components/`)

- Один файл = один компонент
- Компоненты — элементы шапки/подвала или встречающиеся на нескольких страницах
- Локаторы в `__init__`, действия в методах
- **Никаких ассертов** — только действия и ожидания
- Не смешивать логику разных компонентов

```python
from playwright.sync_api import Page, Locator

class MyComponent:
    def __init__(self, page: Page) -> None:
        self.page = page
        self.button: Locator = page.locator("#some-id")

    def click(self) -> None:
        self.button.click()
```

---

## Правила страниц (`pages/`)

- Один файл = одна страница, наследует `BasePage`
- `open()` всегда с `wait_until="domcontentloaded"`
- **Никаких ассертов** — только навигация и действия

```python
from playwright.sync_api import Page, Locator
from framework.base_page import BasePage

class SomePage(BasePage):
    def __init__(self, page: Page) -> None:
        super().__init__(page)
        self.element: Locator = page.locator("#some-id")

    def open(self) -> "SomePage":
        self.page.goto("/path", wait_until="domcontentloaded")
        return self
```

---

## Правила тестов (`tests/`)

- Каждый тест **самодостаточен** — содержит все шаги включая авторизацию
- **Не зависит** от других тестов
- Ассерты только в тестах через `expect()`
- Шаги нумеруются в docstring и комментариях
- Тест оставляет систему в исходном состоянии (очистка данных)

```python
import pytest
from playwright.sync_api import Page, expect

from config.settings import settings
from data.constants import SOME_PATH
from pages.main_page import MainPage
from pages.some_page import SomePage


@pytest.mark.smoke
def test_subject_condition_expected_result(page: Page) -> None:
    """
    Описание теста.

    Шаги:
      1. ...
      2. ...
    """
    # Шаг 1
    main = MainPage(page).open()

    # Шаг 2
    some_page = SomePage(page)
    expect(page).to_have_url(f"{settings.base_url}{SOME_PATH}")
```

---

## Именование тестов

Паттерн: `test_<объект>_<условие>_<ожидаемый результат>`

```python
# Хорошо
def test_search_bar_with_query_navigates_to_search_page()
def test_auth_modal_submit_disabled_without_phone()
def test_logout_redirects_to_home_with_login_button()

# Плохо
def test_search()
def test_auth_2()
```

---

## Селекторы — приоритет

| Приоритет | Тип | Пример |
|---|---|---|
| ✅ 1 | `id` | `#header_profile` |
| ✅ 2 | `data-qa` / `data-testid` | `[data-qa="submit-btn"]` |
| ⚠️ 3 | Уникальный CSS-класс | `.cabinet-menu-logout` |
| ⚠️ 4 | Паттерн `id` | `[id^='card_']` |
| ❌ 5 | Утилитарные классы | `.ant-btn-primary` |
| ❌ 6 | Текст | `button:has-text('Войти')` |
| ❌ 7 | Структурные | `div > li:nth-child(2)` |
| ❌ 8 | `data-v-*` | `[data-v-6dc69163]` |

**Всегда напоминать:** если `id` нет — просить фронтенд добавить `id` или `data-qa`.

Если используется селектор ниже ⚠️3 — оставлять комментарий:
```python
# TODO: попросить добавить id — сейчас временное решение
self.button: Locator = page.locator("button:visible").first
```

---

## Ожидания после действий — обязательно

После **любого** клика или перехода явно подтверждать что действие завершилось.

| Действие | Проверка |
|---|---|
| `page.goto(path)` | `wait_until="domcontentloaded"` + ключевой элемент |
| Клик открывает модалку | `modal.wait_for(state="visible")` |
| Клик закрывает модалку | `modal.wait_for(state="hidden")` |
| Клик → навигация | `expect(page).to_have_url(...)` |
| Клик → форма | `wait_for_load_state("domcontentloaded")` или элемент результата |
| Клик → изменение состояния | `expect(element).to_contain_text(...)` |

---

## `expect()` вместо `assert`

```python
# Правильно — есть retry до таймаута
expect(page).to_have_url(...)
expect(element).to_be_visible()

# Неправильно — падает мгновенно без retry
assert page.url == "..."
assert element.is_visible()
```

---

## Никогда `time.sleep()`

```python
# Запрещено
time.sleep(2)

# Правильно
element.wait_for(state="visible")
page.wait_for_load_state("domcontentloaded")
page.wait_for_function("() => /* условие */")
```

---

## Опциональные модалки

`try/except` только на ожидании появления — ошибки внутри не глотать:

```python
def handle_if_visible(self, timeout: int = 3000) -> None:
    try:
        self.container.wait_for(state="visible", timeout=timeout)
    except PlaywrightTimeoutError:
        return  # не появилась — ожидаемо

    # появилась — ошибки здесь = реальный сбой
    self.button.click()
    self.container.wait_for(state="hidden")
```

---

## Мягкие ассерты

Когда нужно проверить несколько независимых вещей — `expect.soft()`:

```python
expect.soft(page).to_have_url(...)
expect.soft(element).to_be_visible()
expect.soft(button).to_be_enabled()
# pytest соберёт все ошибки и покажет разом
```

---

## Антипаттерны

| Запрещено | Почему | Как правильно |
|---|---|---|
| `time.sleep(N)` | Скрывает проблему | Playwright-ожидания |
| `assert x.is_visible()` | Нет retry | `expect(x).to_be_visible()` |
| Ассерты в компонентах/страницах | Нарушает SRP | Только в тестах |
| Зависимость тестов | Один упал — цепочка сломана | Каждый тест самодостаточен |
| Хардкод URL/текстов | Меняется везде | `data/constants.py` |
| `try/except Exception` на всё | Глотает реальные ошибки | Ловить конкретные исключения |
| `data-v-*` селекторы | Меняются при каждой сборке | `id` или `data-qa` |

---

## Чек-лист нового теста

1. Проверить — есть ли уже нужный компонент или страница
2. Новый компонент/страница — отдельный файл
3. Авторизацию писать явно в теле теста
4. URL-пути только из `data/constants.py`
5. Smoke или regression — в правильную папку
6. Нумеровать шаги в docstring
7. `expect()` — не `assert`
8. Никакого `time.sleep()`
9. После каждого клика/перехода — явная проверка
10. Ненадёжный селектор — оставить `# TODO` с просьбой добавить `id`
11. Тест оставляет систему в исходном состоянии
