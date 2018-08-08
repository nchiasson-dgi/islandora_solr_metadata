# Islandora Solr Metadata

## Introduction

Provides an interface to construct configurations used for displaying metadata on Islandora objects.

## Requirements

This module requires the following modules/libraries:

* [Islandora](https://github.com/discoverygarden/islandora)
* [Tuque](https://github.com/islandora/tuque)
* [Islandora Solr](https://github.com/discoverygarden/islandora_solr_search)

## Installation

Install as
[usual](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).

## Configuration

The Islandora Solr Metadata module is used by selecting it to be the default
metadata display viewer at Manage » Configuration » Metadata Display
(admin/config/islandora/metadata).

![Configuration](https://camo.githubusercontent.com/f5a44185e2c1e7f81e0f76d10a885640e2281479/687474703a2f2f692e696d6775722e636f6d2f6661356f3566582e706e67)

General configuration and metadata field configurations is available at
Manage » Configuration » Solr Index » Metadata Display
(admin/config/islandora/search/metadata).

![Configuration](https://camo.githubusercontent.com/ae1155798564091ff4623aebe039ef962f8ce9ff/687474703a2f2f692e696d6775722e636f6d2f724b65764e4c632e706e67)

### Customization

The backbone of this module is to allow users to select fields indexed in their Solr as what drives metadata displays. This allows for the creation of heterogenous displays pulled from many sources from something that is already easily available.

The Islandora Solr Metadata module uses templates to fuel the markup displayed when it's the defined viewer for an object. As such these are overwritable to alter the display of the metadata as seen fit.

For a more indepth look at the metadata display framework and an example module implementation see the [Islandora wiki](https://github.com/Islandora/islandora/wiki/Metadata-Display-Viewers).

It's to be noted that you can have a content model associated with more than one configuration at a time. Similarly, on objects with two content models, two or more configurations could respond to display the markup for the object. These cases are handled by merging the displays based around the weight. Take for example the case where you have two responding configurations where the first configuration contains the a and c fields and the second the b and d fields. The metadata display output would then be in the following order: a, b, c, d. As such, it's at the discretion of the user, through the creation of configurations, to determine how they want their metadata to be displayed.

## Documentation

This module's documentation is also available at [our wiki](https://wiki.duraspace.org/display/ISLANDORA/Islandora+Solr+Metadata).

## Troubleshooting/Issues

Having problems or solved one? Create an issue, check out the Islandora Google
groups.

* [Users](https://groups.google.com/forum/?hl=en&fromgroups#!forum/islandora)
* [Devs](https://groups.google.com/forum/?hl=en&fromgroups#!forum/islandora-dev)

or contact [discoverygarden](http://support.discoverygarden.ca).

## Maintainers/Sponsors

Current maintainers:

* [discoverygarden](http://www.discoverygarden.ca)

## Development

If you would like to contribute to this module, please check out the helpful
[Documentation](https://github.com/Islandora/islandora/wiki#wiki-documentation-for-developers),
[Developers](http://islandora.ca/developers) section on Islandora.ca and create
an issue, pull request and or contact
[discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
