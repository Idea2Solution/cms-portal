# D Orange CRM (Laravel)

Laravel 12 CRM built from the design in `design/crm4biz.html`.

Modules: **Dashboard · Leads · Inquiries · Follow-ups · Customers · Settings**, plus a 360° company page with a remarks timeline.

## Setup

```bash
composer install
cp .env.example .env && php artisan key:generate   # then set DB_* (MySQL database "cms")
php artisan migrate --seed                         # creates admin@crm.test / password
php artisan db:seed --class=DemoSeeder             # optional sample data
```

Open http://cms.test (Laragon) or run `php artisan serve`.

## Business rules (carried over from the design)

- Leads and customers are one `companies` record; converting keeps all history.
- Inquiry set to **Won** → the lead becomes a customer automatically (`C-00001`…).
- Calls/WhatsApp/email/meeting remarks, new inquiries and completed follow-ups mark a lead **Contacted**.
- Completing a follow-up writes the outcome to the timeline and can book the next one.
- Pipeline stage is derived from lead status + inquiries (shown on the dashboard and company page).

## Where things are

| | |
|---|---|
| Styles (design tokens at the top) | `public/css/crm.css` |
| Front-end behaviour | `public/js/crm.js` |
| Option lists & default settings | `app/Support/Crm.php` |
| Controllers | `app/Http/Controllers` |
| Views | `resources/views` |
