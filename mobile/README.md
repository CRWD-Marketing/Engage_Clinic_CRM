# Engage Clinic — mobile app

Expo (React Native, TypeScript, Expo Router) app for Engage Clinic staff, mirroring the Laravel web app in the parent folder. It currently runs on **mock data** shaped like the Laravel models; the real API comes later.

## Run it

```bash
cd mobile
npm install
npx expo start          # scan the QR code with Expo Go (same Wi-Fi as this PC)
npx expo start --tunnel # if the phone can't reach the PC
```

Mock login: any seeded staff email with password `password`, e.g. therapist `alessandra@engagebehavior.com`.

## Checks

```bash
npx tsc --noEmit   # type-check
npx expo lint      # lint
```

## More

See [CLAUDE.md](CLAUDE.md) for the project rules, folder structure, domain notes and milestone tracker, and [docs/](docs/) for research on the Laravel app.
