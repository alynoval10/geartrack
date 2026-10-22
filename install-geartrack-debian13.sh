#!/usr/bin/env bash

set -Eeuo pipefail

readonly REPOSITORY="${GEARTRACK_REPOSITORY:-git@github.com:alynoval10/geartrack.git}"
readonly BRANCH="${GEARTRACK_BRANCH:-main}"
readonly APP_DIR="${GEARTRACK_APP_DIR:-/var/www/geartrack-test}"
readonly DEPLOY_USER="geartrack-deploy"
readonly DEPLOY_HOME="/var/lib/geartrack-deploy"

fail() {
    printf '\nERROR: %s\n' "$1" >&2
    exit 1
}

trap 'status=$?; printf "\nInstalasi berhenti pada baris %s (kode %s).\n" "$LINENO" "$status" >&2; exit "$status"' ERR

if [[ "${EUID}" -ne 0 ]]; then
    fail "Jalankan skrip ini sebagai root: bash install-geartrack-debian13.sh"
fi

if [[ ! -r /etc/os-release ]]; then
    fail "Tidak dapat membaca informasi sistem operasi."
fi

# shellcheck disable=SC1091
source /etc/os-release

if [[ "${ID:-}" != "debian" || "${VERSION_ID:-}" != "13" ]]; then
    fail "Skrip ini khusus Debian 13. Sistem terdeteksi: ${PRETTY_NAME:-tidak diketahui}."
fi

if [[ ! "$APP_DIR" =~ ^/var/www/[A-Za-z0-9._/-]+$ ]]; then
    fail "GEARTRACK_APP_DIR harus berupa path sederhana di bawah /var/www."
fi

if [[ "$(realpath -m -- "$APP_DIR")" != "$APP_DIR" ]]; then
    fail "GEARTRACK_APP_DIR harus berupa path kanonis tanpa . atau .."
fi

if [[ "$APP_DIR" == "/var/www" || "$APP_DIR" == "/var/www/" ]]; then
    fail "GEARTRACK_APP_DIR tidak boleh menunjuk langsung ke /var/www."
fi

if [[ -e "$APP_DIR" ]]; then
    if [[ ! -d "$APP_DIR" || -n "$(find "$APP_DIR" -mindepth 1 -maxdepth 1 -print -quit)" ]]; then
        fail "Folder tujuan $APP_DIR sudah ada dan tidak kosong. Pindahkan file yang ada atau pilih GEARTRACK_APP_DIR lain."
    fi
fi

printf '%s\n' \
    "Skrip ini akan memasang GearTrack dari repository private:" \
    "  Repository : $REPOSITORY" \
    "  Branch     : $BRANCH" \
    "  Folder     : $APP_DIR" \
    "  Web server : Apache + PHP-FPM" \
    "  Database   : SQLite di storage/app/geartrack.sqlite" \
    "" \
    "Paket sistem dan konfigurasi Apache akan diubah. Folder tujuan harus kosong."
read -r -p "Ketik PASANG untuk melanjutkan: " confirmation
[[ "$confirmation" == "PASANG" ]] || fail "Dibatalkan."

default_host="$(hostname -I 2>/dev/null | awk '{print $1}')"
if [[ -z "$default_host" ]]; then
    default_host="localhost"
fi

read -r -p "Domain atau IPv4 untuk GearTrack [$default_host]: " server_name
SERVER_NAME="${server_name:-$default_host}"

if [[ ! "$SERVER_NAME" =~ ^[A-Za-z0-9.-]+$ ]]; then
    fail "Masukkan nama domain atau alamat IPv4 tanpa skema http/https."
fi

if [[ "$SERVER_NAME" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ || "$SERVER_NAME" == "localhost" ]]; then
    tls_default="n"
else
    tls_default="y"
fi

if [[ "$tls_default" == "y" ]]; then
    read -r -p "Pasang HTTPS Let's Encrypt? Pastikan domain sudah diarahkan ke server. [Y/n]: " enable_tls
else
    read -r -p "Pasang HTTPS Let's Encrypt? Pastikan domain sudah diarahkan ke server. [y/N]: " enable_tls
fi
ENABLE_TLS="${enable_tls:-$tls_default}"
ENABLE_TLS="${ENABLE_TLS,,}"

if [[ "$ENABLE_TLS" != "y" && "$ENABLE_TLS" != "n" ]]; then
    fail "Jawab y atau n untuk pilihan HTTPS."
fi

if [[ "$ENABLE_TLS" == "y" ]]; then
    if [[ "$SERVER_NAME" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ || "$SERVER_NAME" == "localhost" ]]; then
        fail "Let's Encrypt memerlukan nama domain publik, bukan alamat IP atau localhost."
    fi

    read -r -p "Email untuk pemberitahuan sertifikat Let's Encrypt: " LE_EMAIL
    [[ "$LE_EMAIL" =~ ^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$ ]] || fail "Format email tidak valid."
    APP_URL="https://$SERVER_NAME"
    SESSION_SECURE_COOKIE="true"
else
    APP_URL="http://$SERVER_NAME"
    SESSION_SECURE_COOKIE="false"
fi

printf '\nMemasang paket sistem Debian 13...\n'
apt-get update
DEBIAN_FRONTEND=noninteractive apt-get install -y ca-certificates git

if ! git ls-remote --exit-code --heads "$REPOSITORY" "$BRANCH" >/dev/null; then
    fail "Repository tidak dapat diakses. Siapkan SSH Deploy Key read-only untuk root di GitHub, lalu jalankan skrip kembali."
