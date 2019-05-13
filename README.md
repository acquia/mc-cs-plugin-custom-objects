# Custom Objects plugin for Mautic

Allows Mautic users to define custom object types with custom fields. Then create multiple custom objects of each type and associate them to the Contact or Company entities.

You'll be able to 
- filter segments by values in the custom objects
- create campaign conditions based on the values in the custom objects

## Requirements

- Plugin supports PHP 7.1+.

## Documentation

See https://github.com/mautic-inc/plugin-custom-objects/wiki/Custom-Objects-Documentation

## Tests

This plugin is covered with some unit tests and funcional tests that run also in CI on every push.

### Test coverage development in time

![Test coverage in time](https://docs.google.com/spreadsheets/d/e/2PACX-1vQO9XArT-eiiNb__0aiUaYbic_V4bvY5M0aYSOWWajTxMgOelnsQxSOch7QlKeVXt4DVYg2ctoyJJkd/pubchart?oid=810440106&format=image)

_Edit [this doc](https://docs.google.com/spreadsheets/d/1CAf_VfvvmOCriGz4tFtQVDl1xxP0Y7-FQKOQhcAl6kE/edit#gid=0) to update the chart._

### Useful commands

Always run following commands from the `plugins/CustomObjectsBundle` directory.

#### `$ composer test`

With this command you can run all the tests for this plugin. Functional tests included.

#### `$ composer quicktest`

With this command you can run all the tests for this plugin except functional tests which makes it fast.

#### `$ composer fixcs`

If you wan to automatically fix code styles then run this.

#### `$ composer coverage`

Will generate code coverage report into the console in the text form as well as more detailed report into `Tests/Coverage` folder where you can see which lines are or aren't covered. View the report by pointing your browser to `http://[localmautic.test]/plugins/CustomObjectsBundle/Tests/Coverage/index.html`.

#### `$ composer phpunit -- --filter CustomFieldTest`

This way you can filter which phpunit tests you want to run.



