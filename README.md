# Custom Objects plugin for Mautic

Allows Mautic users to define custom object types with custom fields. Then create multiple custom objects of each type and associate them to the Contact or Company entities.

You'll be able to 
- filter segments by values in the custom objects
- create campaign conditions based on the values in the custom objects

## Requirements

- Plugin supports PHP 7.1+.

## Unit tests

With this command you can run the plugin unit tests:

`$ bin/phpunit --bootstrap vendor/autoload.php --configuration app/phpunit.xml.dist --filter CustomObjectsBundle`

*Run it from Mautic root folder*
