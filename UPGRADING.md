# Upgrading Guide

## Upgrade to 2.0

Support for `autoload.exclude-from-files` has been removed. This non-standard usage of the `autoloads` property should never have been supported to begin with.

Instead of using `autoload`:

```json
{
    "autoload": {
        "exclude-from-files": [
            "illuminate/support/helpers.php"
        ]
    }
}
```

You now have to use `extra`:

```json
{
    "extra": {
        "exclude-from-files": [
            "illuminate/support/helpers.php"
        ]
    }
}
```
