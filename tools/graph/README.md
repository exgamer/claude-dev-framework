# Граф правил

Граф знаний по правилам фреймворка (`graphify`): файлы, темы, отдельные правила с конкретными значениями и связи между ними — ссылки файл → файл, «то же правило другими словами» (`semantically_similar_to`), кандидаты в противоречия (`conflicts_with`). Нужен `/dev-rule`: найти все места темы до правки и не потерять правило при правке. Граф — подсказка: связи выделены агентами по тексту, кандидаты проверяются по файлам.

В корпусе: `core`, `approaches`, `regulations`, `architecture`, `stacks`, `skills`, `agents` — только `.md`, без `official/`, `reference/` и `core/decisions-log.md`.

## Файлы

| Что | Где |
|---|---|
| Скрипт | `graph.py` — `sync` / `prepare` / `finish` |
| Промпт агента извлечения | `extract-prompt.md` |
| Промпт проверки кандидатов в противоречия | `verify-conflicts.md` |
| Промпт независимой проверки правки | `diff-review.md` |
| Граф, отчёт, карта, сводка | `graphify-out/graph.json`, `GRAPH_REPORT.md`, `graph.html`, `stats.md` (пишет `finish`) |
| Кэш извлечения (по содержимому файла) | `graphify-out/cache/` и `full-hashes.json` — коммитятся: обновление разбирает только изменённые файлы (включая правку только frontmatter) |
| Корпус (копия правил) | `corpus/` — не коммитится, собирает `sync` |

## Обновление после правки

Все команды — из корня **репозитория-источника** (`cd "$(readlink ~/.claude/dev-framework)"`), не из проекта и не в копии фреймворка (`core/rules-evolution.md`, «Где правится фреймворк»).

```bash
PY=$(head -1 "$(which graphify)" | tr -d '#!')   # интерпретатор, где стоит graphify (pip install graphifyy)
$PY tools/graph/graph.py sync
$PY tools/graph/graph.py prepare                 # печатает чанки: «.chunk_N.txt → .graphify_chunk_NN.json»
```

На каждый чанк — агент `general-purpose` (параллельно, одним сообщением): «Прочитай `tools/graph/extract-prompt.md` и выполни. Список файлов: `<.chunk_N.txt>`. Результат — `<.graphify_chunk_NN.json>`». Затем:

```bash
$PY tools/graph/graph.py finish                  # граф + сводка stats.md; временные чанки удаляются
```

Нечего извлекать (`to extract: 0`) — сразу `finish`. Названия групп берутся из `graphify-out/labels.json`; группы поменялись — переименовать там вручную.

## Вопрос к графу

Из корня репозитория-источника. Где ещё говорится о теме: узлы `rule_*` и `topic_*` в `graph.json`, у которых `label` содержит слово темы, и их соседи (`source_file` — файл, `source_location` — раздел), например:

```bash
$PY -c "
import json,sys; g=json.load(open('tools/graph/graphify-out/graph.json')); t=sys.argv[1].lower()
ids={n['id'] for n in g['nodes'] if t in n.get('label','').lower()}
nb={l['target'] if l['source'] in ids else l['source'] for l in g['links'] if l['source'] in ids or l['target'] in ids}
for n in g['nodes']:
    if n['id'] in ids|nb and n['id'].startswith('rule_'): print(n['source_file'].replace('corpus/',''), '§', n.get('source_location'), '—', n['label'])
" "транзакц"
```

## Полная сверка

Раз в месяц или после серии правок: обновить граф, взять «Кандидаты в противоречия» из `graphify-out/stats.md`, проверить их агентами по `verify-conflicts.md` (по области: процесс/core, Go, PHP), реальные — разбирать в `/dev-rule` по одному. В `core/decisions-log.md` — строка со словами «Сверка правил по графу» и датой (по ней `/dev-rule` считает, когда была прошлая).
