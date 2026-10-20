# Кэш

Форма — как в эталоне `stacks/go/examples/tariff` (`domains/billing/tariff/service.go`: cache-aside inline); автор требует именно её, придуманные «резолверы» с фоновым обновлением отклонялись.

- В домене **два интерфейса**: `Repository` (БД) и `CacheRepository` (Redis) — оба тупые: `Get/Set/Invalidate<X>`, без решений.
- **Cache-aside — в workflow/сервисе-потребителе**, inline: попробовать кэш → промах → БД → записать в кэш. Не в репозитории и не в отдельном классе.
- Промах кэша — `(nil, nil)`, не ошибка. Ошибка Redis не роняет сценарий: уходит в трекер (`errorreporter.CaptureSoft`), сценарий идёт в БД.
- TTL — константа в файле кэш-репозитория (`2*time.Hour`). Свежесть — TTL + ленивое заполнение. **Фоновых пулеров/горутин обновления нет.**
- Инвалидация — при записи, в том же сценарии, что меняет данные (сервис `InvalidateXCache`).
- Go: `gosdk-redis-core` `NewHelper[T]` (`SetStruct`/`GetStruct`), удаление `Unlink`. PHP: `getOrSet` без тегов, `SmartCache` с тегами (`stacks/php/mps-core/rules.md`).
- Ключ — `{service}:{entity}:{id}` или `{service}:{entity}:{поле1}:{поле2}` для составного; префикс сервиса обязателен (общий Redis), все параметры, от которых зависит значение, — в ключе (`performance.md`, `cache-key-incomplete`). Go — константа префикса в файле кэш-репозитория, PHP — по версии `mps/utils`: ≥ 2.6.4 — `KeyHelper::generate()` (`getCacheKeyByParts` там `@deprecated`, `stacks/php/mps-core/rules.md`); < 2.6.4 — `CacheManager::getCacheKeyByParts()` с частями из enum ключей (так эталон — utils 2.6.2, `stacks/php/examples/parking_app/Infrastructure/Cache/Billing/Tariffs/Repositories/TariffCacheRepository.php`).
- Секреты (ключи, JWT-секреты, креды) в Redis не кладутся.
