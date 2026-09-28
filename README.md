# MarketLink

Farm Fresh Just a Click Away

MarketLink is a pre-order website for farmers markets. Farmers put their weekly stock and prices online, customers reserve items before market day, pick a pickup slot and collect the order at the stall. Payment is done in person at pickup.

Built by Team Strange Squad for TechWiz7 (Theme: eGreen Basket, Category: End-to-End Web Solutions).

- Live: https://ubaidportfolio.infinityfree.me/
- Covers 6 Texas markets: Houston, Dallas, San Antonio, Austin, Fort Worth and El Paso

## Features

**Customer**
- Register, login, Continue with Google, forgot password
- Markets near me on a map with distance and directions
- Browse and filter products by category, market, day and price
- Basket grouped by farmer, checkout with market and pickup slot
- Change or cancel an order before the farmer cutoff time
- Favourite farmers, products and markets, back-in-stock alerts
- Reviews and ratings after a completed order

**Farmer**
- Stall profile with map pin, markets, stall number and cutoff hours
- Add, edit and delete products, mark sold out, weekly stock refill
- Pickup slots, accept or decline pre-orders, mark ready and completed
- Sales insights with charts, reply to reviews

**Admin (3 tiers: Super Admin, Support Admin, Community Moderator)**
- Approve or suspend farmers, activate or deactivate customers
- Manage markets, categories and announcements
- Hide products or reviews that break the rules
- Reports with date range, CSV export and print

**Extra**
- AI agents for customer, farmer and admin that can do tasks and always ask before changing data
- Urgent orders (pickup within the hour) with optional AI auto-confirm
- Community feed, customer-farmer messages with photo and voice notes
- Ctrl+K search across products, farmers and markets

## Tech Stack

Laravel 12, Blade, Tailwind CSS, Alpine.js, MySQL, Vite, Leaflet + OpenStreetMap, Chart.js, Spatie Laravel Permission, Laravel Socialite, Groq API

## Installation

Requirements: PHP 8.2+, Composer, Node.js, MySQL (XAMPP works)

```bash
git clone https://github.com/Ali-berry/marketlink-techwiz7
cd marketlink-techwiz7
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create an empty MySQL database and set its name, user and password in `.env`. Then:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Open http://127.0.0.1:8000

Optional keys in `.env` (if a key is empty, only that feature is switched off):

| Key | Used for |
|---|---|
| GROQ_API_KEY_PRIMARY, GROQ_API_KEY_SECONDARY | AI agents |
| ABSTRACT_EMAIL_VALIDATION_API_KEY | Email check at signup |
| GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET | Google login |
| GOOGLE_MAPS_API_KEY | Area autocomplete |

For the demo, `MAIL_MAILER=log` is used, so emails are written to `storage/logs/laravel.log`. For urgent orders you can run `php artisan schedule:work` (optional, status also updates when the page is opened).

Run tests with `php artisan test`.

## Demo Accounts

The login page also has demo buttons for quick login.

| Role | Email | Password |
|---|---|---|
| Super Admin | admin@marketlink.test | Admin@1234 |
| Support Admin | support@marketlink.test | Support@1234 |
| Community Moderator | community-mod@marketlink.test | Community@1234 |
| Farmer (Green Valley Farm) | greenvalley@marketlink.test | Farmer@1234 |
| Farmer (Wildflower Honey Co., pending approval) | wildflower@marketlink.test | Farmer@1234 |
| Customer (Sara Ahmed) | sara@marketlink.test | Customer@1234 |

All 12 accounts are listed in the User Credentials file of the submission.

## Team Strange Squad

| Member | Work |
|---|---|
| Ubaid (Team Leader) | Project structure, hosting, error resolving |
| Ali | Web development, planning, UI / UX, AI agent features, documentation, demo video |
| Zaryan | Support |
| Ammar | Support |

## AI Tools

As asked in the SRS: Claude Code was used as a coding assistant, Google Gemini and ChatGPT for images, and the Groq API runs the AI agents inside the app. Details are in section 21 of the project documentation.
