# Untangle decision log — 2026-11-06

## Scope and baseline (before edits)

Reviewed all tracked application PHP, HTML/CSS, tests, Composer configuration,
workflows, documentation and bundled Bootstrap distribution files at `13cbc01`.
The checkout was clean; no existing edits or findings were discarded. Applied
the root AGENTS.md and the caldoai untangle-codebase skill; both optional
Comments and Tests additions are selected. Tests and builds run in CI only.
No local validation process was started by this thread.

## Decisions and inventory

- Keep SMTP construction and environment validation with `PhpMailerTransport`.
  - Hidden link: `smtpgmail.php` knows PHPMailer settings, dependency availability
    and credentials, while `src/PhpMailerTransport.php` owns sending. An SMTP
    change currently spans the HTTP controller and transport.
  - Retain lazy construction after input validation, including the unavailable
    response when dependencies or configuration are missing. Keep PHPMailer and
    standard `filter_var` rather than implementing mail or email validation.
- Keep the existing CI workflow and supported PHP matrix.
  - Initial suspicion that `parallel` was invalid was disproved by GitHub's
    current workflow syntax and successful run `41723931463` on the baseline.
    Both manifest validation and `composer check` ran on all four PHP versions.
    No baseline failure is established; no workflow repair is needed.
- Remove unused Bootstrap JavaScript and optional theme stylesheets.
  - Surplus: neither page loads JavaScript or the theme. Keep the core stylesheet,
    its source/map and referenced fonts as a coherent upstream distribution;
    do not rewrite vendored code or remove licensing comments.
- Remove the recipient-cleanup comment that merely describes `finally`, and
  replace adapter property assertions with checks of the actual SMTP message.
  - Internal links: tests currently assert ContentType, AltBody, CharSet and
    SMTPDebug settings instead of their externally visible MIME/privacy contract.
  - Preserve outcome aggregation, validation, recipient isolation, partial retry
    warnings, literal message text and exception recovery tests. Standalone test
    helpers are tiny and do not justify a new test framework or shared harness.

## Required special cases retained

- `AllowEmpty` for the nonempty string `0`: PHPMailer uses PHP empty semantics;
  keep the library compatibility explanation and end-to-end fake SMTP coverage.
- Catch factory failures as unavailable and individual gateway failures as failed
  attempts: private diagnostics must not enter responses, and later gateways
  must still be attempted. Partial acceptance warns that retries can duplicate.
- North American +1 normalization and the historical five gateways are domain
  requirements. Do not change carrier availability policy in this refactor.
- Missing credentials/dependencies fail closed; no speculative configuration,
  scheduling, authentication, retry subsystem or dependency upgrade is added.

## Validation and review

- Static diff whitespace review passed. No tests, builds or benchmarks run locally.
- First independent review caught a case-sensitive MIME charset assertion added
  here: PHPMailer emits lowercase `utf-8`. Changed to a case-insensitive header
  check, since MIME charset spelling does not change the behavior contract.
- A second sequential independent review verified the corrected MIME check and
  entire final repository; no worthwhile issues remain. Both reviewers confirmed
  lazy configuration, private failures, meaningful tests and unreferenced assets.
- CI Composer validation/lint/behavioral tests on every supported PHP version
  must pass before merge. Baseline CI run `41723931463` passed; there are no known
  baseline failures to waive. The PR check results are the final validation record.
