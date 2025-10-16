FROM --platform=$TARGETPLATFORM php:8.2-cli-alpine
RUN apk add --no-cache php82-sqlite3 sqlite-libs \
 && mkdir /app /app/uploads \
 && chmod 777 /app/uploads
WORKDIR /app
COPY . .
EXPOSE 80
CMD ["php", "-S", "0.0.0.0:80", "-t", "/app"]