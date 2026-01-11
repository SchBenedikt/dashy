# Setup Instructions for Dashy

Follow these steps to install Dashy from this GitHub repository onto your Nextcloud instance.

## 1. Installation Directory

Navigate to your Nextcloud apps directory. This is usually `apps/` or `custom_apps/` inside your Nextcloud installation folder.

```bash
cd /var/www/nextcloud/apps
# OR
cd /var/www/nextcloud/custom_apps
```

## 2. Clone Repository

Clone the repository into a folder named `dashy`. The folder name **MUST** be `dashy`.

```bash
git clone https://github.com/SchBenedikt/dashy.git dashy
cd dashy
```

## 3. Build Frontend Assets

You need Node.js (v16+) and npm installed to build the Vue.js frontend.

```bash
# Install dependencies
npm install

# Build for production
npm run build
```

**Note:** If you are developing, you can use `npm run dev` to watch for changes (requires allowing the dev port in CSP), but for a stable installation, ALWAYS use `npm run build`.

## 4. Enable the App

Use the Nextcloud command line tool (`occ`) to enable the app. You usually need to run this as the web server user (common users: `www-data`, `www`, `http`).

```bash
# Example for Ubuntu/Debian (adjust path to occ as needed)
sudo -u www-data php /var/www/nextcloud/occ app:enable dashy
```

## 5. Final Permissions Check

Ensure the web server has ownership of the app folder so it can access the files.

```bash
sudo chown -R www-data:www-data /var/www/nextcloud/apps/dashy
```

## 6. Accessing

Go to your Nextcloud instance. Dashy should now be available. You can add the new widgets (Talk, Contacts, Files) via the "+" (Add Widget) button on the dashboard.
