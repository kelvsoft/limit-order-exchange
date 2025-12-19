# Limit Order Exchange

A real-time cryptocurrency limit order book and matching engine built with Laravel 12, Vue 3 (Inertia.js), and Pusher.

## Features
- **Real-time Matching Engine**: Automatic trade execution when buy/sell prices overlap.
- **Live Updates**: Order book and recent trades update instantly via WebSockets.
- **Interactive Charting**: Price history visualized with Chart.js.
- **Asset Management**: Automatic balance and asset updates upon trade execution.

## Setup Instructions
1. **Clone the repository**
   `git clone https://github.com/kelvsoft/limit-order-exchange.git`
2. **Install Dependencies**
   `composer install` and `npm install`
3. **Environment Setup**
   - Copy `.env.example` to `.env`.
   - Configure Database and Pusher credentials.
   - Set `BROADCAST_CONNECTION=pusher`.
4. **Database Migration**
   `php artisan migrate --seed`
5. **Run the Application**
   `php artisan serve`, `npm run dev`, and `php artisan queue:work`.

## Tech Stack
- Laravel 12, Vue 3, Tailwind CSS, Inertia.js, Pusher, Chart.js.
