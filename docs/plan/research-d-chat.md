# Research D — Realtime chat: polling vs websockets on single VPS + php -S + cloudflared

Stack: Laravel 12, single VPS, `php -S :8099` (built-in server, `PHP_CLI_SERVER_WORKERS=6`) via systemd, cloudflared tunnel → HTTPS. Health data (employee therapy chat) = sensitive.

---

## 1. Laravel Reverb on this stack

**Does cloudflared support websockets? YES.**
- Cloudflare FAQ (docs llms-full, sourced from developers.cloudflare.com cloudflare-one FAQ): "Does Cloudflare Tunnel support Websockets? Yes. Cloudflare Tunnel has full support for Websockets."
- The edge proxy supports WebSockets "without additional configuration" — wss:// terminates at Cloudflare edge, upgrade travels the tunnel like any HTTP request. No special cloudflared config needed for a public hostname of type `http://localhost:8099`.
- Caveats: Cloudflare closes idle WS connections (idle timeout; non-configurable except Enterprise) → client heartbeat (ping/pong) needed; Laravel Echo sends pings by default. Super Bot Fight Mode "Definitely Automated" must be Allow or tunnel connections fail with `websocket: bad handshake`.
- Sources: https://developers.cloudflare.com/network/websockets/ , https://developers.cloudflare.com/cloudflare-one/faq/ , https://developers.cloudflare.com/tunnel/concepts/routing/

