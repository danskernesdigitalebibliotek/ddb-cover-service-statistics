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
kubectl apply -f app-deployment.yaml -f app-ingress.yaml -f app-secret.yaml
```

# Secret template
Template to set secrets used be the application.

```yaml
---
apiVersion: v1
kind: Secret
metadata:
  namespace: cover-service
  name: cover-service-faktor-export-secret
type: Opaque
stringData:
  APP_SECRET: 'x'
  APP_MONGODB_USER: 'faktor'
  APP_MONGODB_PASSWORD: 'y'
```
