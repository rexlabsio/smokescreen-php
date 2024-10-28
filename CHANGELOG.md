# Changelog

## 2.4.0

### New Features

* Allow method directive for includes to be a closure instead of a string that refers to a method on the class

## 2.1.0

### General Changes

* Official support for PHP 7.4-8.3 with pipeline to test

### Fixes

* Fixes an issue with parsing relation: marker where `relation:users,other` would be parsed as `['user', 'other]` with
  the `s` character being dropped off the first word

### Breaking Changes

* Drop support for anything outside php 7.4-8.3
