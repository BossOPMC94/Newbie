# Use Ubuntu 22.04 as the base image
FROM ubuntu:22.04

# Update package lists and install necessary packages
RUN apt-get update && \
    apt-get install -y \
    tmate \
    openssh-server \
    openssh-client \
    systemd \
    systemd-sysv \
    dbus \
    dbus-user-session \
    curl \
    ufw \
    net-tools \
    iproute2 \
    hostname && \
    rm -rf /var/lib/apt/lists/*

# Allow root login via SSH
RUN sed -i 's/^#\?\s*PermitRootLogin\s\+.*/PermitRootLogin yes/' /etc/ssh/sshd_config

# Set root password to "root"
RUN echo 'root:root' | chpasswd

# Allow UFW to work without interactive prompts
RUN printf '#!/bin/sh\nexit 0' > /usr/sbin/policy-rc.d

# Configure UFW to allow ports 80 (HTTP) and 443 (HTTPS)
RUN ufw allow 80 && \
    ufw allow 443

# Start systemd-logind on boot
RUN echo "systemctl start systemd-logind" >> /etc/profile

# Set the entrypoint to systemd
ENTRYPOINT ["/sbin/init"]

# Default command (can be overridden)
CMD ["bash"]