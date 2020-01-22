# DDB Cover Service Faktor Export

Data extraction API for Faktor.

## API

The API supplies an endpoint to get entries. It is possible to filter by whether or not an entry is already "extracted" and by date of registration.

## Commands

### Extract entries from elasticsearch that are newer than latest extraction date.

```
bin/console app:extract-statistics
```

### Remove already extracted entries that are more than 3 days old

```
app:cleanup-entries 3
```

## Development

### Create fake content in elasticsearch

```
bin/console app:fake-content
```

## Tests

### Run unit and functional tests

```
composer phpunit
```

### Run tests and generate coverage report

```
composer phpunit-coverage
```

The report is found in the `coverage/report.txt` file.
