
PORT=${PORT:-8000}

echo "Starting Symfony server on port $PORT..."
php -S 0.0.0.0:$PORT -t public
