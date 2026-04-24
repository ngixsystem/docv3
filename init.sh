#!/bin/sh
set -eu

echo "Starting DocV3 containers..."
docker compose up -d --build

echo "Waiting for the application bootstrap to finish..."
sleep 5

docker compose ps

echo
echo "DocV3 should be available at http://localhost:8080"
echo "Use 'docker compose logs app' if the first start is still in progress."
