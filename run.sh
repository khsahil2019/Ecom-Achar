#!/usr/bin/env bash
# Quick Start Script for Achar Heritage Store

HOST="127.0.0.1"
PORT="8000"

echo "=========================================================="
echo "🌶️  ACHAR HERITAGE - COMPLETE ONLINE STORE & ADMIN PANEL"
echo "=========================================================="
echo "Starting local server at http://${HOST}:${PORT}"
echo ""
echo "👉 Customer Store:  http://${HOST}:${PORT}/index.php"
echo "👉 Admin Console:   http://${HOST}:${PORT}/admin/login.php"
echo ""
echo "🔑 Demo Credentials:"
echo "   - Admin:    admin@achar.com / Admin@123"
echo "   - Customer: rahul@example.com / Customer@123"
echo "=========================================================="
echo "Press Ctrl+C anytime to stop."
echo ""

# Attempt to open browser automatically if on macOS
if [[ "$OSTYPE" == "darwin"* ]]; then
    (sleep 1 && open "http://${HOST}:${PORT}") &
fi

php -S "${HOST}:${PORT}"
