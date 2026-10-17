You are a graphify extraction subagent. Read EVERY file listed in your chunk list file and extract a knowledge graph fragment. The corpus is the rule set of an AI dev framework ("dev-framework", written in Russian): principles, regulations, process approaches, stack rules for Go and PHP, skills and agent definitions. Read files fully with the Read tool. Do not modify any file except your output file.

Write the result with the Write tool to the output path you were given, as ONLY valid JSON (no markdown fences, no preamble). Then reply with one line: node/edge counts.

## Purpose of this graph
The graph will be used to find (1) the same topic described in several files (duplication risk), (2) contradicting statements between files, (3) orphan files nobody references, (4) overloaded hub files. So extraction must make cross-file overlap visible.

## Node kinds
1. FILE node for every file: id `file_<relative path normalized>` where relative path is from `corpus/` (e.g. `corpus/core/principles.md` → `file_core_principles_md`), label = relative path (e.g. `core/principles.md`), file_type "document".
2. TOPIC nodes — shared across all chunks, so ALWAYS use ids from this fixed vocabulary when the topic fits (id = `topic_<name>`; label = the name in Russian, short):
   transactions, concurrency, idempotency, outbox, cache, migrations, payments, performance, async_jobs, git_commits, branches, mr_review, review_findings, review_tags, security, authorization, personal_data, secrets, webhooks, files_upload, unit_tests, integration_tests, ui_tests, swagger, rest_api, http_entrypoint, rabbit, redis, postgres, external_integrations, exceptions_errors, logging, comments_docblocks, human_text_style, language_ru_en, naming, layers, domain_model, repository, service_layer, workflow, commands, queries, data_objects, enums, validation_rules, di_container, helpers_reuse, sdk_capabilities, rules_log, decisions, rules_evolution, role_chain, subagents, orchestrator_dev, planning_waves, requirements_analysis, technical_design, impact_analysis, legacy_refactor, stack_migration, threat_model, db_actions_confirmation, commit_push_confirmation, technical_approval, contracts, entity_regulation, cross_domain, self_check, code_navigation, ai_attribution_ban, agent_names, scope_control, gaps_and_disputes, mr_threads, test_cases_qa, project_local_rules, model_choice, usage_tracking
   Only invent a new topic id (`topic_<snake_case_english>`) if nothing in the list fits; keep new ones few.
3. RULE nodes — concrete normative statements (must/never/only/limit numbers) that matter for consistency: id `rule_<file stem>_<short_english_slug>`, label = short Russian paraphrase INCLUDING concrete values (numbers, limits, names), source_location = section heading or line hint. Extract the important ones, ~3–10 per file (more for big rule files, fewer for tiny ones). Prefer statements likely to be repeated or contradicted elsewhere (limits like «до 3 агентов», «до 70 символов», who may commit, what reads what, which file is authoritative).

## Edges
- file → topic: relation `references`, EXTRACTED, 1.0 (file substantially covers topic). Mark weight 2.0 if the file is the primary/authoritative place for the topic.
- rule → file: relation `defined_in`, EXTRACTED, 1.0.
- rule → topic: relation `about`, EXTRACTED, 1.0.
- file → file: relation `cites` when a file explicitly links/mentions another file of the corpus (e.g. «по `core/style.md`», `{DF}/approaches/process/review.md`). EXTRACTED 1.0. Target id must follow the file id format even if that file is not in your chunk. This is very important — extract ALL explicit file references.
- rule ↔ rule in your chunk: `semantically_similar_to` (INFERRED, 0.6–0.95) when two files state the same rule (duplication); `conflicts_with` (AMBIGUOUS 0.1–0.3 or INFERRED 0.6–0.9) when they seem to contradict (different numbers, different owner, opposite instruction). Be careful and concrete.
- rationale nodes optional: `rationale_for` for important WHY explanations.

Hyperedges: max 3, only if clearly useful.

confidence_score is REQUIRED on every edge; EXTRACTED = 1.0; never 0.5 as default.
Node ID format: lowercase, only [a-z0-9_].

Output exactly this JSON shape:
{"nodes":[{"id":"...","label":"...","file_type":"document","source_file":"corpus/relative/path.md","source_location":null,"source_url":null,"captured_at":null,"author":null,"contributor":null}],"edges":[{"source":"...","target":"...","relation":"...","confidence":"EXTRACTED|INFERRED|AMBIGUOUS","confidence_score":1.0,"source_file":"corpus/relative/path.md","source_location":null,"weight":1.0}],"hyperedges":[],"input_tokens":0,"output_tokens":0}

Topic nodes: include each topic you use as a node too (source_file = the first file in your chunk that uses it).
