# wolfXcore Documentation

> **wolfXcore** is a cyberpunk-themed game server management panel built on top of the Pterodactyl framework. It lets you host, manage, and sell access to game servers, bots, and applications from one central dashboard.

---

## Table of Contents

1. [What is wolfXcore?](#1-what-is-wolfxcore)
2. [Getting Started as a User](#2-getting-started-as-a-user)
3. [Managing Your Server](#3-managing-your-server)
4. [Server Use Cases](#4-server-use-cases)
5. [Plans & Pricing](#5-plans--pricing)
6. [Admin Panel Guide](#6-admin-panel-guide)
7. [Nodes & Infrastructure](#7-nodes--infrastructure)
8. [FAQ](#8-faq)
9. [Getting Support](#9-getting-support)
10. [Super Admin System](#10-super-admin-system)
11. [Deploying Code Changes to the VPS](#11-deploying-code-changes-to-the-vps)
12. [Fresh VPS Panel Deployment](#12-fresh-vps-panel-deployment)
13. [Wings Node Installation](#13-wings-node-installation)
14. [Known Gotchas & Fixes](#14-known-gotchas--fixes)

---

## 1. What is wolfXcore?

wolfXcore is a self-hosted game server management panel. It provides:

- A **web-based control panel** to start, stop, restart, and configure your servers
- **Real-time console access** directly from your browser — no SSH needed
- **File manager** to upload, edit, and delete server files
- **Automated backups** to protect your data
- **Database management** for games that need MySQL/SQLite databases
- **Multi-user support** — grant sub-users access to specific servers with granular permissions
- **Multiple game support** — Minecraft, Rust, ARK, CS2, Valheim, FiveM, and 50+ more

---

## 2. Getting Started as a User

### Step 1 — Create an Account
Go to `core.xwolf.space` and click **Create an Account**. Fill in your email, username, and password.

### Step 2 — Choose a Plan
Browse the available plans on your dashboard. Each plan defines how much RAM, CPU, and disk space your server gets.

### Step 3 — Contact Admin to Provision
After picking your plan, contact the administrator (see [Getting Support](#9-getting-support)) with:
- Your **username/email** on the panel
- The **plan** you want
- What **type of server** you need (Minecraft, bot, web app, etc.)

The admin will create your server and it will appear on your dashboard within minutes.

### Step 4 — Launch Your Server
Click your server on the dashboard, then press the **Start** button. Your server will boot in seconds.

---

## 3. Managing Your Server

Once your server is provisioned, you have full control from the panel.

### Console
The **Console** tab shows your server's live output. You can type commands directly into the console input — just like an SSH terminal, but in your browser.

### File Manager
The **Files** tab lets you:
- Browse all server files and folders
- Upload files from your computer
- Edit config files directly in the browser (`.properties`, `.yml`, `.json`, etc.)
- Create, rename, move, and delete files

### Backups
The **Backups** tab lets you create snapshots of your server. A backup saves all your files so you can restore to a previous state if something breaks.

> **Tip:** Create a backup before installing mods, updating the game, or making major config changes.

### Databases
The **Databases** tab allows you to create MySQL databases for your server. Games like Minecraft plugins and web apps often need a database to store player data.

### Schedules
The **Schedules** tab lets you automate tasks — for example, automatically restart your server at 3 AM every day, or run a backup every 6 hours.

### Sub-users
The **Users** tab lets you invite other users to help manage your server. You control exactly what they can see and do — for example, you can give a friend console access but prevent them from deleting files.

### Settings
The **Settings** tab shows your server's resource limits, SFTP credentials (for connecting via FileZilla or WinSCP), and reinstall/reset options.

---

## 4. Server Use Cases

### 🎮 Game Servers
wolfXcore supports over 50 game server types out of the box:

| Game | Notes |
|------|-------|
| Minecraft (Java & Bedrock) | Vanilla, Spigot, Paper, Forge, Fabric |
| Rust | Full mod support via Oxide/uMod |
| ARK: Survival Evolved | Multi-map support |
| Counter-Strike 2 | Competitive & casual |
| Valheim | Plus, Mistlands support |
| FiveM (GTA V RP) | Full resource management |
| Terraria | TShock support |
| 7 Days to Die | Dedicated server |

### 🤖 Discord Bots
Run your Discord bots 24/7 without needing your PC to be on:
- **Node.js** bots (Discord.js, Eris)
- **Python** bots (discord.py, nextcord, hikari)
- **Any language** — if it runs in Docker, it runs here

### 🌐 Web Applications & APIs
Host backend services with direct port access:
- Node.js / Express APIs
- Python Flask / FastAPI
- Static sites and React apps (with a reverse proxy)

### 📦 Custom Applications
Anything that runs in a Docker container can run on wolfXcore:
- Databases (Redis, MongoDB, PostgreSQL)
- Scheduled jobs and cron tasks
- Web scrapers
- Trading/arbitrage bots
- Monitoring tools

---

## 5. Plans & Pricing

Plans are managed by the administrator. Each plan includes:

| Resource | Description |
|----------|-------------|
| **RAM** | How much memory your server has (e.g., 2 GB = 2048 MB) |
| **CPU** | Percentage of a CPU core (100% = 1 full core) |
| **Disk** | Storage space for server files |
| **Databases** | Number of MySQL databases you can create |
| **Backups** | Number of backup slots available |

Contact the admin to see current pricing or to upgrade/downgrade your plan.

---

## 6. Admin Panel Guide

The admin panel is accessible at `/admin`. Only users marked as **root admin** can access it.

### Overview
Shows total server count, user count, node count, and active plans at a glance.

### Servers
Manage all servers across all users. You can:
- Create servers on behalf of users
- Change resource limits (RAM, CPU, disk)
- View and access any server's console
- Suspend or delete servers

### Users
Manage user accounts. You can:
- Create new users
- Promote users to admin
- Reset passwords
- View all servers owned by a user

### Nodes
Nodes are the physical or virtual machines that actually run the servers. Each node runs the **Wings** daemon which communicates with the panel.

To add a node:
1. Go to **Admin → Nodes → Create New**
2. Fill in the node's public hostname/IP
3. Follow the Wings installation steps shown on the configuration tab
4. Allocations (IPs + ports) must be added before servers can be created on the node

### Plans & Pricing
Create hosting plans that define RAM, CPU, disk, and monthly price. These appear on the user dashboard when a user has no servers.

### Nests & Eggs
**Nests** are categories (e.g., "Minecraft", "Rust"). **Eggs** are the specific server templates within a nest. Each egg defines:
- The Docker image to use
- Default startup command
- Environment variables
- Install script

---

## 7. Nodes & Infrastructure

### How it works
```
User Browser
     │
     ▼
wolfXcore Panel (core.xwolf.space)
     │  ← API calls (HTTPS)
     ▼
Wings Daemon (corenode.xwolf.space:8080)
     │  ← Docker
     ▼
Game Server Containers
```

### Wings (the node daemon)
Wings is a Go-based daemon that runs on each node. It:
- Receives commands from the panel (start, stop, install, backup)
- Manages Docker containers for each server
- Streams console output back to the panel
- Handles file operations, backups, and databases

### Requirements per node
- Ubuntu 20.04+ or Debian 11+
- Docker installed
- Minimum 2 GB RAM (more = more servers)
- Public IP or hostname reachable from the panel

---

## 8. FAQ

**Q: My server is stuck on "Installing"**
A: The egg's install script ran into an issue. Check the server's Install Log in the admin panel, or delete and recreate the server with a different egg/version.

**Q: I can't connect to my game server**
A: Make sure:
1. The server is showing as **Running** in the console
2. Your server card on the dashboard shows your assigned **port number** (e.g., `25585`). Combine it with the node address (`corenode.xwolf.space`) to connect — for example: `corenode.xwolf.space:25585`. The full connection details are also available under the **Settings** tab.
3. The port is opened on the node's firewall

**Q: My server ran out of memory and crashed**
A: The RAM limit was hit. Contact admin to upgrade your plan, or reduce the amount of plugins/mods on your server.

**Q: Can I upload a world/save file?**
A: Yes — use the **File Manager** to upload a `.zip` and then extract it, or connect via SFTP using the credentials in your server's **Settings** tab.

**Q: How do I get SFTP access?**
A: Go to your server → **Settings** → copy the SFTP host and port. Use FileZilla or WinSCP. Your username and password are your wolfXcore panel credentials.

**Q: How do I restart automatically?**
A: Go to **Schedules** and create a new schedule. Set it to run a `restart` action on a cron schedule (e.g., `0 3 * * *` for 3 AM daily).

---

## 9. Getting Support

| Channel | Link | Best For |
|---------|------|----------|
| **Discord** | [discord.gg/tNYvK42j](https://discord.gg/tNYvK42j) | Real-time help, community chat |
| **GitHub Issues** | [github.com/SilentWolf-Kenya/wolfXcore/issues](https://github.com/SilentWolf-Kenya/wolfXcore/issues) | Bug reports, feature requests |
| **GitHub Wiki** | [github.com/SilentWolf-Kenya/wolfXcore/wiki](https://github.com/SilentWolf-Kenya/wolfXcore/wiki) | Full documentation |

> For urgent issues, contact the admin directly.

---

## 10. Super Admin System

The Super Admin system is an owner-level security layer built into wolfXcore. It sits above regular admin access and allows the panel owner to manage, promote, or revoke admin privileges — even from users who already have admin access.

### How It Works

- The Super Admin panel has **no sidebar link** — it is only accessible by navigating directly to the URL
- Authentication page: `core.xwolf.space/admin/wxn-super/auth`
- Only the correct secret key unlocks the panel
- Once authenticated, the session stays active until you log out or close the browser
- Closing the browser or logging out ends the Super Admin session

### What You Can Do in the Super Admin Panel

| Action | Description |
|--------|-------------|
| View all admins | See every user with admin access |
| Revoke admin | Remove admin access from any user |
| Promote users | Grant admin access to any regular user |
| View all users | Browse and manage every account on the panel |

> **Your own account is always protected.** You cannot accidentally demote yourself from within the Super Admin panel.

---

### Setup During Deployment

The Super Admin key is stored in your server's `.env` file and is **never stored in the database or exposed in the panel UI**.

**Step 1 — Generate a strong random key:**

```bash
openssl rand -hex 20
```

Copy the output (e.g. `8eb7064333cb264dad10e66edfeaba5e04aaf6e4`).

**Step 2 — Add it to your VPS `.env`:**

```bash
echo "SUPER_ADMIN_KEY=YOUR_KEY_HERE" >> /var/www/wolfxcore/.env
```

Replace `YOUR_KEY_HERE` with the key you generated.

**Step 3 — Clear the config cache and restart PHP-FPM:**

```bash
cd /var/www/wolfxcore
php artisan config:clear
php artisan config:cache
systemctl restart php8.3-fpm
```

> OPcache is enabled with `validate_timestamps=0` for performance. This means PHP-FPM worker processes do **not** automatically notice when `bootstrap/cache/config.php` changes. You must restart PHP-FPM after every `config:cache` run, otherwise the live web process keeps serving the old cached config.

> Store your key somewhere secure (e.g. a password manager). Anyone who knows this key can access the Super Admin panel.

> **Note:** The key is read via `config('wolfxcore.super_admin_key')` which maps to `config/wolfxcore.php`. This mapping (`'super_admin_key' => env('SUPER_ADMIN_KEY', '')`) is already in the codebase — do not remove it or the key will silently fail even if set in `.env`.

---

### Changing the Key

To change your Super Admin key at any time:

1. Edit `/var/www/wolfxcore/.env` and update `SUPER_ADMIN_KEY=`
2. Run `php artisan config:clear && php artisan config:cache`
3. All existing Super Admin sessions are immediately invalidated

---

### Security Notes

- The key is compared using timing-safe comparison to prevent brute-force attacks
- The Super Admin URL prefix (`/admin/wxn-super/`) is not listed anywhere publicly
- Regular admins cannot see the Super Admin panel contents — only the locked sidebar link
- There is no "forgot key" — if you lose it, update the `.env` and run config:cache

---

## 11. Deploying Code Changes to the VPS

wolfXcore is developed in a GitHub-backed repository at `github.com/SilentWolf-Kenya/wolfXcore`. The live panel at `core.xwolf.space` runs from `/var/www/wolfxcore` on the VPS. All changes must be committed and pushed from the Replit workspace, then pulled to the VPS.

### Commit → Push → Pull Workflow

**Step 1 — Make your changes in Replit, then commit:**
```bash
git add -A
git commit -m "your change description"
```

**Step 2 — Push to GitHub (Replit handles the token via Secrets):**
```bash
git remote set-url origin "https://${GITHUB_TOKEN}@github.com/SilentWolf-Kenya/wolfXcore.git"
git push origin main
git remote set-url origin "https://github.com/SilentWolf-Kenya/wolfXcore.git"
```

**Step 3 — Pull and update on the VPS:**
```bash
ssh root@<VPS_IP>
cd /var/www/wolfxcore
git fetch origin main && git reset --hard origin/main
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan config:clear && php artisan config:cache
php artisan route:clear && php artisan route:cache
php artisan view:clear && php artisan view:cache
systemctl restart php8.3-fpm
supervisorctl restart wolfxcore-worker:*
```

### Frontend-Only Changes (JS/TS/CSS)

Frontend assets (`public/assets/`) are built locally in Replit (not on the VPS):
```bash
# In Replit workspace:
yarn build          # or: npm run build
git add -f public/assets/
git commit -m "build: update frontend assets"
# Then push + pull as above
```

> The `public/assets/` directory is gitignored by default. The `-f` flag force-adds the built files.

### Database Migrations

Always run migrations after pulling code that adds new migrations:
```bash
php artisan migrate --force
```

### Keeping the VPS Clean

- Never edit files directly on the VPS — always commit in Replit and pull to VPS
- `git status` inside `/var/www/wolfxcore` should always show a clean working tree
- If the VPS has local edits you want to discard: `git reset --hard origin/main`

---

## 12. Fresh VPS Panel Deployment

This section documents the complete process for deploying wolfXcore to a brand-new Ubuntu 24.04 VPS.

### Prerequisites

- Ubuntu 24.04 VPS with a public IP
- Domains pointing to the VPS: `core.xwolf.space` (panel), `corenode.xwolf.space` (Wings)
- Root SSH access
- GitHub repository: `github.com/SilentWolf-Kenya/wolfXcore`

### Step 1 — System packages

```bash
apt-get update && apt-get upgrade -y
apt-get install -y software-properties-common curl git unzip
add-apt-repository ppa:ondrej/php -y && apt-get update
apt-get install -y php8.3 php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml \
  php8.3-curl php8.3-mbstring php8.3-zip php8.3-bcmath php8.3-gd \
  php8.3-tokenizer php8.3-common php8.3-readline php8.3-intl \
  mariadb-server redis-server nginx certbot python3-certbot-nginx
```

### Step 2 — MariaDB database

```bash
mysql -e "CREATE DATABASE wolfxcore DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER 'wolfxcore'@'127.0.0.1' IDENTIFIED BY 'WolfXcore@DB2026!';"
mysql -e "GRANT ALL PRIVILEGES ON wolfxcore.* TO 'wolfxcore'@'127.0.0.1';"
mysql -e "FLUSH PRIVILEGES;"
```

### Step 3 — Clone and configure code

```bash
git clone https://github.com/SilentWolf-Kenya/wolfXcore.git /var/www/wolfxcore
cd /var/www/wolfxcore

# Install Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

composer install --no-dev --optimize-autoloader --no-interaction

cp .env.example .env
# Edit .env:
#   APP_URL=https://core.xwolf.space
#   DB_HOST=127.0.0.1, DB_DATABASE=wolfxcore, DB_USERNAME=wolfxcore, DB_PASSWORD=WolfXcore@DB2026!
#   SESSION_COOKIE=wolfxcore_session
#   CACHE_DRIVER=redis, SESSION_DRIVER=redis, QUEUE_DRIVER=redis
nano .env

# Generate key
php artisan key:generate --force

# Migrate and seed
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder
php artisan db:seed --class=EggSeeder

# Permissions
chown -R www-data:www-data /var/www/wolfxcore
chmod -R 755 /var/www/wolfxcore/storage /var/www/wolfxcore/bootstrap/cache
```

### Step 4 — PHP-FPM

```bash
# Edit /etc/php/8.3/fpm/php.ini:
#   upload_max_filesize = 100M
#   post_max_size = 100M
systemctl enable --now php8.3-fpm
```

### Step 5 — Nginx + SSL

```bash
# Get SSL certificate
certbot certonly --nginx -d core.xwolf.space --non-interactive --agree-tos --email admin@xwolf.space
```

Create `/etc/nginx/sites-available/wolfxcore`:
```nginx
server {
    listen 443 ssl http2;
    server_name core.xwolf.space;
    root /var/www/wolfxcore/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/core.xwolf.space/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/core.xwolf.space/privkey.pem;
    ssl_session_cache shared:SSL:10m;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Robots-Tag none;
    add_header Content-Security-Policy "frame-ancestors 'self'";
    add_header X-Frame-Options DENY;
    add_header Referrer-Policy same-origin;

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param PHP_VALUE "upload_max_filesize = 100M \n post_max_size=100M";
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 300;
    }

    location ~ /\.ht { deny all; }
}
server {
    listen 80;
    server_name core.xwolf.space;
    return 301 https://$host$request_uri;
}
```

```bash
ln -s /etc/nginx/sites-available/wolfxcore /etc/nginx/sites-enabled/wolfxcore
nginx -t && systemctl reload nginx
```

### Step 6 — Supervisor (queue workers)

Create `/etc/supervisor/conf.d/wolfxcore.conf`:
```ini
[program:wolfxcore-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/wolfxcore/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/wolfxcore/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
supervisorctl reread && supervisorctl update && supervisorctl start wolfxcore-worker:*
```

### Step 7 — Cron scheduler

```bash
echo "* * * * * www-data php /var/www/wolfxcore/artisan schedule:run >> /dev/null 2>&1" \
  > /etc/cron.d/wolfxcore
```

### Step 8 — Create admin user

```bash
cd /var/www/wolfxcore
php artisan tinker --no-interaction << 'PHP'
$u = new \Pterodactyl\Models\User;
$u->name_first = 'Silent'; $u->name_last = 'Wolf';
$u->username = 'wolf'; $u->email = 'admin@xwolf.space';
$u->password = bcrypt('YOUR_PASSWORD');
$u->root_admin = 1;
$u->save();
echo 'Admin created: '.$u->email.PHP_EOL;
PHP
```

---

## 13. Wings Node Installation

This documents how the Wings daemon was installed for the `wolfxcore` node (`corenode.xwolf.space`).

### Prerequisites

- The panel is already running at `core.xwolf.space`
- `corenode.xwolf.space` DNS points to the VPS IP (same VPS for single-node setups)
- Docker installed

### Step 1 — Install Docker

```bash
curl -fsSL https://get.docker.com | sh
systemctl enable --now docker
```

### Step 2 — Download Wings

```bash
mkdir -p /etc/pterodactyl /var/lib/pterodactyl/volumes /var/log/pterodactyl
curl -L -o /usr/local/bin/wings \
  https://github.com/pterodactyl/wings/releases/latest/download/wings_linux_amd64
chmod +x /usr/local/bin/wings
```

### Step 3 — SSL certificate for the node

```bash
# Wings serves HTTPS directly on port 8080 — needs its own cert
# Stop Nginx temporarily or use webroot method:
mkdir -p /var/www/certbot
# Add a temporary Nginx server block for corenode.xwolf.space on port 80, then:
certbot certonly --webroot -w /var/www/certbot -d corenode.xwolf.space \
  --non-interactive --agree-tos --email admin@xwolf.space
```

### Step 4 — Create the node in the panel

Connect the node to the panel database (or use the admin UI at `/admin/nodes/new`):

```bash
cd /var/www/wolfxcore
php artisan tinker --no-interaction << 'PHP'
$loc = \Pterodactyl\Models\Location::firstOrCreate(
    ['short' => 'nairobi'], ['long' => 'Nairobi, Kenya']
);
$node = new \Pterodactyl\Models\Node;
$node->name            = 'wolfxcore';
$node->description     = 'wolfXcore game server node - Nairobi';
$node->location_id     = $loc->id;
$node->fqdn            = 'corenode.xwolf.space';
$node->scheme          = 'https';
$node->behind_proxy    = false;
$node->public          = true;
$node->memory          = 6000;
$node->memory_overallocate = 0;
$node->disk            = 100000;
$node->disk_overallocate   = 0;
$node->upload_size     = 100;
$node->daemon_token_id = substr(str_replace(['+','/','='],'',base64_encode(random_bytes(16))),0,16);
$node->daemon_token    = encrypt(str_replace(['+','/','='],'',base64_encode(random_bytes(32))));
$node->daemonListen    = 8080;
$node->daemonSFTP      = 2022;
$node->daemonBase      = '/var/lib/pterodactyl';
$node->save();
$conf = $node->getConfiguration();
echo 'Node ID: '.$node->id.PHP_EOL;
echo 'TOKEN_ID: '.$conf['token_id'].PHP_EOL;
echo 'TOKEN: '.$conf['token'].PHP_EOL;
PHP
```

Note the `TOKEN_ID` and `TOKEN` values for the next step.

### Step 5 — Write Wings config

Create `/etc/pterodactyl/config.yml`:
```yaml
debug: false
uuid: <any-uuid-v4>
token_id: <TOKEN_ID from above>
token: <TOKEN from above>
api:
  host: 0.0.0.0
  port: 8080
  ssl:
    enabled: true
    cert: /etc/letsencrypt/live/corenode.xwolf.space/fullchain.pem
    key: /etc/letsencrypt/live/corenode.xwolf.space/privkey.pem
  upload_limit: 100
system:
  data: /var/lib/pterodactyl/volumes
  sftp:
    bind_port: 2022
remote: https://core.xwolf.space
allowed_mounts: []
```

### Step 6 — Systemd service for Wings

Create `/etc/systemd/system/wings.service`:
```ini
[Unit]
Description=wolfXcore Wings Daemon
After=docker.service network-online.target
Requires=docker.service
StartLimitIntervalSec=180
StartLimitBurst=30

[Service]
User=root
WorkingDirectory=/etc/pterodactyl
LimitNOFILE=4096
PIDFile=/var/run/wings/daemon.pid
ExecStart=/usr/local/bin/wings
Restart=on-failure
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

```bash
systemctl daemon-reload
systemctl enable --now wings
systemctl status wings    # should show: active (running)
```

### Step 7 — Add allocations for the node

Servers cannot be created until port allocations are added to the node. In the panel admin:

1. Go to **Admin → Nodes → wolfxcore → Allocation**
2. Add the node's IP and a port range, e.g.: IP `0.0.0.0`, ports `25565-25600`

Or via the panel API:
```bash
# Add ports 25565-25600 to node ID 1
cd /var/www/wolfxcore
php artisan tinker --no-interaction << 'PHP'
$node = \Pterodactyl\Models\Node::find(1);
for ($p = 25565; $p <= 25600; $p++) {
    \Pterodactyl\Models\Allocation::firstOrCreate(
        ['node_id' => $node->id, 'port' => $p],
        ['ip' => '0.0.0.0']
    );
}
echo 'Allocations added: '.(\Pterodactyl\Models\Allocation::where('node_id',1)->count()).PHP_EOL;
PHP
```

### Step 8 — Verify connectivity

```bash
# Wings should return 401 (auth required) — confirming it's live:
curl -s -o /dev/null -w '%{http_code}' https://corenode.xwolf.space:8080/
# Expected: 401

# Check Wings logs:
journalctl -u wings -f
```

### Current Node Summary

| Setting | Value |
|---------|-------|
| Node name | wolfxcore |
| FQDN | corenode.xwolf.space |
| Port | 8080 (HTTPS) |
| SFTP | 2022 |
| Memory limit | 6,000 MB |
| Disk limit | 100,000 MB (100 GB) |
| Docker data | `/var/lib/pterodactyl/volumes` |
| Wings binary | `/usr/local/bin/wings` |
| Wings config | `/etc/pterodactyl/config.yml` |
| Systemd service | `wings.service` |

> **Eggs (server templates)** are installed separately by the administrator via the admin panel at `/admin/nests`. wolfXcore ships with default Minecraft and Discord bot eggs from the standard Pterodactyl egg repository.

---

## 14. Known Gotchas & Fixes

Issues encountered during the wolfXcore deployment and their solutions. Read this before debugging a broken fresh install.

---

### Admin panel shows broken layout / missing icons

**Symptom:** The admin panel (`/admin`) loads but has no icons, broken info boxes, and the page heading runs together with the subtitle.

**Cause:** The `Theme::getUrl()` helper in `app/Extensions/Themes/Theme.php` generates paths like `/themes/wolfxcore/vendor/...`, but after a fresh `git clone` the theme folder on disk is still named `pterodactyl` at `public/themes/pterodactyl/`. All admin CSS (Bootstrap, AdminLTE, FontAwesome) returns 404.

**Fix (already applied in the repo):** The folder has been renamed to `public/themes/wolfxcore/` to match the URL path. On any fresh clone this is automatically correct.

**If it happens again:** Verify the folder name on the VPS:
```bash
ls /var/www/wolfxcore/public/themes/
# Should show: wolfxcore
# If it shows: pterodactyl — run:
mv /var/www/wolfxcore/public/themes/pterodactyl /var/www/wolfxcore/public/themes/wolfxcore
```

---

### Super Admin key returns "not configured on this server"

**Symptom:** You set `SUPER_ADMIN_KEY` in `.env` and ran `config:cache`, but the Super Admin auth page still shows the error.

**Cause:** The `.env` key is only readable by Laravel if it has a corresponding entry in a config file. The key `config('wolfxcore.super_admin_key')` requires the line `'super_admin_key' => env('SUPER_ADMIN_KEY', '')` to exist inside `config/wolfxcore.php`.

**Fix (already applied in the repo):** That line is now present in `config/wolfxcore.php`. Never remove it.

**Verify it's working:**
```bash
cd /var/www/wolfxcore
php artisan tinker --no-interaction <<< 'echo config("wolfxcore.super_admin_key");'
# Should print your key, not an empty line
```

---

### PHP class not found errors (wolfXcoreException, wolfXcore\Models\*, etc.)

**Symptom:** Laravel logs show errors like `Class "wolfXcore\Http\Controllers\Admin\SuperAdminController" not found` or `Class "Pterodactyl\Exceptions\wolfXcoreException" not found`.

**Cause:** The wolfXcore rebranding sed-replaced `Pterodactyl` → `wolfXcore` across all files, including inside blade templates that contain inline PHP (`{{ \wolfXcore\Models\Server::count() }}`), and in PHP `extends` / `use` statements that reference internal class names which were never renamed.

**Fix (already applied in the repo):**
- Blade files: all PHP class references (`\wolfXcore\Models\*`, `use wolfXcore\Http\...`) reverted to `\Pterodactyl\Models\*`, `use Pterodactyl\Http\...`
- Exception classes: all `extends wolfXcoreException` reverted to `extends PterodactylException`
- PHP autoload namespace in `composer.json` stays as `"Pterodactyl\\": "app/"` — the internal namespace was never renamed

**Rule:** Only user-facing display text (page titles, console output, HTML) uses wolfXcore branding. PHP namespaces, class names, and internal identifiers all stay as `Pterodactyl`.

---

### Assets load slowly on first visit

**Symptom:** The panel feels slow, especially on African mobile connections.

**Fix (already applied on the VPS):**
- Gzip enabled for all JS/CSS in `/etc/nginx/nginx.conf`
- 1-year `Cache-Control: immutable` headers for all hashed `/assets/*` files
- PHP-FPM switched to static pool with 10 workers
- OPcache tuned to 256 MB with timestamp validation disabled

The main JS bundle compresses from ~530 KB to ~164 KB. After the first load, all assets come from browser cache instantly.

---

### Panel returns 500 after fresh deploy

**Symptom:** `curl https://core.xwolf.space/auth/login` returns HTTP 500.

**Most common cause:** `APP_KEY` is empty or missing in `.env`.

**Check and fix:**
```bash
grep 'APP_KEY' /var/www/wolfxcore/.env
# If empty or missing:
cd /var/www/wolfxcore
php artisan key:generate --force
php artisan config:clear && php artisan config:cache
```

**Second most common cause:** A renamed PHP class still referenced somewhere. Check the Laravel log:
```bash
head -5 /var/www/wolfxcore/storage/logs/laravel-$(date +%Y-%m-%d).log
```

---

*wolfXcore is a custom fork of the Pterodactyl Panel — MIT Licensed.*
