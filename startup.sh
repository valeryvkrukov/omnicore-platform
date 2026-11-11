#!/bin/bash
set -e

echo "=== 🚀 Launch of the OmniCore Platform infrastructure ==="

# 1. Resetting stuck Docker interfaces and clearing host ports
echo "🧹 Cleaning up old sessions..."
docker compose down --remove-orphans || true

for port in 5415 8000; do
    PID=$(lsof -t -i:$port || true)
    if [ ! -z "$PID" ]; then
        echo "⚠️  Port $port is in use by system process $PID. Releasing it..."
        kill -9 "$PID" || true
    fi
done

# 2. Lifting containers
echo "📦 Building and running containers..."
docker compose up -d --build

# 3. Await for PostgreSQL (User: omnicore_user, DB: omnicore_dev, Internal Port: 5432)
echo "⏳ Checking PostgreSQL readiness within a Docker network..."
until docker compose exec -T postgres pg_isready -U omnicore_user -d omnicore_dev -p 5432 > /dev/null 2>&1; do
    sleep 1
    echo -n "."
done
echo -e "\n✅ PostgreSQL is fully ready for operation!"

# 4. Warming up the Symfony backend directly in the PHP container
echo "⚙️  Clearing the Symfony cache and checking migrations..."
docker compose exec -T php php bin/console cache:clear --env=dev
docker compose exec -T php php bin/console doctrine:migrations:migrate OmniCore\\Migrations\\Version20261102111033 --no-interaction --allow-no-migration || true
docker compose exec -T php php bin/console doctrine:migrations:migrate OmniCore\\Migrations\\Version20261108130630 --no-interaction --allow-no-migration || true

# 5. Fill the test data
echo "⚙️  Fill the test data by using omnicore:seed-large-customers command..."
docker compose exec -T php php bin/console omnicore:seed-data
docker compose exec -T php php bin/console omnicore:seed-large-customers

echo "=== 🎉 Infrastructure has been successfully deployed! ==="
echo "🔗 The backend is available at: http://127.0.0.1:8000"
