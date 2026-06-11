# Deploying gatto to gatto-piccolo.com (Hetzner CX23, Ubuntu)

Two pieces, one VPS:
- `gatto-piccolo.com` → Angular dashboard (static, served by nginx)
- `api.gatto-piccolo.com` → Laravel API (php-fpm)

## 0. DNS
At your registrar, point both names at the VPS public IP:
```
A   gatto-piccolo.com       <VPS_IP>
A   www.gatto-piccolo.com   <VPS_IP>
A   api.gatto-piccolo.com   <VPS_IP>
```

## 1. Server packages
```bash
sudo apt update
sudo apt install -y nginx postgresql php-fpm php-cli php-pgsql php-mbstring \
  php-xml php-curl php-zip unzip git certbot python3-certbot-nginx
# Composer
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
# Node 20+ (to build Angular)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash - && sudo apt install -y nodejs
ls /run/php/    # note your php-fpm socket name (e.g. php8.4-fpm.sock)
```

## 2. Firewall
```bash
sudo ufw allow 'Nginx Full'    # opens 80 + 443 (22 already allowed)
sudo ufw status
```

## 3. Database
```bash
sudo -u postgres psql -c "CREATE DATABASE gatto;"
sudo -u postgres psql -c "CREATE USER gatto WITH PASSWORD 'change-me';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE gatto TO gatto;"
sudo -u postgres psql -d gatto -c "GRANT ALL ON SCHEMA public TO gatto;"
```

## 4. Code layout
```bash
sudo mkdir -p /var/www/gatto && sudo chown -R $USER:www-data /var/www/gatto
cd /var/www/gatto
git clone <your-api-repo>        api
git clone <your-dashboard-repo>  src-dashboard
```

## 5. Laravel env
```bash
cd /var/www/gatto/api
cp deploy/.env.production.example .env      # then edit DB password + ANTHROPIC_API_KEY
php artisan key:generate
```
Make sure storage is writable by php-fpm:
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
```

## 6. nginx vhosts
```bash
sudo cp deploy/nginx-gatto-piccolo.conf      /etc/nginx/sites-available/gatto-piccolo.com
sudo cp deploy/nginx-api-gatto-piccolo.conf  /etc/nginx/sites-available/api.gatto-piccolo.com
# fix the php-fpm socket in the api vhost to match `ls /run/php/`
sudo ln -s /etc/nginx/sites-available/gatto-piccolo.com     /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/api.gatto-piccolo.com /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

## 7. TLS (Let's Encrypt)
```bash
sudo certbot --nginx -d gatto-piccolo.com -d www.gatto-piccolo.com -d api.gatto-piccolo.com
```
certbot adds the 443 server blocks and the 80→443 redirect automatically.

## 8. Build + release
```bash
cd /var/www/gatto/api
bash deploy/deploy.sh
```
This installs deps, migrates, seeds the demo ledger, caches config, builds Angular,
publishes it to the nginx root, and reloads nginx.

## 9. Verify
```bash
curl https://api.gatto-piccolo.com/api/summary      # JSON
# open https://gatto-piccolo.com → dashboard, switch EN/ET, ask a question
```

## Notes
- **Demo data**: `ERP_FAKE=true`, so the live site shows the seeded ledger
  (the same verifiable figures). Flip to a live SmartAccounts source later via
  `smartaccounts:connect` + `ERP_FAKE=false`.
- **Cost/abuse**: `/api/ask` spends Anthropic credits. It's rate-limited
  (`throttle:10,1`). Watch usage, or put it behind a simple token if the site
  gets traffic.
- **Redeploy**: `git pull` in both repos, then `bash deploy/deploy.sh` again.
