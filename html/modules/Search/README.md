# Search module for Omeka S

This module add search capabilities to the public interface of Omeka S.

This module alone is basically useless, but it provides a common interface for other modules to extend it.

It can be extended in two ways:

- Forms that will build the search form and construct the query
- Adapters that will do the real work

A very basic form is provided as an example, but no adapters.
However the [Solr module](https://github.com/biblibre/omeka-s-module-Solr) provides a search adapter for Solr.

## Requirements

- [jQueryUI module](https://github.com/biblibre/omeka-s-module-jQueryUI) for admin interface

## Build status

[![Build Status](https://travis-ci.org/biblibre/omeka-s-module-Search.svg?branch=master)](https://travis-ci.org/biblibre/omeka-s-module-Search)
