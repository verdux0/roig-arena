#!/usr/bin/env bash

if [ -z "${BASH_VERSION:-}" ]; then
    exec bash "$0" "$@"
fi

set -Eeuo pipefail

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log() {
    echo -e "${BLUE}>>> $*${NC}"
}

ok() {
    echo -e "${GREEN}[OK] $*${NC}"
}

warn() {
    echo -e "${YELLOW}[WARN] $*${NC}"
}

fail() {
    echo -e "${RED}[ERROR] $*${NC}"
    exit 1
}

has_cmd() {
    command -v "$1" >/dev/null 2>&1
}

upsert_env_var() {
    local key="$1"
    local value="$2"

    if grep -qE "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    elif grep -qE "^#\s*${key}=" .env; then
        sed -i "s|^#\s*${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

version_ge() {
    # Returns success if version $2 is greater or equal than $1
    [ "$(printf '%s\n' "$1" "$2" | sort -V | head -n1)" = "$1" ]
}

install_composer_deps_in_container() {
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$APP_DIR:/var/www/html" \
        -w /var/www/html \
        laravelsail/php84-composer:latest \
        composer install
}

install_docker_ubuntu() {
    if ! has_cmd sudo; then
        fail "No se encontro sudo para instalar Docker automaticamente. Instala Docker y Docker Compose plugin manualmente."
    fi

    if ! has_cmd apt-get; then
        fail "Sistema no compatible con instalacion automatica por apt-get. Instala Docker manualmente."
    fi

    log "Docker no esta instalado. Intentando instalar Docker Engine + Compose plugin..."

    sudo apt-get update
    sudo apt-get install -y ca-certificates curl gnupg

    sudo install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    sudo chmod a+r /etc/apt/keyrings/docker.gpg

    # shellcheck disable=SC1091
    . /etc/os-release
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" \
        | sudo tee /etc/apt/sources.list.d/docker.list >/dev/null

    sudo apt-get update
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    sudo systemctl enable --now docker

    if [ -n "${SUDO_USER:-}" ]; then
        sudo usermod -aG docker "${SUDO_USER}" || true
        warn "Se anadio ${SUDO_USER} al grupo docker. Puede requerir cerrar sesion y volver a entrar."
    else
        sudo usermod -aG docker "$USER" || true
        warn "Se anadio $USER al grupo docker. Puede requerir cerrar sesion y volver a entrar."
    fi
}

ensure_docker() {
    if ! has_cmd docker; then
        install_docker_ubuntu
    fi

    if ! docker compose version >/dev/null 2>&1; then
        fail "Docker Compose plugin no disponible. Instala docker-compose-plugin."
    fi

    if ! docker info >/dev/null 2>&1; then
        fail "Docker existe pero no responde. Arranca el daemon de Docker y vuelve a ejecutar el script."
    fi

    ok "Docker y Docker Compose disponibles."
}

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="${ROOT_DIR}/roig-arena"

[ -d "$APP_DIR" ] || fail "No se encontro la carpeta roig-arena en ${ROOT_DIR}."

cd "$APP_DIR"

log "Preparando entorno para Roig Arena en $APP_DIR"

ensure_docker

if [ ! -f .env ]; then
    cp .env.example .env
    ok "Archivo .env creado desde .env.example"
else
    ok "Archivo .env ya existe"
fi

log "Aplicando variables de entorno base para Sail (MySQL)..."
upsert_env_var "DB_CONNECTION" "mysql"
upsert_env_var "DB_HOST" "mysql"
upsert_env_var "DB_PORT" "3306"
upsert_env_var "DB_DATABASE" "arena"
upsert_env_var "DB_USERNAME" "sail"
upsert_env_var "DB_PASSWORD" "password"
ok "Variables DB_* configuradas para Sail."

log "Instalando dependencias de Composer..."
COMPOSER_DONE=0

if has_cmd composer && has_cmd php; then
    LOCAL_PHP_VERSION="$(php -r 'echo PHP_VERSION;')"
    if version_ge "8.2.0" "$LOCAL_PHP_VERSION"; then
        if composer install; then
            COMPOSER_DONE=1
        else
            warn "composer install local fallo. Se reintenta con contenedor Composer."
        fi
    else
        warn "PHP local ($LOCAL_PHP_VERSION) es < 8.2. Se usa contenedor Composer."
    fi
else
    warn "Composer o PHP local no disponible. Se usa contenedor Composer."
fi

if [ "$COMPOSER_DONE" -ne 1 ]; then
    install_composer_deps_in_container
fi

if [ -f ./vendor/bin/sail ] && [ ! -x ./vendor/bin/sail ]; then
    chmod +x ./vendor/bin/sail
fi

[ -x ./vendor/bin/sail ] || fail "No se pudo generar ./vendor/bin/sail. Revisa el resultado de composer install."
ok "Sail instalado correctamente."

echo ""
ok "Bootstrap completado."
echo ""
echo "Siguiente paso:"
echo "  Ejecuta ./arena.sh o sh arena.sh desde la raiz del repo."
echo ""
ok "Comandos utiles:"
echo "  cd roig-arena"
echo "  ./vendor/bin/sail up -d"
echo "  ./vendor/bin/sail artisan test"
echo "  ./vendor/bin/sail npm run dev"
