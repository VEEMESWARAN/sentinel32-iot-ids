# Sentinel32 — Render Deployment

This version is converted from the local XAMPP/MySQL build to **Render + Docker + PostgreSQL**.

## What changed
- MySQL/MariaDB -> PostgreSQL (`pdo_pgsql`)
- Local database constants -> Render `DATABASE_URL`
- Hard-coded API/Telegram secrets -> Render environment variables
- Added Docker deployment on Render
- Added `/api/health.php` health check
- PostgreSQL-compatible UPSERT, date/time and interval queries
- Apache binds to Render's `$PORT` (default 10000)

## Render environment variables
Set these on the Web Service:
- `DATABASE_URL` — Render Postgres internal connection URL (Blueprint links this automatically)
- `DEVICE_API_KEY` — long random secret; must match ESP32 `X-API-KEY`
- `TELEGRAM_ENABLED` — `true` after Telegram is configured
- `TELEGRAM_BOT_TOKEN` — Telegram bot token
- `TELEGRAM_CHAT_ID` — Telegram destination chat ID
- `APP_TIMEZONE` — `Asia/Kuala_Lumpur`

Never commit real secret values to GitHub.

## Deploy option A — Render Blueprint
1. Push this project to a GitHub repository.
2. Render Dashboard -> New -> Blueprint.
3. Select the repository containing `render.yaml`.
4. Supply the requested secret environment values.
5. Deploy.

## Deploy option B — Create services manually
1. Create Render Postgres in the same region as the Web Service.
2. Run `database/schema.sql` against the Render Postgres database.
3. Create a Web Service from the GitHub repo and select Docker.
4. Dockerfile path: `./Dockerfile`.
5. Health check path: `/api/health.php`.
6. Add the environment variables listed above.

## Initialize PostgreSQL schema
Run the contents of `database/schema.sql` once against your Render Postgres database. You can use Render's database connection details with a PostgreSQL client such as `psql` or pgAdmin.

## Verify deployment
Open:
`https://YOUR-SERVICE.onrender.com/api/health.php`

Expected:
`{"ok":true,"app":"Sentinel32 IoT IDS","database":"connected",...}`

Then open:
`https://YOUR-SERVICE.onrender.com/`

## ESP32 endpoints after deployment
Replace `YOUR-SERVICE` with your actual Render hostname:
- `https://YOUR-SERVICE.onrender.com/api/heartbeat.php`
- `https://YOUR-SERVICE.onrender.com/api/receive_telemetry.php`
- `https://YOUR-SERVICE.onrender.com/api/receive_alert.php`

The ESP32 must send the same `DEVICE_API_KEY` in the HTTP header `X-API-KEY`.

## Telegram test
After setting Telegram environment variables and changing `TELEGRAM_ENABLED=true`, open:
`https://YOUR-SERVICE.onrender.com/api/test_telegram.php`

## Important
The existing dashboard design and API JSON structure were retained so the ESP32 firmware needs only the server URL/API-key changes when moving from localhost to Render.
