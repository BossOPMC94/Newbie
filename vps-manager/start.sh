#!/bin/bash

# Set root password from environment variable
if [ -n "$ROOT_PASSWORD" ]; then
    echo "root:$ROOT_PASSWORD" | chpasswd
fi

# Start SSH service
service ssh start

# Keep the container running
tail -f /dev/null