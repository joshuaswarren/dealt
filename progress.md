# Progress

## 2026-07-11 - built and shipped

- Scaffold: Laravel 13 + Livewire 4 + Reverb + Pest on Postgres (dealt_dev / dealt_test).
- Deal engine: mulberry32 PRNG, 13-track catalog DNA, three cards/day with
  distinct finish rules, seeded 6x16 patterns, per-key note tables.
  5 determinism tests, 1380+ assertions.
- Loop wall: ULID loops table, bounce API (rate-limited 10/min, 30/day per IP,
  pattern validated), spin endpoint (3/loop per IP per 5 min),
  LoopBounced ShouldBroadcastNow on public `wall` channel. 6 API tests.
- Frontend: Web Audio 6-voice engine (lookahead scheduler), deal screen with
  30s deadman auto-pick, 16-step session grid, bounce modal, localStorage
  streaks, client-side .mid export, wall with pattern-synthesized playback +
  Echo live arrivals. Y2K dark design system, 375px clean.
- Voice gate: about page + README linted clean (voice_lint article mode),
  josh-voice-14b rewrite pass converged (zero diff) on both.
- Verified locally: 11 Pest tests green (exit 0), full browser flow at 1440
  and 375 (deal -> house-deal -> grid edit -> bounce -> wall).
- Shipped: `cloud ship` created app + Postgres + Reverb WS cluster;
  https://dealt-production-hczrri.laravel.cloud live (all pages 200,
  db=pgsql, broadcast=reverb). Genesis loop: "the house" / "as dealt".
- Reverb E2E verified on prod by DealtDeployFix sub-agent: WebSocket
  connected, live .wall-card.fresh arrival in a non-reloaded tab, test loop
  deleted after. The earlier "window.Echo undefined" was a headless-harness
  artifact (module scripts not auto-executed), not a deploy bug - the VITE
  vars were baked into the first build all along.
- Tweet reply drafted and voice-gated (lint clean, rewrite converged):
  /tmp/dealt-tweet.txt. Remnic memory store was 503 at session close;
  this file is the durable record.

## Next (blocked on Joshua)

- Post the gated reply to Taylor's tweet (2075667366646858222) - needs
  Joshua's explicit go-ahead to publish under his X account.
- Bounce the first real loop as DJ Zip.
