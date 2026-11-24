<p align="center">
  <img src="backend/public/images/logo-md.png" alt="Pickleball Club logo" width="180">
</p>

# PickleBall Club: Court Booking & Paddle Rental

A web app for a pickleball club. Players book a court for an open time slot, then choose whether to rent paddles for the same time. Admins manage courts, paddles and bookings.

Built with **Laravel 13**, **Livewire 4**, **Tailwind CSS 4** and **MySQL**.

## Features

**Players**
- See today's courts on the home page, with the next free time for each.
- Book a court: pick a court, a day (up to 30 days ahead) and a start time, for 1 to 4 hours. Taken times can't be picked.
- After booking, choose whether to rent paddles. You can only rent as many as are free for your time slot.
- **My bookings** shows upcoming, past and cancelled bookings. You can add or remove paddles, or cancel, until the booking starts.

**Admins**
- Dashboard with today's schedule, bookings awaiting confirmation, paddles rented today and this month's revenue.
- Confirm, complete or cancel bookings, and search them by reference, customer name or email.
- Add and edit courts (hourly rate, opening hours) and paddles (rental rate, units owned). Deactivate items to hide them from customers.

**Booking rules** (enforced in `backend/app/Services/BookingService.php`)
- No overlapping bookings on the same court. Back-to-back bookings are allowed.
- Bookings stay within the court's opening hours and can't be made for a time that has passed.
- Paddle stock is counted per time slot. A cancelled booking frees its court time and paddles.
- Prices are calculated on the server:
  - court fee = court's hourly rate × hours
  - paddle fee = paddle's hourly rate × hours × quantity
- Limits are set in `backend/config/booking.php`: how many days ahead you can book, the maximum hours, start-time minutes, and the maximum paddles of each type per booking.
- Bookings that rely on the same court or paddles are processed one at a time, so two people can't take the same slot or the last paddle.

## Requirements

- PHP 8.3 or newer, with the `pdo_mysql` extension
- Composer
- Node.js 20 or newer and npm
- MySQL 8

## Setup

```bash
git clone https://github.com/KertCainAbajo/PickleBall.git
cd PickleBall/backend

composer install
npm install

cp .env.example .env        # on Windows PowerShell: copy .env.example .env
php artisan key:generate
```

Create a MySQL database named `pickleball`. Then open `backend/.env` and set your MySQL login:

```env
DB_DATABASE=pickleball
DB_USERNAME=root
DB_PASSWORD=your-mysql-password
```

Create the tables, load the sample data and build the styles:

```bash
php artisan migrate --seed
npm run build
```

## Running the app

**Easiest (Windows):** double-click `start.bat` in the project folder. Each time, it:
- installs missing packages
- applies new database migrations
- rebuilds the styles
- starts the server with live style updates
- opens http://127.0.0.1:8000

Opening the folder in **VS Code** runs the same thing automatically: allow automatic tasks the first time VS Code asks. It skips opening the browser.

Press `Ctrl + C` in that terminal to stop everything.

**Just `php artisan serve` also works.** On your local machine it updates things before the server starts:
- applies new database migrations
- rebuilds the styles if any view, CSS or Livewire file changed since the last build
- removes a leftover Vite file if Vite isn't running

To turn this off, set `AUTO_UPDATE_ON_SERVE=false` in `.env`.

**For live style updates while editing,** open two terminals in the `backend` folder:

```bash
# Terminal 1: the Laravel app
php artisan serve

# Terminal 2: styles and scripts (updates the page as you edit)
npm run dev
```

Then open http://127.0.0.1:8000. Keep both terminals open while you use the app, and press `Ctrl + C` in each to stop.

`npm run build` is only needed when you put the app on a real server.

### Sample accounts

These are created by `php artisan db:seed`:

| Role     | Email                      | Password   |
|----------|----------------------------|------------|
| Admin    | `admin@pickleball.test`    | `password` |
| Customer | `customer@pickleball.test` | `password` |

> Change or remove these accounts before putting the app online.

## Tests

```bash
cd backend
php artisan test
```

The tests use an in-memory SQLite database, so they never touch your MySQL data.

## Project structure

```
backend/
├── app/
│   ├── Livewire/              # Pages: Home, BookCourt, RentPaddles, Bookings/*, Admin/*, Auth/*
│   ├── Models/                # Court, Paddle, Booking, BookingPaddle (rental line), User
│   ├── Services/
│   │   └── BookingService.php # All booking, rental and pricing rules
│   ├── Policies/              # Customers can only see and change their own bookings
│   └── Casts/DateOnly.php     # Stores booking dates as plain Y-m-d
├── config/booking.php         # Booking limits
├── database/                  # Migrations, factories, seeder (sample courts, paddles, accounts)
├── resources/
│   ├── css/app.css            # Design tokens: logo colours, fonts, shared styles
│   └── views/                 # Blade layout, Livewire views, components (court diagram, status badge)
├── routes/web.php
└── tests/Feature/             # Booking rules and page tests
```
