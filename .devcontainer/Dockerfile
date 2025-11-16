# Base image: PHP + Node + common tooling
FROM mcr.microsoft.com/devcontainers/php-node:1-8.3-bullseye

# Install PHP extensions needed by Laravel
RUN docker-php-ext-install pdo_mysql bcmath

# (Optional but nice) Set working dir
WORKDIR /workspace

COPY .devcontainer/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
