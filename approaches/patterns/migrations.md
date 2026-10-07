# Миграции БД

- Одна миграция — одно изменение схемы; имя — что делает (`add_grace_minutes_to_tariffs_table`, `create_fiscal_receipts_table`).
- Go: gormigrate, реестр `internal/migrations/registry.go`, применяется отдельным `cmd/console`, не kernel-ом при старте.
- PHP: `Infrastructure/Postgres/{D}/Providers/database/migrations/`, таблицы в схеме подпроекта (`parking.tariffs`).
- Новый `NOT NULL` / новое значение enum / смена типа — сначала выяснить, что лежит в колонке на stage/prod; нужен default или backfill.
- Миграция данных (шифрование кредов, перенос) — отдельной миграцией, обратимой где возможно.
- Удаление колонки/таблицы, которую читает код, — в два релиза: сначала код перестаёт читать, потом миграция.
- Изменение схемы, затрагивающее другие сервисы, — согласование (`regulations/technical-approval.md`).
