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
- Known issue at time of writing: VITE_REVERB_APP_KEY missing from first
  build, so window.Echo undefined on prod; DealtDeployFix sub-agent
  fixing + verifying Reverb E2E (fresh build with vars present).

## Next

- Confirm Reverb live arrival on prod (sub-agent receipt).
- Reply to Taylor's tweet with the laravel.cloud URL + screenshot.
- Joshua bounces the real first loop as DJ Zip.
