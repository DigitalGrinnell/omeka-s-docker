FROM php:apache

# Omeka-S web publishing platform for digital heritage collections (https://omeka.org/s/)
# Initial maintainer: Oldrich Vykydal (o1da) - Klokan Technologies GmbH
# Forked from MAINTAINER Eric Dodemont <eric.dodemont@skynet.be>
MAINTAINER Mark McFate <mcfatem@grinnell.edu>

ARG omeka_s_repo=https://github.com/DigitalGrinnell/omeka-s
ARG omeka_s_branch=master
ARG centerrow_theme_repo=https://github.com/DigitalGrinnell/centerrow
ARG centerrow_theme_branch=master

RUN a2enmod rewrite

ENV DEBIAN_FRONTEND noninteractive
RUN apt-get -qq update && apt-get -qq -y upgrade
RUN apt-get -qq update && apt-get -qq -y --no-install-recommends install \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libpng-dev \
    libjpeg-dev \
    libmemcached-dev \
    zlib1g-dev \
    imagemagick \
    libmagickwand-dev \
    libcurl4-gnutls-dev \
    git

# Install the PHP extensions we need
RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/
RUN docker-php-ext-install -j$(nproc) iconv pdo pdo_mysql mysqli gd
RUN pecl install mcrypt-1.0.2 && docker-php-ext-enable mcrypt && pecl install imagick && docker-php-ext-enable imagick \
  && pecl install solr && docker-php-ext-enable solr

RUN git config --global user.email "digital@grinnell.edu" && \
  git config --global user.name "Mark McFate"

### Add the Omeka-S PHP code
#COPY ./omeka-s-1.4.0.zip /var/www/
#RUN unzip -q /var/www/omeka-s-1.4.0.zip -d /var/www/ \
#&&  rm /var/www/omeka-s-1.4.0.zip \
#&&  rm -rf /var/www/html/ \
#&&  mv /var/www/omeka-s/ /var/www/html/

## Add the Omeka-S PHP code
COPY ./omeka-s-master.zip /var/www/
RUN unzip -q /var/www/omeka-s-master.zip -d /var/www/ \
  &&  rm /var/www/omeka-s-master.zip \
  &&  rm -rf /var/www/html/ \
  &&  mv /var/www/omeka-s-master/ /var/www/html/

## Pull the Omeka-S PHP code via git... but leave it in /tmp/omeka-s
#RUN git clone ${omeka_s_repo} /tmp/omeka-s
#WORKDIR /tmp/omeka-s
#RUN git fetch origin && git checkout ${omeka_s_branch} && git merge origin/${omeka_s_branch}
#RUN git merge -X theirs ${omeka_s_branch}
#RUN rm -rf /var/www/html/
#RUN mv -f /tmp/omeka-s/ /var/www/html/
#RUN rm -rf /tmp/omeka-s

COPY ./imagemagick-policy.xml /etc/ImageMagick/policy.xml
COPY ./.htaccess /var/www/html/.htaccess

# Add some Omeka modules
COPY ./omeka-s-modules-v4.tar.gz /var/www/html/
RUN rm -rf /var/www/html/modules/ \
&&  tar -xzf /var/www/html/omeka-s-modules-v4.tar.gz -C /var/www/html/ \
&&  rm -f /var/www/html/omeka-s-modules-v4.tar.gz

## Add some themes
#COPY ./centerrow-v1.4.0.zip ./cozy-v1.3.1.zip ./thedaily-v1.4.0.zip /var/www/html/themes/
#RUN unzip -q /var/www/html/themes/centerrow-v1.4.0.zip -d /var/www/html/themes/ \
#&&  unzip -q /var/www/html/themes/cozy-v1.3.1.zip -d /var/www/html/themes/ \
#&&  unzip -q /var/www/html/themes/thedaily-v1.4.0.zip -d /var/www/html/themes/ \
#&&  rm -f /var/www/html/themes/centerrow-v1.4.0.zip /var/www/html/themes/cozy-v1.3.1.zip /var/www/html/themes/thedaily-v1.4.0.zip

# Add some themes. MAM: Replacing `centerrow-v1.4.0` from above with a different approach
RUN git clone ${centerrow_theme_repo} /var/www/html/themes/centerrow
WORKDIR /var/www/html/themes/centerrow
RUN git pull origin ${centerrow_theme_branch} && git checkout ${centerrow_theme_branch}
COPY ./cozy-v1.3.1.zip ./thedaily-v1.4.0.zip /var/www/html/themes/
RUN unzip -q /var/www/html/themes/cozy-v1.3.1.zip -d /var/www/html/themes/ \
  &&  unzip -q /var/www/html/themes/thedaily-v1.4.0.zip -d /var/www/html/themes/ \
  &&  rm -f /var/www/html/themes/cozy-v1.3.1.zip /var/www/html/themes/thedaily-v1.4.0.zip

# Create one volume for files and config
RUN mkdir -p /var/www/html/volume/config/ && mkdir -p /var/www/html/volume/files/
COPY ./database.ini /var/www/html/volume/config/
RUN rm -f /var/www/html/config/database.ini \
&& ln -s /var/www/html/volume/config/database.ini /var/www/html/config/database.ini \
&& rm -Rf /var/www/html/files/ \
&& ln -s /var/www/html/volume/files/ /var/www/html/files \
&& chown -R www-data:www-data /var/www/html/ \
&& chmod 600 /var/www/html/volume/config/database.ini \
&& chmod 600 /var/www/html/.htaccess

VOLUME /var/www/html/volume/

CMD ["apache2-foreground"]
