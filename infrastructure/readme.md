# Kubernetes configuration

See the cover service repository about the basic cluster setup: https://github.com/danskernesdigitalebibliotek/ddb-cover-service/tree/develop/infrastructure/k8s

## Setup this part of the application.
Change into cover service namespace.
```bash
kubectx ddb-cover-service
```

Deploy MongoDB database and remember to change the password variable in the command.
```bash
helm install faktor-mongo stable/mongodb --set mongodbUsername=faktor,mongodbPassword=faktor-1234,mongodbDatabase=faktor
```

Deploy the application.
```bash
helm install cover-service-faktor infrastructure/cover-service-faktor/ --set ingress.enableTLS=true --set ingress.domain=faktor-cover.dandigbib.org 
```

