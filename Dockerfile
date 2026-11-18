# Imagem da aplicação: PHP 8.3 com o servidor embutido e a extensão do MySQL.
FROM php:8.3-cli
RUN docker-php-ext-install pdo_mysql
ENV TZ=America/Sao_Paulo
WORKDIR /app
COPY . /app
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
