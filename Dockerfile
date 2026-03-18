FROM ubuntu:latest

RUN apt-get update -y  \
    && apt-get -y install software-properties-common \
    && add-apt-repository ppa:ondrej/php \
    && apt-get -y install nginx \
    && apt-get -y install php8.5 \
    && apt-get -y install php8.5-common \
    && apt-get -y install php8.5-cli \
    && apt-get -y install php8.5-fpm \
    && apt-get -y install php8.5-mysql \
    && apt-get -y install php8.5-xml \
    && apt-get -y install php8.5-curl \
    && apt-get -y install php8.5-mbstring \
    && apt-get -y install php8.5-zip \
    && apt-get -y install php8.5-gd \
    && apt-get -y install php8.5-gmp

COPY ../ /var/www/html/
COPY ../nginx.conf /etc/nginx/nginx.conf

CMD ["sh", "-c", "service php8.5-fpm start && exec nginx -g 'daemon off;'"]

EXPOSE 80