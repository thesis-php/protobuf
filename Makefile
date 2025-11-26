UID := $(shell id -u)
GID := $(shell id -g)

build:
	docker compose build --build-arg UID=$(UID) --build-arg GID=$(GID)

up: build
	docker compose up -d

down:
	docker compose down --remove-orphans

php:
	docker compose exec php bash
