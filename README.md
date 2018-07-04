# Smokescreen

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![Packagist](https://img.shields.io/packagist/v/rexlabs/smokescreen.svg)](https://packagist.org/packages/rexlabs/smokescreen)
[![Build Status](https://travis-ci.org/rexlabsio/smokescreen-php.svg?branch=master)](https://travis-ci.org/rexlabsio/smokescreen-php)
[![Code Coverage](https://scrutinizer-ci.com/g/rexlabsio/smokescreen-php/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/rexlabsio/smokescreen-php/?branch=master)
[![StyleCI](https://styleci.io/repos/116249928/shield?branch=master)](https://styleci.io/repos/116249928)

## Overview

Smokescreen is a PHP library for transforming data.  This is ideal for transforming and serializing
API responses but can be applied to many scenarios.

See also [Smokescreen Laravel Library](https://github.com/rexlabsio/smokescreen-laravel-php)

## Features

- Simple intuitive interface
- Transform any type of data (arrays, models, etc.)
- Embedding of related resources (aka. includes)
- Declarative includes
- Includes auto-wiring
- Declarative fields (including sparse field sets)
- Eager loading
- Flexible transformation and serialization
- Support for lazy loading
- No other dependencies

## Usage

To use Smokescreen, you define transformer for each resource, and
then call either the Smokescreen `item()` or `collection()` method to
transform the data.

Full documentation is available from:
https://smokescreen-docs.netlify.com/


## Requirements and dependencies

- PHP >= 7.0

So vanilla.

## Installation

Install package via composer:

`composer require rexlabs/smokescreen`

## Laravel package

We provide a Laravel wrapper package which provides some nice conveniences for working within the Laravel framework: `rexlabs/laravel-smokescreen`

See the Github repository for more information:

- [Laravel Smokescreen Package](https://packagist.org/packages/rexlabs/laravel-smokescreen)
- [Laravel Smokescreen Repository](https://github.com/rexlabsio/smokescreen-laravel-php)

## Tests

To run phpunit tests:

```bash
composer run tests
```

Feel free to add more tests.

## FAQ

### Why call it "Smokescreen"

Great question, thanks for asking. Our team racked our brain for several hours to come up with the perfect name for
this package.  In the end we went with Smokescreen because there is a transformer named smokescreen and it sounds cool.

### Why wouldn't I just use Fractal

We took all the good ideas from Fractal, and made it more extensible and safer for children.

## Contributing

Pull-requests are welcome. Please ensure code is PSR compliant.
[Smokescreen on Github](http://github.com/rexlabsio/smokescreen-php)

## Who do I talk to?

Talk to team #phoenix, or one of these people:
 
- Jodie Dunlop <jodie.dunlop@rexsoftware.com.au>
- Alex Babkov <alex.babkov@rexsoftware.com.au>

## About

- Author: [Jodie Dunlop](https://github.com/jodiedunlop)
- License: [MIT](LICENSE)
- Copyright (c) 2018 Rex Software Pty Ltd
