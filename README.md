# TNB Electricity Meter Monitoring App

A PHP + MySQL app for XAMPP. Includes **Version 1** (normal photo upload) and
**Version 2** (forced live camera capture) — switchable from Admin ➜ Settings,
no code changes needed to demo both.

## 1. Install into XAMPP

1. Copy the whole `tnb-meter-app` folder into `C:\xampp\htdocs\`
   (final path should be `C:\xampp\htdocs\tnb-meter-app`).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.

## 2. Create the database

1. Open `http://localhost/phpmyadmin`.
2. Click **Import**, choose `database.sql` from this project, click **Go**.
   This creates the `tnb_meter_app` database, all tables, default settings,
   one sample meter, and 2 seed accounts (admin + user).

## 3. Activate the seed accounts (one-time)

The seed accounts in `database.sql` are inserted with a placeholder password
so it doesn't rely on a guessed hash. Right after importing, visit:

```
http://localhost/tnb-meter-app/setup_create_accounts.php
```

This sets working passwords:
- **Admin:** admin@example.com / admin123
- **User:** user@example.com / user123

Then **delete `setup_create_accounts.php`** (or rename it) for security, and
change these passwords later (a "change password" screen isn't included in
this trial build — you can update the `password` column via a new
`password_hash()` call, or add a settings screen for it).

## 4. Configure the app

Edit `config/db.php` if your MySQL user/password differs from the XAMPP
default (`root` / empty password).

If your folder name isn't `tnb-meter-app`, update `BASE_URL` in
`config/config.php` to match.

## 5. Login and try it

Go to `http://localhost/tnb-meter-app/login.php`.

- Log in as **Admin** → go to **Settings** to:
  - Set the daily usage limit (kWh) and submission deadline time.
  - Enter your WhatsApp gateway details (see step 6).
  - Switch **App Version** between "1 - Allow upload" and "2 - Live camera only".
- Log in as **User** (or create more Users under Admin ➜ Users, assigning
  them to specific meters) → go to **Submit Reading**:
  - **Version 1:** a normal `<input type="file">` lets you take a photo or
    pick one from the gallery — use this to test by photographing any paper
    with a number written on it.
  - **Version 2:** the page starts a live camera stream in the browser; you
    press **Start Camera** → **Capture Photo**. There is no gallery/file
    picker anywhere in this flow, and the **Submit** button stays disabled
    until a live photo has been captured. The server also rejects submission
    if no captured photo is present, so this is enforced both client- and
    server-side.

Each meter allows **one reading per calendar day** (Malaysia time) — this
matches "the User submits the reading every day" and lets the over-usage
calculation compare yesterday's reading vs. today's reading directly.

## 6. WhatsApp Alerts

This trial is wired to **Fonnte** (https://fonnte.com) because it's the
fastest way to test WhatsApp sending without waiting for official Meta
Business verification — sign up, connect your WhatsApp number by scanning a
QR code, and copy your device token into Admin ➜ Settings ➜ WhatsApp Gateway
(API URL: `https://api.fonnte.com/send`, API Token: your device token,
Admin Number: the number that should receive alerts, format `60123456789`).

To use a different gateway (Twilio WhatsApp API, WABLAS, or the official
WhatsApp Cloud API), you only need to edit **one function**:
`send_whatsapp_message()` in `includes/whatsapp.php`. Everything else in the
app calls that function and doesn't care which gateway is behind it.

## 7. Set up the two scheduled checks (cron)

The two WhatsApp alerts are **not** sent automatically by the web app itself —
a scheduled task must run the two scripts in `/cron`:

- `cron/cron_check_usage.php` — compares today's reading to the previous
  reading; if usage > limit, sends the over-usage WhatsApp alert.
- `cron/cron_check_missing.php` — after the configured deadline time, checks
  every active meter for a missing reading today and sends a reminder.

Both scripts are safe to run repeatedly — `notification_log` (with a unique
key per type/meter/day) stops duplicate WhatsApp messages being sent twice
in the same day.

### Windows Task Scheduler (XAMPP is usually on Windows)

1. Open **Task Scheduler** → **Create Task**.
2. **Trigger:** Daily, repeat every 30–60 minutes (e.g. from 7am to 8pm).
3. **Action:** Start a program:
   - Program/script: `C:\xampp\php\php.exe`
   - Add arguments: `C:\xampp\htdocs\tnb-meter-app\cron\cron_check_usage.php`
4. Repeat step 1–3 for `cron_check_missing.php`.

(On Linux/Mac XAMPP, add the equivalent two lines to `crontab -e`, e.g.
`*/30 7-20 * * * php /opt/lampp/htdocs/tnb-meter-app/cron/cron_check_usage.php`.)

## 8. Project structure

```
tnb-meter-app/
├── config/           # DB connection + global settings (timezone, paths)
├── includes/         # auth, shared functions, WhatsApp sender, header/footer
├── assets/           # css + camera.js (live capture logic for Version 2)
├── cron/             # the two scheduled WhatsApp check scripts
├── uploads/          # meter photos are saved here (protected from execution)
├── database.sql      # full schema + seed data
├── login.php / logout.php / index.php
├── dashboard.php     # Admin: today's usage status per meter
├── meters.php        # Admin: add/manage meters
├── users.php         # Admin: add users, assign meters
├── settings.php      # Admin: usage limit, deadline, WhatsApp config, app version
├── submit_reading.php  # User: submit reading — renders V1 or V2 UI based on settings
└── readings.php      # History list (Admin sees all, User sees own)
```

## Notes / trial limitations

- Photos are stored on local disk under `/uploads` and linked to each
  reading record, as requested.
- Submission date/time are recorded using PHP's `date()` with the timezone
  set to `Asia/Kuala_Lumpur` in `config/config.php`, so they're always
  Malaysia time regardless of server location.
- Version 2's "no gallery browsing" requirement is implemented by never
  rendering a file `<input>` at all — only `getUserMedia()` + `<canvas>` frame
  capture is used, which has no OS picker to fall back to. On very old
  browsers without camera API support, the capture UI simply won't start
  (there is intentionally no upload fallback in Version 2, per the spec).
- This is a trial-grade build (no password reset screen, no pagination on
  the readings list, no automated tests). It is a solid, working base to
  harden before production use — the main things to review before then are
  proper input validation limits, HTTPS enforcement (browsers require HTTPS
  for camera access on non-localhost domains), and moving credentials in
  `config/db.php` out of source control.
