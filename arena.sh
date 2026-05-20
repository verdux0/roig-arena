#!/bin/bash

# Si se ejecuta con "sh arena.sh", re-ejecutar en bash para mantener compatibilidad
if [ -z "${BASH_VERSION:-}" ]; then
    exec bash "$0" "$@"
fi

# Configuración de colores para mejor legibilidad (Verbose mode)
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}>>> Iniciando proceso de reinicio del entorno Arena...${NC}\n"

# 1. Acceder al directorio del proyecto
# Usamos -e para que el script se detenga si hay errores
cd roig-arena || { echo "Error: No se pudo encontrar la carpeta 'roig-arena'"; exit 1; }

# 1.1 Verificar permisos de ejecucion de Sail
# En algunos entornos estos scripts pierden el bit +x y provocan "Permission denied".
if [ ! -x ./vendor/laravel/sail/bin/sail ]; then
    echo -e "${YELLOW}[0/5] Corrigiendo permisos de Sail...${NC}"
    chmod +x ./vendor/laravel/sail/bin/sail ./vendor/bin/sail 2>/dev/null || {
        echo "Error: no se pudieron ajustar permisos de Sail"; exit 1
    }
    echo -e "${GREEN}✔ Permisos de Sail corregidos.${NC}\n"
fi

# 2. Detener y limpiar contenedores (down)
# El flag -v elimina los volúmenes (limpia la base de datos por completo)
echo -e "${YELLOW}[1/5] Deteniendo contenedores y limpiando volúmenes...${NC}"
./vendor/bin/sail down -v
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✔ Contenedores detenidos y eliminados correctamente.${NC}\n"
else
    echo "Error al detener contenedores"; exit 1
fi

# 3. Iniciar contenedores en segundo plano (up -d)
echo -e "${YELLOW}[2/5] Levantando servicios en modo detach...${NC}"
./vendor/bin/sail up -d
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✔ Servicios iniciados.${NC}\n"
else
    echo "Error al iniciar Sail"; exit 1
fi

# 4. Esperar a que MySQL esté listo
# Es crucial esperar antes de migrar, ya que el contenedor sube pero el motor DB tarda unos segundos
echo -e "${YELLOW}[3/5] Esperando a que MySQL esté completamente listo...${NC}"
echo -n "Esperando"
MYSQL_READY=0
for i in {1..60}; do
    if ./vendor/bin/sail exec -T mysql mysqladmin ping -h "127.0.0.1" -u "${DB_USERNAME:-sail}" -p"${DB_PASSWORD:-password}" --silent >/dev/null 2>&1; then
        MYSQL_READY=1
        break
    fi
    echo -n "."
    sleep 1
done
echo ""

if [ $MYSQL_READY -ne 1 ]; then
    echo "Error: MySQL no estuvo listo a tiempo"; exit 1
fi

./vendor/bin/sail ps
echo -e "${GREEN}✔ Estado de los contenedores verificado.${NC}\n"

# 5. Asegurar APP_KEY antes de migraciones/tests
# Si APP_KEY esta vacia, Laravel lanza MissingAppKeyException al ejecutar pruebas.
if ! grep -q '^APP_KEY=base64:' .env; then
    echo -e "${YELLOW}[4/5] APP_KEY vacia. Generando clave de aplicacion...${NC}"
    ./vendor/bin/sail artisan key:generate --force
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✔ APP_KEY generada correctamente.${NC}\n"
    else
        echo "Error al generar APP_KEY"; exit 1
    fi
fi

# 6. Ejecutar migraciones y seeders
# --fresh borra tablas existentes y --seed puebla los datos (Sectores, Asientos, etc.)
echo -e "${YELLOW}[4/5] Ejecutando migraciones y poblando base de datos...${NC}"
./vendor/bin/sail artisan migrate:fresh --seed
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✔ Base de datos lista y poblada.${NC}\n"
else
    echo "Error en las migraciones"; exit 1
fi

# 7. Ejecutar tests de PHPUnit
# Verificamos que todo el sistema (Auth, Reservas, Compras) funcione tras el reset
echo -e "${YELLOW}[5/5] Ejecutando batería de tests...${NC}"
./vendor/bin/sail artisan test
if [ $? -eq 0 ]; then
    echo -e "\n${GREEN}🚀 TODO CORRECTO: Entorno reiniciado, poblado y testeado con éxito.${NC}"
else
    echo -e "\n${YELLOW}⚠ Los servicios están arriba pero algunos tests han fallado.${NC}"
    exit 1
fi
