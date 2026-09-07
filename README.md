# Periodic Post Reporter

This project was built as a technical challenge for a backend developer position.

## Prerequisites
- Docker & Docker Compose
- A bash-compatible shell (macOS/Linux Terminal, or WSL/Git Bash on Windows) — commands below use bash syntax and commands.

## Ports & config
Ensure ports `5432` (Postgres), `8000` (app), and `9200` (Elasticsearch) are free on the host.

## Setup
1. Copy `.env.example` to `.env`
2. In `.env`, set:
   ```
   DB_HOST=postgres
   ELASTICSEARCH_HOST=elasticsearch
   ```
   (Docker Compose service names, not `127.0.0.1` — containers resolve each other by service name on the internal network.)
3. `docker compose up -d` (runs migrations automatically)
4. `docker compose exec app php artisan app:create-user {email} {username}`
5. Place the provided posts JSON at `storage/app/private/seed-data.json`
6. `docker compose exec app php artisan app:seed-elasticsearch`

App is available at `http://localhost:8000`.

## Testing
`docker compose exec app php artisan test`

## Scheduled reports
`docker compose exec app php artisan app:dispatch-reports` — manual trigger; scheduled daily at 08:00 via Laravel's scheduler in production.

## Known issue: Elasticsearch red status on startup
If Elasticsearch's cluster health stays `red` after `docker compose up`, Docker Desktop's virtual disk usage may trigger ES's disk-based shard allocation watermark, blocking allocation even with adequate host disk space. Check:
```
curl http://localhost:9200/_cluster/health?pretty
```
If `status` is `red`, disable the watermark check (safe for local dev only):
```
curl -X PUT "localhost:9200/_cluster/settings" -H "Content-Type: application/json" -d '{"transient": {"cluster.routing.allocation.disk.threshold_enabled": false}}'
curl -X POST "localhost:9200/_cluster/reroute?retry_failed=true"
```

## Known limitation
Seed data is dated 2024; scheduled reports query the current date range, so live scheduled runs will show 0 posts against this static dataset. To verify search/histogram behavior end-to-end, either query Elasticsearch directly against a 2024 date range (see tests) rather than relying on the live scheduler, or update the seeded documents' `published_at` values to fall within the current date range before running `app:dispatch-reports`.

## Design notes & answers to task questions

**Scaling with request/user growth**
- Stateless API (Sanctum tokens) — horizontally scale app instances behind a load balancer.
- `DispatchReport` queue jobs scale independently of web traffic; add workers as report volume grows.
- Elasticsearch scales via additional nodes/shards as index size grows; current single-shard config suits current data volume only.

**User-selectable delivery channels (one or more)**
- Store `channels` as a JSON array on `report_schedules` (e.g. `['email', 'sms']`), not a single value, to support multi-select.
- Use Laravel's Notification system instead of a direct `Mail::send` call: a `PostsReportNotification` implements `via()` returning the schedule's selected channels, with per-channel delivery defined in `toMail()`, `toSms()`, etc. Adding a new channel means adding one method and a driver, not touching dispatch logic.
- Validate `channels` against a whitelist/enum at the request layer, consistent with how `period` is validated.

**Scaling with large historical data volume**
- Partition `report_runs` by date range if history grows unbounded, keeping idempotency lookups fast.
- Archive `report_runs` beyond a retention window to cold storage.
- Existing unique index on `(report_schedule_id, period_start, period_end)` already covers the idempotency query pattern efficiently at scale.

**Load test / benchmark**

`ab -n 100 -c 10` against `GET /api/v1/report-schedules` (authenticated):

- Requests per second: 37.45
- Mean response time: 267 ms
- P90: 283 ms
- P95: 288 ms
- P99: 291 ms
- Failed requests: 0

Response times are dominated by Eloquent query + JSON serialization overhead at low concurrency; no failures observed at this load level. Elasticsearch-backed endpoints (report generation) were not load tested separately due to time constraints — recommended next step given ES query latency is the more likely bottleneck at scale.

## Verifying the report pipeline manually
The task's example uses "تهران" and "آلودگی" as keywords; this repo's seed data (2024-12-18 to 2024-12-21) can be queried directly with a Persian keyword to confirm search, histogram aggregation, and Excel export all work end-to-end:

```bash
docker compose exec app php artisan tinker --execute="
    \$posts = app(\App\Repositories\Contracts\PostSearchRepositoryInterface::class);
    \$histogram = \$posts->countByDay(['تهران', 'آلودگی'], \Carbon\Carbon::parse('2024-12-18'), \Carbon\Carbon::parse('2024-12-21'));
    \Maatwebsite\Excel\Facades\Excel::store(new \App\Exports\PostsReportExport(\$histogram), 'report.xlsx', 'local');
"
docker compose cp app:/var/www/html/storage/app/private/report.xlsx ./report.xlsx
```

Open `report.xlsx` to confirm the generated report contains a per-day post count for the given keyword and date range.

## What I'd improve with more time
- Broader test coverage, particularly integration tests against a real Elasticsearch instance (e.g. via Testcontainers) instead of mocked interfaces for the index manager and search repository.
- More consistent request/response schema conventions across endpoints (e.g. unifying resource wrapping keys).
- Applying the same architectural patterns (repository/service separation) uniformly across all resources, not only the Elasticsearch-backed ones.
