#!/bin/sh

APP_VERSION=develop
VERSION=latest

docker build --no-cache --build-arg APP_VERSION=${APP_VERSION} --tag=danskernesdigitalebibliotek/cover-service-faktor-export:${VERSION} --file="cover-service-faktor-export/Dockerfile" cover-service-faktor-export
docker build --no-cache --build-arg VERSION=${VERSION} --tag=danskernesdigitalebibliotek/cover-service-faktor-export-nginx:${VERSION} --file="nginx/Dockerfile" nginx

docker push danskernesdigitalebibliotek/cover-service-faktor-export:${VERSION}
docker push danskernesdigitalebibliotek/cover-service-faktor-export-nginx:${VERSION}
