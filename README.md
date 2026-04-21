# wolfXcore — Game Server Management Panel

![wolfXcore Landing](https://i.ibb.co/n8zGnhVj/Screenshot-2026-04-14-175412.png)

  > **wolfXcore** is a cyberpunk-themed, full-stack game server management panel built for modern hosting providers. Manage game servers, handle billing with Paystack & M-Pesa, provision servers automatically, and control everything from a powerful Super Admin dashboard.

  🌐 **Live Panel:** [panel.xwolf.space](https://panel.xwolf.space)
  📚 **Docs:** [xcoredocs.xwolf.space](https://xcoredocs.xwolf.space)

  ---

  ## What is wolfXcore?

  wolfXcore (also known as **wxn** or **wolfX panel**) is a complete game server management solution designed for African hosting providers and beyond. It features a neon-green cyberpunk UI, integrated payment processing, and automated server provisioning — all in one platform.

  Whether you're hosting Minecraft, FiveM, Discord bots, or any other application, wolfXcore gives you and your customers full control.

  ---

  ## ✨ Features

  ### 🎮 Game Server Management
  - Start, stop, restart, and monitor game servers in real time
  - Console access with a fully themed cyberpunk terminal
  - File manager, database manager, schedule manager, and more
  - Customizable server tabs (hide/show per role)

  ### 💳 Integrated Billing
  - **Paystack** payment gateway (card + M-Pesa)
  - KES (Kenyan Shilling) currency support
  - Wallet system — top up and pay from balance
  - Plans: Starter, Standard, Pro, Unlimited, Admin Panel
  - Automated server provisioning on successful payment

  ### 🛠️ Super Admin Control Panel
  - Live **Theme Designer** — change accent colors, fonts, backgrounds with zero rebuild
  - **Sitewide Announcements** — broadcast messages to all users (success / info / warning / danger)
  - **Maintenance Mode** — put the site in maintenance with one click
  - User management — promote/demote admins, view all users
  - Server provisioning config (Node, Nest, Egg defaults)
  - Paystack API key management from the dashboard
  - Custom CSS injection
  - Notification system for live user alerts

  ### 🎨 Cyberpunk UI
  - Neon green accent with full theme customization
  - Dark backgrounds with scan-line and grid effects
  - Orbitron + JetBrains Mono fonts
  - Fully responsive admin and user panels

  ---

  ## 🛠 Tech Stack

  | Layer | Technology |
  |---|---|
  | Backend | Laravel 11 (PHP 8.4) |
  | Frontend | React + TypeScript (Webpack) |
  | Styling | Tailwind CSS + Custom CSS Variables |
  | Database | MySQL / MariaDB |
  | Payments | Paystack (M-Pesa + Card) |
  | Server Agent | Wings (Go) |
  | Deployment | Nginx + PHP-FPM |

  ---

  ## 🚀 Getting Started

  ### Requirements
  - PHP 8.4+
  - Node.js 18+ & Yarn
  - MySQL 8+
  - Nginx
  - A Paystack account (for billing)

  ### Installation

  ```bash
  # Clone the repository
  git clone https://github.com/sil3nt-wolf/wolfXcore.git
  cd wolfXcore

  # Install PHP dependencies
  composer install --no-dev --optimize-autoloader

  # Install JS dependencies
  yarn install

  # Copy and configure environment
  cp .env.example .env
  php artisan key:generate

  # Run migrations
  php artisan migrate --force

  # Build frontend assets
  yarn run build:production

  # Cache config and routes
  php artisan config:cache && php artisan route:cache
  ```

  ---

  ## 📦 Plans & Pricing (Default)

  | Plan | Price (KES) | Description |
  |---|---|---|
  | Starter | 40/mo | Entry-level game server |
  | Standard | 80/mo | Mid-tier performance |
  | Pro | 120/mo | High performance |
  | Unlimited | 150/mo | Unrestricted resources |
  | Admin Panel | 300 | Grants admin access to the panel |

  All plans are configurable from the Super Admin dashboard.

  ---

  ## 🔐 Super Admin

  wolfXcore includes a separate, key-protected Super Admin interface at `/admin/wxn-super/auth`. Features include:

  - Live theme designer (no rebuild required)
  - Sitewide announcements
  - Maintenance mode toggle
  - User admin management
  - Payment gateway configuration
  - Server provisioning defaults

  ---

  ## 📸 Screenshots

  > Panel running live at [panel.xwolf.space](https://panel.xwolf.space)

  ---

  ## 🌍 Built for Africa

  wolfXcore is designed with African hosting providers in mind — with native M-Pesa support via Paystack, KES currency, and a focus on simplicity and performance on lower-bandwidth connections.

  ---

  ## 📄 License

  wolfXcore is proprietary software developed by **WOLF TECH**. All rights reserved.

  ---

  ## 🔗 Links

  - 🌐 Panel: [panel.xwolf.space](https://panel.xwolf.space)
  - 📚 Documentation: [xcoredocs.xwolf.space](https://xcoredocs.xwolf.space)
  - 🐙 GitHub: [github.com/sil3nt-wolf/wolfXcore](https://github.com/sil3nt-wolf/wolfXcore)

  ---

  *wolfXcore · wxn · wolfX panel · Wolf Tech · Game Server Panel · Kenya · Africa*
  