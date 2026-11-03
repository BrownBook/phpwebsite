FROM amazonlinux:2023
ENV container docker
USER root

RUN dnf install -y nginx php8.2 php8.2-fpm php8.2-devel php8.2-dba php8.2-mbstring php8.2-pgsql php8.2-pdo php8.2-gd php-pear postgresql15 postgresql15-contrib


RUN dnf -y install gcc make vim zip unzip;
RUN dnf -y update
#RUN pecl install Xdebug

#ADD docker_conf/xdebug.ini /etc/php.d/xdebug.ini

RUN mkdir /etc/nginx/conf.d/common

ADD docker_conf/error_page.conf /etc/nginx/conf.d/common/error_page.conf
ADD docker_conf/php.conf /etc/nginx/conf.d/common/php.conf
ADD docker_conf/secure_block.conf /etc/nginx/conf.d/common/secure_block.conf
ADD docker_conf/nginx.conf /etc/nginx/nginx.conf

ADD docker_web/docker-web-entry.sh /docker-entry.sh
EXPOSE 80
CMD ["-D","FOREGROUND"]
ENTRYPOINT ["/docker-entry.sh"]
