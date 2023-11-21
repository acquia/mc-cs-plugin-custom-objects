# Custom Objects plugin for Mautic

Allows Mautic users to define Custom Objects with custom fields. Then create multiple custom items of each type and associate them to the Contact or Company entities.

You'll be able to 
- filter Segments by values in the Custom Objects
- create Campaign conditions based on the values in the Custom Objects

## Workflow Update

Until Github Actions' CI/CD are in place, we request to developers to:
1. Merge their unreviewed and unapproved PRs to `development` branch.
2. Merge their reviewed and approved PRs to `beta` branch.

## Glossary

- `Custom Field` represents one piece of information. Example: Price, Description, Color
- `Custom Object` is set of custom fields that will allow users to create multiple instances of this object. Example: Product, Invoice.
- `Custom Item` is created when Custom Object fields are populated with specific information. Example: Mautic T-shirt, Invoice 2022-02-22-123.

## Example Usage

As an example we can create a `Custom Object` **Product**. To create one go to the right hand side admin menu. This Custom Object will have these `custom fields`:
- Name is always there by default. No need to create a custom field for it.
- Price _(Number field)_
- Description _(Textarea field)_
- Color _(Select box with options: Red, Green, Blue)_

Once we save such Custom Object then on the left hand side menu we'll be able to see new **Product** menu item and we'll be able to start creating new product **custom items**:

Product 1:
Name: Mautic T-shirt
Price: $123
Description: Great T-shirt to support your favorite project and market it whenever you walk!
Color: Blue

Product 2:
Name: Mautic Hoodie
Price: $153
Description: Great hoodie to support your favorite project and market it whenever you walk and stay worm at the same time!
Color: Red

Once some of your contacts buy a Mautic T-shirt or hoodie you can link them with the product they've bought. You can automate that with a Mautic Campaign action, API or CSV import. Once these links are established you'll be able to build Segments based on who bought what, or if they for example bought products with price greater than $100 or products of red color. Then your automated workflows can hit the right audience.

## Requirements

- Plugin supports PHP 7.1+.

## Documentation

See [Wiki](https://github.com/acquia/mc-cs-plugin-custom-objects/wiki)

## Tests

The plugin has currently test coverage of 91%. Each new PR must be covered by tests to be considered to be merged. To run the tests execute `composer test -- --filter CustomObjects` from the Mautic root dir.

## License

Copyright (C) 2022 Acquia, Inc.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