**Does Reverb require another process? YES.**
- `php artisan reverb:start` is a long-running ReactPHP WebSocket server (default `0.0.0.0:8080`). It is NOT served through php -S and cannot be: php -S is request-scoped; a websocket handshake + persistent connection cannot live inside it.
- Practical consequence on this box: a second systemd unit (reverb.service) alongside php -S, and a second tunnel public hostname (e.g. `ws.<domain>` → `http://localhost:8080`) or a path-based ingress rule routing `/app/` and `/apps/` (Reverb's URIs) to Reverb and everything else to php -S.
- Reliability concerns: Reverb is a long-running PHP process — memory growth is the known operational complaint; needs `reverb:restart` on every deploy (code changes don't take effect otherwise); Supervisor-style auto-restart required. Default event loop (`stream_select`) caps at ~1,024 open files — irrelevant at <50 users.
- Source: https://laravel.com/docs/12.x/reverb

**Verdict:** technically works. Operationally it doubles the moving parts on a box whose whole appeal is one systemd unit + tunnel.

## 2. Pusher / Ably managed alternatives

**Cost**
- Pusher Channels: Sandbox free = 200k msgs/day, 100 concurrent connections. First paid tier $49/mo (1M msgs, 500 conns). Billing counts API messages + delivered messages (1 publish to 50 subscribers = 51 messages). Sources: https://pusher.com/channels/pricing
- Ably: Free = 500 MAU; Pro $29/mo (1K MAU); Business $349/mo adds SLA/SOC2/HIPAA. Source: https://ably.com/pricing

**Health-data privacy (this is the killer)**
- Pusher: message payloads are ephemeral (not stored; Cache Channels 30-min memory TTL), 9 public regions, but NO Indonesia region — closest is Singapore. No BAA; no HIPAA compliance claim; GDPR stance only. Optional E2E-encrypted channels (data hidden from Pusher) exist. Source: https://pusher.com/security
- Ably: SOC 2 Type II, HIPAA BAA available, data residency limited to EU/US/AUS — again no Indonesia. Message-level 256-bit AES with private key available. Source: https://ably.com/security-and-compliance
- Indonesia PDP Law (UU 27/2022, effective Oct 2024): health data is "specific personal data"; processing requires explicit consent; cross-border transfer to a country without adequate protection requires the controller to ensure protection and can require government coordination. Note: PDP law has no strict data-localization mandate (unlike pre-2012 health-records ministerial drafts), but routing unencrypted therapy chat through a US/EU third party puts a controller-heavy compliance burden on a small operation. I could not reach a live citable English summary in this session (Dataguidance/DLA Piper paywalled) — verify with counsel before relying on this.

**Verdict:** for <50 users, $29–49/mo buys nothing polling doesn't give, adds a third-party dependency (availability + latency from Indonesia to SG/EU), and creates a PDP compliance surface. Rejected.

## 3. Polling vs SSE vs long-polling on php -S

php -S facts (php.net):
- Default single process, single-threaded: one blocked request stalls the whole server. "The web server runs only one single-threaded process, so PHP applications will stall if a request is blocked."
- `PHP_CLI_SERVER_WORKERS=6` forks 6 workers → at most 6 requests in flight. Each poll request = one worker for the request duration (a chat poll endpoint should answer in ~10-50ms).
- Source: https://www.php.net/manual/en/features.commandline.webserver.php

Math for 50 concurrent users polling every 5s = 10 req/s, each ~30ms of DB query → 0.3 workers busy average; even at 200ms/query, 2 workers busy out of 6. Comfortably sustainable. At 2s polling (25 req/s) it gets tight but still OK; 5s is safe margin. One slow endpoint (report export, PDF) eats a worker for its full duration — keep heavy work out of the web workers.

- SSE: each SSE stream = 1 worker held for stream lifetime. With 6 workers, 6 SSE clients = server dead for everyone. Browsers also cap 6 connections/domain on HTTP/1.1 (Cloudflare serves HTTP/2 to clients so cap is fine, but the worker starvation on php -S is fatal). Workaround = 30s max stream lifetime + reconnect, which is... polling with extra steps. Source: https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events/Using_server-sent_events
- Long-polling (hold request 20-30s until new message): even worse on php -S — each held request is a burned worker. 6 long-polls = site down.
- Cloudflare caveat for polling: Proxy Read Timeout 125s — irrelevant for short polls. For any idea of "hold the request a while", the free-plan 100s and tunnel-layer timeouts bite. Source: https://developers.cloudflare.com/fundamentals/reference/connection-limits/

**Is 5-10s polling fine for therapy chat?** Yes. This is asynchronous consultation, not a multiplayer game. A 5s worst-case first-render delay on the counterparty's reply is imperceptible in a consulting workflow. WhatsApp-web-feel is achievable at 5s; bump to 3s during active typing windows if desired.

## 4. Data model for chat

Existing `chat_messages` (database/migrations/2026_11_04_000001_create_chat_messages_table.php) is already close to minimal-correct for a 2-party per-session thread:
`id, perusahaan_session_id (FK), sender_id (FK users), body text, read_at nullable, timestamps, index(session_id, id)`.

Recommended minimal additions (not a rewrite):
- `sender_role` enum('employee','consultant') — avoids a join to resolve display role; or derive from existing relations if already available.
- `deleted_at` soft-delete or `purge_at` timestamp for retention sweeps.
- Read receipts: current single `read_at` is correct for 1:1 — the other party's read = the row's read_at. For future group threads, split into `chat_message_reads(message_id, user_id, read_at)` — YAGNI today.

Privacy/retention patterns for health chat:
- Auto-purge: scheduled command deletes messages older than N days after session end (PDP data-minimization). `prunable()` on a Message model (Laravel Prune trait) is one line.
- Field-level encryption at rest: `body` cast via Laravel's `encrypted` cast (`protected $casts = ['body' => 'encrypted']`) — app-layer AES, DB compromise doesn't leak chat. Caveat: encrypted cast prevents LIKE search on body — acceptable for chat.
- Never log message bodies (sensitive-data rule already in working agreement).
- Backup consideration: encrypted cast means backups of DB are ciphertext; key lives in .env — protect it.

## 5. Recommendation

**Keep polling. Improve it; do not add websockets now.**

1. Polling at 5s intervals via the existing endpoint, with `?after_id=<lastId>` incremental fetch (cheap single indexed range scan on existing `(session_id, id)` index) and 304-style empty responses.
2. Add adaptive interval: 3s while tab focused + recent activity, 10s when tab hidden (visibilitychange). Halves average load for zero server work.
3. Add `sender_role` + retention purge (`Prunable`, e.g. 90 days after session close) + `encrypted` cast on `body` for PDP posture.
4. Skip Reverb: it works behind cloudflared, but a second always-on process, deploy restart coupling, and memory supervision on a box currently running one `php -S` unit is complexity with no user-visible win at this scale.
5. Skip Pusher/Ably: no Indonesia region, PDP burden for health data, monthly cost, third-party latency/availability — strictly worse than local polling here.
6. Revisit triggers: (a) >150 concurrent chat users, (b) sub-second delivery becomes a product requirement (live crisis-intervention feature), (c) migration to php-fpm + nginx (SSE becomes viable). Until one of those, polling is the boring correct answer.

---

### Sources
- https://laravel.com/docs/12.x/reverb
- https://developers.cloudflare.com/cloudflare-one/faq/ (Tunnel websocket support: yes)
- https://developers.cloudflare.com/network/websockets/ (edge WS proxy, idle timeout, bot-fight-mode interplay)
- https://developers.cloudflare.com/tunnel/concepts/routing/ (service types)
- https://developers.cloudflare.com/fundamentals/reference/connection-limits/ (proxy timeouts)
- https://www.php.net/manual/en/features.commandline.webserver.php (single-threaded, PHP_CLI_SERVER_WORKERS)
- https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events/Using_server-sent_events (SSE + PHP worker exhaustion)
- https://pusher.com/channels/pricing , https://pusher.com/security
- https://ably.com/pricing , https://ably.com/security-and-compliance
- PDP Law UU 27/2022: primary text (peraturan.go.id) not fetched this session; health=specific-data/consent/cross-border claims marked for legal verification.
