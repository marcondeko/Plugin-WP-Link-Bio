FROM wordpress:latest

# Instala qualquer dependência adicional, se necessário
RUN docker-php-ext-install mysqli
