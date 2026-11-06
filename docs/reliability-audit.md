# Reliability audit — 2026-11-06

## Baseline and inventory (recorded before application edits)

Clean bound worktree at current `origin/master`, `298ef92bb7163e421c935a1a25f50572ede1882b`.
Read AGENTS.md, all application/source/test files, CSS, Composer manifest/lock,
workflows, README and prior decision log. This is a behavior audit, not a
second structural cleanup. No local tests, builds, benchmarks or install hooks.

Public interfaces: static `index.html` posts `email` (historical phone field
name) and `message` to `smtpgmail.php`; `ReminderDelivery::send` returns status,
HTTP code, public message and internal gateway outcomes; `ReminderTransport`
returns acceptance booleans or throws. Environment configuration supplies SMTP
credentials and sender. PHPMailer 7.1.1 owns SMTP and MIME serialization.

Flow: phone/message validation → lazy environment/dependency validation → one
transport → five isolated SMTP envelopes → aggregate result → escaped HTML.
There is no database, account/tenant model, scheduling, transaction, durable
queue or background worker. Request-local state needs no cross-request locks.
No JavaScript/TypeScript sources, package manifest or JS subproject exist;
requested tester-army/e2e integration is inapplicable to this PHP-only repo.
Existing PHP tests cover real PHPMailer MIME/protocol behavior with an in-memory
SMTP adapter and subprocess HTTP responses, without credentials or network SMTP.

## Finding F1 — ambiguous SMTP failures reported as definite non-delivery

Priority: medium; confidence: high. `src/ReminderDelivery.php`, final all-failed
report. Trigger: a gateway accepts SMTP DATA but its acknowledgement is lost,
causing the transport to throw; all other gateways reject or also fail. The
transport contract cannot distinguish definite rejection from an unconfirmed
acceptance. Actual response says “No gateway accepted” and invites a retry
without the duplicate warning used for partial success. Expected: report that
no acceptance was *confirmed*, retain failed/502 and the outcomes API, and warn
that retries may duplicate messages. This does not assert that any particular
provider experienced an outage; it is a reachable failure of the response's
claim under the existing Throwable/boolean contract. No retry or delivery
semantics are added.

Regression intent: an exception representing lost acknowledgement plus four
rejections must produce failed/502, attempt all gateways, and describe uncertainty
and duplicate risk. A genuine rejection-only outcome also cannot establish that
*every* false outcome was a definitive rejection. Existing successful and partial
acceptance contracts remain unchanged.

## Reviewed paths and rejected hypotheses

- Phone regex rejects arrays, injected recipient syntax, extra digits and foreign
  country codes before constructing SMTP; normalization agrees with the grammar.
- Missing/invalid messages and unavailable dependencies/configuration fail closed.
  `0` is deliberately allowed through PHPMailer's empty-body semantics.
- Recipient/CC/BCC and attachments clear before each send; recipients also clear in
  finally. Later gateways run after exceptions. Bodies remain literal UTF-8 text.
- Result HTML contains only fixed escaped messages, no recipients, credentials,
  user markup or SMTP diagnostics; responses disable caching and reject GET.
- No automatic retries exist. SMTP acceptance does not promise phone delivery;
  historic gateways are documented provider-dependent behavior, not a proven bug.
- Authentication/rate limiting are explicitly absent and public deployment requires
  review per README. No deployment or new account/access policy is in scope.
- Timeout is configured; total latency and lost acknowledgements cannot establish
  exactly-once delivery. No speculative persistence/idempotency rewrite is justified.
- Suspected invalid workflow `parallel` syntax is rejected: current GitHub docs
  support it and baseline run 42276461553 actually ran both nested steps.
- Locked dependencies and pinned Actions stay unchanged; no build pipeline exists
  beyond Composer validation, PHP lint and behavior tests on supported runtimes.

## Validation evidence

Baseline CI: https://github.com/aranlucas/reminder/actions/runs/42276461553 passed
all four PHP versions (8.2.35, 8.3.36, 8.4.27, 8.5.12) and aggregate `ci`.
Job steps/logs were inspected. New regression CI and independent review pending.
