# Analytics Service

Event-driven microservice for collecting and aggregating blog post view statistics. Consumes `post.viewed` events from RabbitMQ, stores raw view data, and provides an internal API for querying statistics and trending posts.

## Architecture

```
Frontend ──▶ RabbitMQ (post.viewed) ──▶ Analytics Consumer ──▶ MySQL
                                                                 │
                                              Admin / Frontend ◀─┘
                                              (internal API)
```

**Data flow:**
1. Frontend publishes `post.viewed` event to RabbitMQ when a user opens a post
2. Analytics consumer processes the event and stores raw view data in `post_views`
3. Scheduler aggregates daily stats into `post_daily_stats` (hourly)
4. Internal API serves statistics to Admin dashboard and Frontend author panel

## Tech Stack

- **Backend:** PHP 8.5 / Laravel 12
- **Database:** MySQL 8
- **Message queue:** RabbitMQ (php-amqplib)
- **API docs:** OpenAPI 3.0 (L5-Swagger)

## API Endpoints (Internal — X-Internal-Api-Key)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/v1/posts/{postUuid}/stats` | Post view statistics |
| GET | `/v1/authors/{userId}/stats` | Author statistics (all posts) |
| GET | `/v1/trending` | Trending posts |

### Query Parameters

- `period` — `day`, `week`, `month`, `year`, `all` (stats endpoints)
- `period` — `7d`, `30d` (trending endpoint)
- `limit` — number of results (trending, default: 10)
- `post_uuids` — comma-separated list (author stats, optional filter)

### Health (Kubernetes probes)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Liveness probe |
| GET | `/ready` | Readiness probe (DB) |

## Database Schema

- **`post_views`** — raw view events (post UUID, viewer info, timestamp)
- **`post_daily_stats`** — aggregated daily view counts per post

## Getting Started

### Prerequisites

- Docker & Docker Compose
- Running infrastructure services (Traefik, RabbitMQ)

### Development

```bash
cp src/.env.example src/.env
# Edit .env with your configuration

docker compose up -d
```

Containers:

| Container | Role | Port |
|-----------|------|------|
| `analytics-app` | PHP-FPM | 9000 (internal) |
| `analytics-nginx` | Web server | via Traefik |
| `analytics-consumer` | RabbitMQ consumer | — |
| `analytics-db` | MySQL 8 | 127.0.0.1:3310 |

### Scheduler

The `analytics:aggregate-daily` command runs every hour to compute daily aggregations.

## Roadmap

- [x] RabbitMQ consumer for post.viewed events
- [x] Raw view storage and daily aggregation
- [x] Internal API (post stats, author stats, trending)
- [x] Kubernetes manifests
- [ ] Unit and E2E tests
- [ ] Advanced statistics (per category/tag, trend comparisons)
- [ ] Interactive charts and visualizations

## License

All rights reserved.