fi

DEBIAN_FRONTEND=noninteractive apt-get install -y \
    apache2 \
    composer \
    curl \
    nodejs \
    npm \
    php8.4-bcmath \
    php8.4-cli \
    php8.4-curl \
    php8.4-fpm \
    php8.4-gd \
    php8.4-intl \
    php8.4-mbstring \
    php8.4-opcache \
    php8.4-sqlite3 \
    php8.4-xml \
    php8.4-zip \
    sqlite3 \
    unzip \
    zip

if [[ "$ENABLE_TLS" == "y" ]]; then
    DEBIAN_FRONTEND=noninteractive apt-get install -y certbot python3-certbot-apache
fi

printf '\nMeng-clone repository GearTrack...\n'
mkdir -p "$(dirname "$APP_DIR")"
git clone --depth 1 --branch "$BRANCH" "$REPOSITORY" "$APP_DIR"

if [[ -e "$APP_DIR/.env" ]]; then
    fail "Repository berisi .env. Periksa sumber repository dan jangan menimpa konfigurasi yang sudah ada."
fi

if ! id "$DEPLOY_USER" >/dev/null 2>&1; then
    useradd --system --create-home --home-dir "$DEPLOY_HOME" --shell /usr/sbin/nologin "$DEPLOY_USER"
fi

usermod --append --groups www-data "$DEPLOY_USER"
chown -R "$DEPLOY_USER:www-data" "$APP_DIR"

mkdir -p \
    "$APP_DIR/storage/app/public" \
    "$APP_DIR/storage/app/backups" \
    "$APP_DIR/storage/framework/cache/data" \
    "$APP_DIR/storage/framework/sessions" \
    "$APP_DIR/storage/framework/views" \
    "$APP_DIR/storage/logs" \
    "$APP_DIR/bootstrap/cache"
chown -R "$DEPLOY_USER:www-data" \
    "$APP_DIR/storage" \
    "$APP_DIR/bootstrap/cache"
find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type d -exec chmod 2770 {} +
find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type f -exec chmod 0660 {} +

cp "$APP_DIR/.env.example" "$APP_DIR/.env"

set_env() {
    local key="$1"
    local value="$2"
    local env_file="$APP_DIR/.env"

    if grep -q "^${key}=" "$env_file"; then
        sed -i "/^${key}=/c\\${key}=${value}" "$env_file"
    else
        printf '%s=%s\n' "$key" "$value" >> "$env_file"
    fi
}

set_env APP_NAME 'GearTrack'
set_env APP_ENV 'production'
set_env APP_DEBUG 'false'
set_env APP_URL "$APP_URL"
set_env DB_CONNECTION 'sqlite'
set_env DB_DATABASE "$APP_DIR/storage/app/geartrack.sqlite"
set_env SESSION_DRIVER 'database'
set_env SESSION_SECURE_COOKIE "$SESSION_SECURE_COOKIE"
set_env FILESYSTEM_DISK 'local'
set_env BACKUP_ADMIN_EMAILS ''
touch "$APP_DIR/storage/app/geartrack.sqlite"
chown "$DEPLOY_USER:www-data" "$APP_DIR/storage/app/geartrack.sqlite"
chmod 0660 "$APP_DIR/storage/app/geartrack.sqlite"
chown "$DEPLOY_USER:www-data" "$APP_DIR/.env"
chmod 0640 "$APP_DIR/.env"

printf '\nMenyiapkan Apache...\n'
cat > /etc/apache2/sites-available/geartrack-test.conf <<EOF
<VirtualHost *:80>
    ServerName $SERVER_NAME
    DocumentRoot $APP_DIR/public

    <Directory $APP_DIR/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/geartrack-test-error.log
    CustomLog \${APACHE_LOG_DIR}/geartrack-test-access.log combined
</VirtualHost>
EOF

a2enmod proxy_fcgi setenvif rewrite headers >/dev/null
a2enconf php8.4-fpm >/dev/null
a2ensite geartrack-test.conf >/dev/null
apache2ctl configtest
systemctl enable --now php8.4-fpm apache2
systemctl reload apache2

if [[ "$ENABLE_TLS" == "y" ]]; then
    certbot --apache --non-interactive --agree-tos --redirect --email "$LE_EMAIL" -d "$SERVER_NAME"
fi

printf '\nMemasang dependency dan menyiapkan database GearTrack...\n'
runuser -u "$DEPLOY_USER" -- env HOME="$DEPLOY_HOME" bash -c "cd '$APP_DIR' && composer install --no-dev --optimize-autoloader --no-interaction"
runuser -u "$DEPLOY_USER" -- env HOME="$DEPLOY_HOME" bash -c "cd '$APP_DIR' && npm ci && npm run build"
runuser -u "$DEPLOY_USER" -- env HOME="$DEPLOY_HOME" bash -c "cd '$APP_DIR' && php artisan key:generate --force && php artisan migrate --force && php artisan db:seed --force && php artisan storage:link && php artisan optimize"

if [[ "$ENABLE_TLS" == "n" ]]; then
    printf '\nPERINGATAN: situs menggunakan HTTP. Gunakan hanya untuk pengujian tertutup; jangan buat akun Administrator melalui jaringan publik tanpa HTTPS.\n'
fi

printf '\nInstalasi selesai. Buka %s\n' "$APP_URL"
printf 'GearTrack akan menampilkan formulir pengaturan awal jika belum ada Administrator.\n'
printf 'Pastikan DocumentRoot Apache tetap menunjuk ke %s/public.\n' "$APP_DIR"
