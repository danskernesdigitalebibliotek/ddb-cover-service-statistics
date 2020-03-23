# DDB Cover Service Faktor Export

Data extraction API for Faktor.

## API

The API supplies an endpoint to get entries. It is possible to filter by whether or not an entry is already "extracted" and by date of registration.

## Commands

### Extract entries from elasticsearch that are newer than latest extraction date.

```
bin/console app:extract:latest
```

### Remove already extracted entries that are more than 3 days old

```
app:cleanup:entries 3
```

### Extract logs from 15-18 March 2020 to a csv.

```
bin/console app:extract:days filename.csv --from=15-3-2020 --to=18-3-2020 --types=hit
```

Run `bin/console app:extract:days --help` for options.

## Development

### Load fixtures into elasticsearch

```
bin/console app:fixtures:load
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
