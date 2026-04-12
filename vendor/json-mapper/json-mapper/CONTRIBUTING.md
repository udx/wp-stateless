# Contributing to JsonMapper

:+1::tada: First off, thanks for taking the time to contribute! :tada::+1:

Since JsonMapper currently only is a small project (though it has great ambitions) we welcome any contribution
that helps us move forward. The project could use help with any of the following subjects:

* *Middleware*: Any new ideas for middleware that would be helpful for the project users are welcomed.
* *Performance*: Mapping should be fast pointing out or resolving any bottlenecks in performance are very helpful.
* *Documentation*: The project should have an up-to-date version of documentation. Any help here is appreciated.

## Ideal pull request
To avoid going back and forth the project `composer.json` was setup in such a way that it allows you to run 
the same checks we do. These checks are:
```bash
composer unit-tests        # For running the unit tests
composer integration-tests # For running the integration tests
composer phpcbf            # For applying the correct code style (PSR-12) to the sources
composer phpcs             # For scanning the sources for the correct style (PSR-12) being used
composer phpstan           # For analysing the sources for potential bugs
```
