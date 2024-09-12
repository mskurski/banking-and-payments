#!/bin/bash
set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0;0m'

echo "${GREEN}Starting init script...${NC}"

echo "${GREEN}Syncing docker-compose ${YELLOW}.env${GREEN} file...${NC}"
rsync -ua .env.dist .env

echo "${GREEN}Setting up user:group ids...${NC}"
USER_ID="$(id -u)"
GROUP_ID="$(id -g)"
< .env sed "s/APP_UID_VALUE/$USER_ID/" > .env.tmp && mv .env.tmp .env
< .env sed "s/APP_GID_VALUE/$GROUP_ID/" > .env.tmp && mv .env.tmp .env

echo "${GREEN}Building and starting containers...${NC}"
docker compose --env-file .env up -d

echo "${GREEN}Installing composer dependencies...${NC}"
docker compose --env-file .env exec php composer install
