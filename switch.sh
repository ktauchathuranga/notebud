#!/bin/bash

if [ "$1" = "local" ]; then
    cp .env.local .env
    echo "Switched to local Docker MongoDB"
    docker-compose up -d
elif [ "$1" = "cloud" ]; then
    cp .env.cloud .env  
    echo "Switched to MongoDB Atlas"
    docker-compose up -d web
else
    echo "Usage: ./switch.sh [local|cloud]"
fi
