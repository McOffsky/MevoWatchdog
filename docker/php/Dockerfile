### DOCKERFILE FOR DEVELOPMENT IMAGE
### for prod dockerfile look in project root direcotry
FROM ezsystems/php:7.2-v1-dev

WORKDIR /var/www

RUN echo "deb [check-valid-until=no] http://archive.debian.org/debian jessie-backports main" > /etc/apt/sources.list.d/jessie-backports.list
RUN sed -i '/deb http:\/\/deb.debian.org\/debian jessie-updates main/d' /etc/apt/sources.list
RUN apt-get -o Acquire::Check-Valid-Until=false update

RUN apt-get install -y \
        openssh-client \
        --no-install-recommends && \
    apt-get clean -y

RUN mkdir -p /root/.ssh && chmod 700 /root/.ssh
RUN ssh-keyscan -t rsa github.com  >> /root/.ssh/known_hosts
RUN ssh-keyscan -t rsa bitbucket.org >> /root/.ssh/known_hosts

RUN rm /usr/local/etc/php/conf.d/xdebug.ini

RUN mkdir -p /var/logs/php && chmod 766 /var/logs/php

### Setup entrypoint script
COPY entrypoint.sh /entrypoint.sh

#Install Cron
RUN apt-get -y install cron
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]

ADD crontab /etc/cron.d/mevo-cron
RUN chmod 0644 /etc/cron.d/mevo-cron
RUN touch /var/log/cron.log
RUN crontab /etc/cron.d/mevo-cron

CMD cron && php-fpm

EXPOSE 9000
