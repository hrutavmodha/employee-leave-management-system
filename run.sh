#!/bin/bash

# Function to handle cleanup on exit
cleanup() {
    echo ""
    echo "Stopping servers..."
    kill $SERVE_PID
    kill $VITE_PID
    exit
}

# Trap SIGINT (Ctrl+C) and call cleanup
trap cleanup SIGINT

echo "Starting Laravel Development Server..."
php artisan serve --host=0.0.0.0 &
SERVE_PID=$!

echo "Starting Vite Development Server..."
npm run dev -- --host &
VITE_PID=$!

echo "------------------------------------------------"
echo "ELMS is running at: http://10.50.15.110:8000"
echo "Press Ctrl+C to stop both servers."
echo "------------------------------------------------"

# Wait for background processes
wait
