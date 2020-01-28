#!/bin/sh

(cd ../../ && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service-faktor-export/cover-service-faktor-export --file="infrastructure/docker/cover-service-faktor-export/Dockerfile" .)
(cd ../../ && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service-faktor-export/cover-service-faktor-export-nginx --file="infrastructure/docker/nginx/Dockerfile" . )

docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service-faktor-export/cover-service-faktor-export:latest
docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service-faktor-export/cover-service-faktor-export-nginx:latest
