# Achar Heritage - Automation Makefile
PORT ?= 8000
HOST ?= 127.0.0.1

.PHONY: run start open status help

## Start the local server
run:
	@echo "🌶️  Starting Achar Heritage E-Commerce on http://$(HOST):$(PORT)..."
	@echo "👉 Customer Website: http://$(HOST):$(PORT)"
	@echo "👉 Admin Console:   http://$(HOST):$(PORT)/admin/login.php"
	@echo "Press Ctrl+C to stop the server."
	php -S $(HOST):$(PORT)

## Open website in macOS browser
open:
	@open http://$(HOST):$(PORT)
	@open http://$(HOST):$(PORT)/admin/login.php

## Show help
help:
	@echo "Available commands:"
	@echo "  make run   - Start the local PHP development server on http://$(HOST):$(PORT)"
	@echo "  make open  - Open the website and admin console in your browser"
