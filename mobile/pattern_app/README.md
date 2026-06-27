# Pattern Mobile App

Flutter mobile app source for Pattern Vacation Homes.

This app is intended to live alongside the existing Laravel RMS and PWA. The PWA remains active; this Flutter app will use Laravel API endpoints for Android and iOS.

## Current Scope

- Animated login and role selection shell
- Tenant dashboard
- Owner dashboard
- Animated tenant smart-lock swipe control
- Bookings/properties cards
- Payout and statement shortcuts
- Messages/support placeholder
- API service layer with offline sample data

## PWA Status

The current Laravel/PWA stays active. This Flutter app is the new native Android/iOS source and will connect to the same backend through API endpoints.

## Run After Flutter Is Installed

```bash
cd mobile/pattern_app
flutter create --platforms=android,ios .
flutter pub get
flutter run
```

Flutter was not installed on the current development machine when this scaffold was created, so the first local verification step after installing Flutter should be:

```bash
flutter analyze
flutter test
```

The project source is dependency-light and uses Flutter SDK widgets only for now.
