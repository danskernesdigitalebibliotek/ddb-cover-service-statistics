# Kubernetes configuration

See the cover service repository about the basic cluster setup: https://github.com/danskernesdigitalebibliotek/ddb-cover-service/tree/develop/infrastructure/

## Setup this part of the application.
Change into cover service namespace.
```bash
kubectx ddb-cover-service
```

```bash
helm repo add bitnami https://charts.bitnami.com/bitnami
```

Deploy MongoDB database and remember to change the password variable in the command.
```bash
kubectl apply -f mongodb-storage.yaml
helm install faktor-mongo bitnami/mongodb --namespace=cover-service --set mongodbUsername=faktor,mongodbPassword=faktor-1234,mongodbDatabase=faktor,metrics.enabled=true,persistence.existingClaim=mongodb-managed-disk
```

Deploy the application.
```bash
helm install cover-service-faktor infrastructure/cover-service-faktor/ --namespace cover-service --set ingress.enableTLS=true --set ingress.domain=faktor-cover.dandigbib.org
```
