# Reminder

A PHP form that sends messages immediately through email-to-SMS gateways; it does not schedule reminders.

## Local setup

Use Composer and a PHP version satisfying the patch-level requirements in [composer.json](composer.json), with the filter and OpenSSL extensions. Install the locked dependencies:

```sh
composer install --no-interaction --no-scripts --no-plugins
```

Before sending, configure these environment variables in the PHP server's environment:

- `REMINDER_SMTP_USERNAME`: Gmail SMTP username
- `REMINDER_SMTP_PASSWORD`: the account's SMTP credential
- `REMINDER_FROM_ADDRESS`: the verified sender email address

Keep credentials out of source control and publicly served files; rotate any previously committed credentials before reuse. The application does not read `.env` files. It uses Gmail SMTPS (`smtp.gmail.com:465`); missing configuration prevents sending and returns an unavailable response.

`composer dev` serves the app at `https://reminder.localhost/index.html` through [Portless](https://github.com/vercel-labs/portless) (`npm install -g portless`); its first run may ask for `sudo` to bind port 443 and trust a local certificate.

## Delivery contract

Messages are sent as literal UTF-8 text, not interpreted as HTML or local attachment paths.

The app attempts every configured carrier gateway without discovering the recipient's carrier. Providers may no longer offer email-to-SMS delivery. SMTP acceptance does not confirm arrival on a phone, and retrying after partial or unconfirmed acceptance can duplicate messages.

HTTP results omit recipient lists, message contents and SMTP diagnostics.

Before any public deployment, review access control/rate limiting and carrier availability. The application has no authentication.

## Offline checks

```sh
composer validate --strict
composer check
```

The tests use fake delivery outcomes and an in-memory SMTP adapter, and unset SMTP configuration for entrypoint checks. They require the installed dependencies but no credentials, and do not contact SMTP servers or send notifications.

Run `composer audit --locked` separately when network access is available; it queries the advisory service and is not an offline check.
