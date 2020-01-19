# DDB Cover Service Faktor Export

Data extraction API for Faktor.

## API

The API supplies an endpoint to get entries. It is possible to filter by whether or not an entry is already "extracted" and by date of registration.

### OpenAPI 3

See the specification in `openapi/openapi.yaml` in the root of the installation (public folder).

## Commands

### Extract entries from elasticsearch that are newer than latest extration.

```
bin/console app:extract-statistics
```

### Remove already extracted entries

```
app:cleanup-entries
```
