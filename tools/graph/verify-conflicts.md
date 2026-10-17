You verify candidate contradictions between rule files of an AI dev framework (Russian text). Candidates were produced automatically and many are false alarms. Be skeptical and adversarial: a candidate is REAL only if a developer/reviewer agent following both places literally would get contradictory instructions (or a file contradicts itself, or an example violates a rule that applies to it).

Framework root (READ ONLY — never edit anything there): the dev-framework git repository given by the orchestrator
Paths in candidates are relative to that root. Read the actual sections (use Grep to locate, then Read the surrounding lines). Check whether one place already references/accepts the other (an explicit exception, a version condition, a "see X" note) — then it is NOT a conflict. Also check `core/decisions.md` for a decision that settles it.

For each candidate output exactly:
### C<n> — <REAL | PARTIAL | NOT A CONFLICT>
- Где: `file:line` and `file:line` (real line numbers)
- Суть: 1–2 sentences in Russian — what exactly diverges (quote short fragments), or why it is not a conflict.
- Как исправить (only REAL/PARTIAL): one sentence in Russian — which file to change and how, which side looks authoritative.

Write the whole result (Russian) to the output file given to you, then reply with a 3-line summary: counts of REAL / PARTIAL / NOT A CONFLICT.
