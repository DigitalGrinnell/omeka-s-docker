# Omeka-S as https://omeka.localdomain

This document explains how to spin up Omeka-S v2.0.2 as a local stack on your OSX workstation.  

## Commands

```
cd ~
mkdir -p Documents/GitHub
git clone https://github.com/McFateM/dockerized-server.git
git clone https://github.com/DigitalGrinnell/omeka-s-docker.git
cd dockerized-server
git checkout localdomain
docker network create web
docker-compose up -d
cd ../omeka-s-docker
git checkout omeka-s-v2
docker-compose up -d
```

## /etc/hosts

Make sure the host's `/etc/hosts` file has only one ACTIVE line that reads:

```
127.0.0.1	localhost omeka.localdomain traefik.localdomain pma.localdomain solr.localdomain
```

## The Sites

Complete the Omeka-S installation and configuration at `https://omeka.localdomain`.  
