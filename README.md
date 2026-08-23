# SignLauncher

SignLauncher is a small, self-hosted digital signage system for one location and a limited number of displays. It uses PHP and JSON files; it does not require a database or external service.

## Features

- One administrator account with login, logout, password change, and recovery-code password reset.
- Session-protected content administration and CSRF protection for state-changing forms.
- JPEG upload and time-based scheduling for the built-in display IDs: `eisbar`, `theke`, `food`, `eingang`, and `stehle`.
- Server-side playlist selection and private media delivery for authenticated displays.

## How it works

The administrator creates scheduled events in `formular.php`. Events are stored in `data/events.json`; uploaded images are stored in `data/media/`. A display opens `display.php` with its screen identifier. Every 60 seconds it requests a playlist for that display only. The server returns the active image, or the fallback `standard_<display>.jpg` when no event is active.

When schedules overlap, the server selects the item with the highest optional `priority`; when priorities are equal, it selects the most recently starting matching item. The current administration form does not create priorities, so normal uploads use the latter rule.

## Installation and first setup

1. Serve this directory with PHP over HTTPS.
2. Configure a writable application data directory as described below.
3. Open `setup.php` once. Set an administrator password of at least 12 characters and save the displayed recovery code offline.
4. Sign in at `login.php`. The old `formular.html` URL redirects to the sign-in-protected administration page.

## Configuration

`SIGNLAUNCHER_DATA_DIR` optionally sets the directory for JSON data and uploaded images. In production, point it to a writable directory outside the web root. Without it, SignLauncher uses the local `data/` directory.

`SIGNLAUNCHER_TIMEZONE` optionally sets the venue timezone. It defaults to `Europe/Berlin`.

The fallback images are named `standard_eisbar.jpg`, `standard_theke.jpg`, and so on, and must be placed where the web server can serve them.

## Displays

After signing in, open **Displays** (`screens.php`) and copy the matching URL to each device's browser in full-screen mode. For example, the Eisbar display uses `display.php?screen=eisbar`.

The legacy `display.html` page does not select a screen; use `display.php?screen=<screen>` instead.

## Content and schedules

In `formular.php`, choose a display, select a start and end date/time, and upload a JPEG. Files are limited to 5 MB and 8000 × 8000 pixels. The schedule is evaluated on the server. The event list lets the signed-in administrator delete an event; events that ended more than 24 hours ago and their media are cleaned up when a new upload is saved.

## Security notes

Use HTTPS and keep PHP, the web server, and the host patched. Do not share the administrator password or recovery code. Display URLs are intentionally public to devices that know a valid screen identifier.

Never expose the data directory directly. The included `.htaccess` files protect it on Apache. With Nginx or another server, either use `SIGNLAUNCHER_DATA_DIR` outside the web root or explicitly deny `/data/` requests. The PHP process needs read/write access to the configured data directory.
