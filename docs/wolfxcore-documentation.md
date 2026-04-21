# wolfXcore Documentation

> **wolfXcore** is a cyberpunk-themed game server management panel built on top of the open-source wolfXcore framework. It lets you host, manage, and sell access to game servers, bots, and applications from one central dashboard.

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
Go to `panel.xwolf.space` and click **Create an Account**. Fill in your email, username, and password.

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
wolfXcore Panel (panel.xwolf.space)
     │  ← API calls (HTTPS)
     ▼
Wings Daemon (node.xwolf.space:8080)
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
2. Your server card on the dashboard shows your assigned **port number** (e.g., `25585`). Combine it with the node address (`node.xwolf.space`) to connect — for example: `node.xwolf.space:25585`. The full connection details are also available under the **Settings** tab.
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
| **GitHub Issues** | [github.com/sil3nt-wolf/wolfXcore/issues](https://github.com/sil3nt-wolf/wolfXcore/issues) | Bug reports, feature requests |
| **GitHub Wiki** | [github.com/sil3nt-wolf/wolfXcore/wiki](https://github.com/sil3nt-wolf/wolfXcore/wiki) | Full documentation |

> For urgent issues, contact the admin directly.

---

## 10. Super Admin System

The Super Admin system is an owner-level security layer built into wolfXcore. It sits above regular admin access and allows the panel owner to manage, promote, or revoke admin privileges — even from users who already have admin access.

### How It Works

- The Super Admin panel has **no sidebar link** — it is only accessible by navigating directly to the URL
- Authentication page: `panel.xwolf.space/admin/wxn-super/auth`
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
openssl rand -hex 32
```

Copy the output (e.g. `a3f9b2c1d4e5...`).

**Step 2 — Add it to your VPS `.env`:**

```bash
echo "SUPER_ADMIN_KEY=YOUR_KEY_HERE" >> /var/www/wolfxcore/.env
```

Replace `YOUR_KEY_HERE` with the key you generated.

**Step 3 — Clear the config cache:**

```bash
cd /var/www/wolfxcore
php artisan config:clear
php artisan config:cache
```

> Store your key somewhere secure (e.g. a password manager). Anyone who knows this key can access the Super Admin panel.

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

wolfXcore is developed in a GitHub-backed repository. The live server at `161.97.100.158` runs from `/var/www/wolfxcore`. Changes committed and pushed to `main` are deployed to the VPS using `git pull` followed by a set of post-pull steps.

### Standard Deployment (from Replit)

Run the sync script from the Replit workspace whenever you want to ship committed changes:

```bash
VPS_HOST=161.97.100.158 VPS_PASSWORD=<root-password> bash scripts/vps-sync.sh
```

What it does:
1. SSH into the VPS as `root`
2. Stash any locally modified files on the VPS (they are **not** deleted — run `git stash pop` to recover)
3. Switch to `main` if needed, then run `git pull --ff-only origin main`
4. Run `composer install --no-dev`
5. Run `php artisan migrate --force`
6. Clear all Laravel caches (`config`, `view`, `route`, `optimize`, `event`)
7. Reload PHP-FPM and restart queue workers

### Running Directly on the VPS

If you are already logged into the VPS you can run the script locally:

```bash
cd /var/www/wolfxcore
bash scripts/vps-sync.sh --local
```

### Options

| Flag | Effect |
|------|--------|
| *(none)* | Stash local changes, pull, run post-pull steps |
| `--local` | Skip SSH — run everything on the current machine |
| `--reset` | **Hard-reset** local changes instead of stashing (changes are lost) |
| `--dry-run` | Print every command that would run without executing anything |

### Keeping the VPS Git-Clean

To prevent local changes from accumulating on the VPS again:

- **Never edit files directly on the VPS.** Always make changes in the Replit/GitHub repo, commit them, and deploy with the script.
- If an emergency hotfix must be applied directly on the VPS, immediately commit the same change in the repo and run the sync script so the two environments stay in sync.
- After the sync script runs, `git status` inside `/var/www/wolfxcore` should always be clean.

### One-Time VPS Setup (if git pull has never worked)

If the VPS git remote is not pointing to GitHub yet:

```bash
cd /var/www/wolfxcore
git remote -v                     # check current remote
# If missing or wrong:
git remote set-url origin https://github.com/<org>/wolfxcore.git
git fetch origin
```

Then run `bash scripts/vps-sync.sh --local --reset` to do a hard initial sync.

---

*wolfXcore is a custom fork of [wolfXcore Panel](https://wolfxcore.io) — MIT Licensed.*
