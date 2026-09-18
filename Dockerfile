FROM bitnami/laravel:10
COPY . /app
WORKDIR /app
RUN composer install
CMD [ "php", "artisan", "serve", "--host", "0.0.0.0", "--port", "8000" ]
