## Scripts: native first

Before adding a script file (`scripts/`, `bin/`, `tools/`, root `*.sh|*.py|*.mjs`) or a package script that runs one, take the first rung that works:

1. A flag or subcommand of a tool the repo already uses (`<tool> --help`).
2. Declarative config for that tool (`composer.json`, `.github/workflows/ci.yml`).
3. A maintained plugin for that tool.
4. A one-line composer.json script that calls a native command.
5. A test in the existing runner (the existing PHP tests via `composer test`), for anything that asserts.
6. A custom script, only for behavior specific to this repo. Its first comment names the native rung it couldn't use and why. It has a test in the existing runner, and the PR that adds it quotes that comment.

## Code: libraries first

Before hand-writing general-purpose code, use a well-maintained library: actively released, widely adopted, typed, and a fit for this stack. Reach for one already in the dependencies first. Write custom code for domain logic and for the glue no library covers. A PR that adds a dependency says why it was picked; a PR that hand-writes something a library covers names the library and why it didn't fit.

- SMTP connections and message construction → `phpmailer/phpmailer` (`PHPMailer`).
- Sender email validation → PHP standard-library `filter_var` with `FILTER_VALIDATE_EMAIL`.

## Tests: behavior and regression value

- Before adding a test, identify the specific behavior, a credible mistake it should catch, and an independently justified expected result.
- Prefer small, fast, reliable tests whose failures identify the broken behavior. Use integration/E2E tests for interactions isolated tests cannot establish.
- Do not reproduce implementation logic in assertions or mocks.
- Protect contracts, not incidental internal structure.
- Distinguish temporary development scaffolding from checks worth maintaining.
- Never delete or weaken a failing test just to make the suite pass.
