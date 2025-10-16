# ---- ultra-light PHP runtime ----
FROM php:8.2-cli-alpine
RUN apk add --no-cache sqlite-libs sqlite php-sqlite3 php-pdo_sqlite
WORKDIR /app
COPY . /app
RUN mkdir -p /app/uploads && chmod -R 777 /app/uploads
EXPOSE 80
CMD ["php", "-S", "0.0.0.0:80", "-t", "/app"]