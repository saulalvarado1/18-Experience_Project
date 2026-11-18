FROM php:8.2-apache

# Actualizar repositorios e instalar dependencias del sistema
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    zip \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Configurar e instalar extensiones de PHP necesarias para Laravel, PostgreSQL y DomPDF
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    gd \
    zip \
    bcmath \
    intl \
    opcache

# Habilitar mod_rewrite en Apache
RUN a2enmod rewrite

# Copiar configuracion de VirtualHost
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar script de entrada
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

# Exponer puerto 80 del contenedor
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
