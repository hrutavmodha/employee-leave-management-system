#!/bin/bash

# Function to handle cleanup on exit
cleanup() {
    echo ""
    echo "Stopping servers..."
    kill $SERVE_PID
    kill $VITE_PID
    kill $REDIS_PID
    kill $QUEUE_PID
    exit
}

# Trap SIGINT (Ctrl+C) and call cleanup
trap cleanup SIGINT

IP_ADDR=$(hostname -I | awk '{print $1}')
if [ -z "$IP_ADDR" ]; then
    IP_ADDR="127.0.0.1"
fi
URL="http://$IP_ADDR:8000"

echo "Starting Redis Server..."
redis-server &
REDIS_PID=$!

echo "Starting Laravel Development Server (Backend)..."
php artisan serve --host=0.0.0.0 &
SERVE_PID=$!

echo "Starting Vite Development Server (Frontend)..."
npm run dev -- --host &
VITE_PID=$!

echo "Starting Laravel Queue Listener..."
php artisan queue:listen --tries=1 --timeout=0 &
QUEUE_PID=$!

# Small delay to let servers start
sleep 2

echo "------------------------------------------------"
echo "ELMS is running at: $URL"
echo "Opening browser at $URL"
echo "Press Ctrl+C to stop both servers."
echo "------------------------------------------------"

# Open browser (Linux command)
xdg-open $URL || echo "Please open $URL in your browser manually."

# Wait for background processes
wait
