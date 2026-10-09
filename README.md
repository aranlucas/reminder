# Reminder

A small PHP reminder application with a Bootstrap frontend and SMTP email support.

## Local setup

Use PHP 8.2 or later with the filter and OpenSSL extensions, and Composer. Install the locked, supported PHPMailer dependency:

```sh
composer install --no-interaction --no-scripts --no-plugins
```

Before sending, configure these environment variables in the PHP server's environment:

- `REMINDER_SMTP_USERNAME`: Gmail SMTP username
- `REMINDER_SMTP_PASSWORD`: the account's SMTP credential
- `REMINDER_FROM_ADDRESS`: the verified sender email address

Do not put credentials in source control or a publicly served file. The application does not read `.env` files. The existing Gmail SMTPS connection (`smtp.gmail.com:465`) is retained. Previous inline settings in `smtpgmail.php` must be moved into the server environment; missing configuration returns a safe unavailable response without sending anything.

`composer dev` serves the app at `https://reminder.localhost/index.html` through [Portless](https://github.com/vercel-labs/portless) (`npm install -g portless`); its first run may ask for `sudo` to bind port 443 and trust a local certificate. The application entrypoint uses Composer's PHPMailer, not the old bundled classes. The historical `smtpmail/` examples remain untouched and are not supported entrypoints; do not expose or run them as part of a deployment.

## Delivery contract

Enter a 10-digit North American phone number, optionally with the `+1` country code and common spaces, parentheses, dots or hyphens. Messages are sent as literal UTF-8 text, not interpreted as HTML or local attachment paths.

The existing five carrier gateways are retained. This is a legacy gateway strategy, not a carrier discovery service; the configured providers may no longer offer email-to-SMS delivery. SMTP acceptance does not confirm arrival on a phone. The result distinguishes complete gateway acceptance, partial acceptance and total failure. Retrying after partial acceptance can duplicate messages.

Invalid or missing form fields never initialize a mail transport. Recipient lists, message contents and SMTP diagnostics are not included in normal HTTP results.

## Offline checks

```sh
composer validate --strict
composer check
composer audit --locked
```

The contract tests use fake delivery outcomes and an in-memory SMTP adapter for the real PHPMailer. Entrypoint tests deliberately unset SMTP configuration. No tests contact SMTP servers, send notifications, use real reminders, or require credentials. They cover aggregate outcomes, malformed requests, literal text, per-send recipient isolation and safe error responses.

Before any public deployment, review access control/rate limiting and the obsolete bundled examples as well as carrier availability. This change does not add authentication, schedule reminders, rotate any historical credentials, or deploy the application.
