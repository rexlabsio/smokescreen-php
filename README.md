# Smokescreen

## Overview

This package is a vanilla PHP (with no dependencies) library  for transforming and serializing data; typically RESTful API responses.

## Usage

```php
<?php
require 'vendor/autoload.php'
use RexSoftware\Smokescreen\Smokescreen;

class MyController
{
    protected $smokescreen;
    
    public function __construct(Smokescreen $smokescreen) {
        $this->smokescreen = $smokescreen;
    }
    
    public function index()
    {
        return $this->smokescreen
            ->collection(Post::all(), new PostTransformer)
            ->toArray();
    }
    
     public function show(Post $post)
     {
        return $this->smokescreen
            ->item($post, new PostTransformer)
            ->toArray();
     }
}
```

## Requirements and dependencies

- PHP >= 7.0

So vanilla.

## Installation

This package is currently hosted on RexSoftware's private packagist repository. First ensure you have configured your 
`composer.json` to use this repository.

Install package via composer:

`composer require rexsoftware/smokescreen`

## Laravel package

We provide a Laravel wrapper package which provides some nice conveniences for working
within the Laravel framework: `rexsoftware/laravel-smokescreen` which you should install instead of this one.

See the Github repository for more information:

[Smokescreen on Rexlabsio Github](https://github.com/rexlabsio/smokescreen)

## API

### item()

Set the item resource to be transformed:

`$smokescreen->item(mixed $item, mixed $transformer = null);`

```php
<?php
// Via a transformer
$smokescreen->item($post, new PostTransformer);

// Via a callable
$smokescreen->item($post, function($post) {
    return [
        'id' => $post['id'],
        'subject' => $post['subject'],
        'body' => $post['body'],
        'author' => $post['author'],
    ];
});
```

- Can be transformed via a `TransformerInterface` object (more flexible) or via a callable
- If no transformer is supplied, the original resource will be returned without any transformation
- The given resource can be mixed, so you can supply a model from your framework or something as simple as an array

### collection()

Set collection resource to be transformed:

`$smokescreen->collection(mixed $collection, mixed $transformer = null);`

```php
<?php
// Via a transformer
$smokescreen->collection($posts, new PostTransformer);

// Via a callable
$smokescreen->collection($posts, function($post) {
    return [
        'id' => $post['id'],
        'subject' => $post['subject'],
        'body' => $post['body'],
        'author' => $post['author'],
    ];
});
```

- Use to transform a collection of items (like an array or something that can be iterated)
- Provide a callable or a `TransformerInterface` object

### setTransformer()

Set the transformer to use on the previously set resource:

`$smokescreen->setTransformer(TransformerInterface|callable $transformer);`

```php
<?php
$smokescreen->item($post)
    ->setTransformer(new SomeOtherTransformer);
```

- It's an alternative to passing the transformer directly to resource methods.
- Use when transformer not already passed to one of the `collection()` or `item()` methods
or to replace the transformer on the resource.
- Must have a resource set first, since this is applied directly to the resource.

### getTransformer()

Get the current transformer:

```php
$transformer = $smokescreen->getTransformer(); // Now what?
```

- Must have a resource set first
- Returns `null` when no transformer is defined on the resource 

### setSerializer()

Set the serializer to be used for formatting the transformed output:

`$smokescreen->setSerializer(SerializerInterface $serializer)`

```php
<?php
$smokescreen->setSerializer(new MyCustomSerializer);
```
- If not explicitly set - we provide `DefaultSerializer` as the default, it returns collections nested under a `"data"` node, and an item 
resource without any nesting.
- Your custom serializer should implement the `SerializerInterface` interface.

### getSerializer()

Get the serializer object which will be used to serialize every resource:

`$serializer = $smokescreen->getSerializer()`

### setRelationsLoader()

Override the default relations loader:

`$smokescreen->setRelationsLoader(RelationsLoaderInterface $loader);`

```php
<?php
$smokescreen->setRelationsLoader(new MyRelationsLoader);
```

- You only need to set this if you plan to handle eager-loading relationships for includes
- Your loader should implement the `RelationsLoaderInterface` interface and provide a `load()` method.
- See `Rexsoftware\Smokescreen\Relations\RelationLoaderInterface`

### getRelationsLoader()

Get the current (if any) relations loader object:

`$relationsLoader = $smokescreen->getRelationsLoader()`

## toArray()

Kicks off the transformation and serialization process and returns an `array`:

```php
<?php
$output = $smokescreen->item(
        [
            'id' => 1234,
            'subject' => 'Will XRB go to 350?',
            'body' => 'To the mooooon!!!',
            'author' => 'Sam Lin',
            'created_at' => '2018-01-03 15:13:00',
        ],
        new PostTransformer()
    )
    ->toArray();
```

// Output from DefaultSerializer
```php
[
    'id' => 1234,
    'subject' => 'Will XRB go to 350?',
    'body' => 'To the mooooon!!!',
    'author' => 'Sam Lin',
];
```

## jsonSerialize()

Returns the data to be serialized as JSON.

- Implements the PHP JsonSerializable interface
- Returns the same data as `toArray()`

## toJson()

Returns the JSON encoded (string) representation returned from `jsonSerialize()`

```php
<?php
$output = $smokescreen->collection(
        [
            [
                'id' => 1234,
                'subject' => 'Will XRB go to 350?',
                'body' => 'To the mooooon!!!',
                'author' => 'Sam Lin',
                'created_at' => '2018-01-03 15:13:00',
            ],
        ],
        new PostTransformer()
    )
    ->toJson();
```

Output from DefaultSerializer:

```json
{
  "data": [
    {
      "id": 1234,
      "subject": "Will XRB go to 350?",
      "body": "To the mooooon!!!",
      "author": "Sam Lin"
    }
  ]
}
```

### parseIncludes()

Parse a string containing an includes definition:

`$smokescreen->parseIncludes(string $includes);`

```php
<?php
$smokescreen
    ->collection($posts, new PostTransformer)
    ->parseIncludes('id,subject,user{id,full_name},comments{id,user}:limit(3),image');
```

The default parser `RexSoftware\Smokescreen\Includes\IncludeParser` allows specifying a comma seperated list
of includes which may be either a property returned from the `transform()` method, or an include key declared
in the `$includes` array definition (See Transformers section below).

Curly braces are used to indicate depth.

Given a `PostTransformer` class which specifies the following includes:

```php
protected $includes = [
    'user',
    'comments',
    'image',
];

// public function includeUser($post) { ... }
// public function includeComments($post) { ... }
// public function includeImage($post) { ... }
```

In the above example, our includes string would result in the following mapping

- Only the properties `id` and `subject` from the PostTransformer `transform()` result.
- The `user` include which is mapped to the $includes declaration in PostTransformer and results in 
calling `includeUser($post)` which fires the `UserTransformer`.
    - The `Includes` object for this scope contains the following keys: `['id', 'full_name']`
    - Since those aren't mapped within `$includes` they are filtered in the `transform()` result.
- The `comments` include which is mapped to the $includes declaration in PostTransformer and results in 
 calling `includeComments($post)` which fires the `CommentTransformer`.
    - The `Includes` object for this scope contains the following keys: `['id', 'user']`
    - `user` is mapped within `$includes` it will call the `includeUser` method of the transformer
    - `id` is determined to be a property, the `transform()` result will be filtered to only include
     that property
    - All include methods can reach into the scope via `$this->getScope()` to fetch stateful information
    - In the include definition, the `comments` include was suffixed with a parameter `:limit(3)` 
    - The `includeComments` can fetch all the parameters for the scope via the convenience method 
    `$this->getParameters();` or can fetch an individual parameters (with optional default) via `$this->getParameter('limit', 10);`
        - It can treat the `limit` parameter however it likes (eg. add a limit to it's query builder)
- The `image` include is mapped within `$includes` of PostTransformer, results in calling 
`includeImage($post)` which fires the `ImageTransformer`
    - The `Includes` object for this scope contains the following keys: `[]` which means all properties will be
    will be returned from the `transform()` method
    - There are no includes to process

Notes:

- We determine properties by first eliminating mapped includes.
- If we have ONLY invalid properties which are not found in the result from `transform()` they are ignored. 
- When no properties are defined, in the include string definition, all of the properties will
be returned from the `transform()` method.

You may also specify `$defaultProps = [ ... ]` on the transformer to only return those properties
by default.



## Transformers

### Example Transformer

```php
<?php
class PostTransformer extends AbstractTransformer
{
    protected $includes = [
        'user' => 'default|relation:user|method:includeTheDamnUser',
        'comments' => 'relation',
    ];

    public function transform(Post $post): array
    {
        return [
            'id' => $post->id,
            'user' => $this->when($post->user_id, [
                'id' => $post->user_id,
            ]),
            'title' => $post->title,
            'summary' => $post->summary,
            'body' => $post->body,
            'created_at' => utc_datetime($post->created_at),
            'updated_at' => utc_datetime($post->updated_at),
        ];
    }

    public function includeTheDamnUser(Post $post)
    {
        return $this->item($post->user); // Infer Transformer
    }

    public function includeComments(Post $post)
    {
        return $this->collection($post->comments, new CommentTransformer);
    }
}
```

- You declare your available includes via the `$includes` array
- Each include accepts 0 or more of the following directives:
    - `default`: This include is always enabled regardless of the requested includes
    - `relation`: Indicates that a relation should be eager-loaded.  If the relation name is different 
    specify it as `relation:othername`
    - `method`: By default the include key is mapped to `include{IncludeKey}` you can provide the method 
    to be used instead
- Your `transform()` method should return an array.
- Define your include methods in the format `include{IncludeKey}(Model)` - they should return either a 
`collection()` or an `item()`
- `when()` is a simple helper method which accepts a condition and returns 
either the given value when true, or null (by default) when false.  In the above example
the `"user"` node will be `null` if there is no `user_id` set on the `$post` object.

### Properties that can be overridden

#### $includes 

An optional array of include keys with optional definition:

```php
<?php
class MyTransformer extends AbstractTransformer
{
  protected $includes = [
      'user',
      'photos' => 'default'
  ];
  
  // ...
}

```

#### $defaultProps

An optional array of default properties to return (filter) from the result of the `transform()` method
if this is empty, all properties will be returned unless an include definition overrides this.

```php
<?php
class MyTransformer extends AbstractTransformer
{
  // By default the 'age' property will not be returned
  // unless explicitly requested as an include  
  protected $defaultProps = [
      'id',
      'name',
  ];
  
  public function transform($data)
  {
      return [
          'id' => $data['id'],
          'name' => $data['name'],
          'age' => $data['age'],
      ];    
  }
}
```

### Available methods

#### getScope()

Returns the `Scope` object which contains the current resource and the `Includes` object

`$scope = $this->getScope();`

#### getIncludes()

Returns an `Includes` object containing include keys, and mapped parameters.

`$includes = $this->getIncludes();`

Alternative to `$this->getScope()->getIncludes();`

#### getParameters()

Returns an associative array indexed by include key.

`$params = $this->getParameters();`

Alternative to `$this->getScope()->getIncludes()->getParameters()`

#### getParameter()

`$value = $this->getParameter(string $includeKey, mixed $default = null);`

Alternative to `$this->getScope()->getIncludes()->getParameter($includeKey)`

## FAQ

### Why call it it "Smokescreen"

Great question, thanks for asking. Our team racked our brain for several hours to come up with the perfect name for
this package.  In the end we went with Smokescreen because there is a transformer named smokescreen and it sounds cool.

### Why wouldn't I just use Fractal

We took all the good ideas from Fractal, and made it more extensible and safer for children.

## Contributing

Pull-requests are welcome. Please ensure code is PSR compliant.
[Smokescreen on Github](http://github.com/)

## Who do I talk to?

Talk to team #phoenix, or one of these people:
 
- Jodie Dunlop <jodie.dunlop@rexsoftware.com.au>
- Alex Babkov <alex.babkov@rexsoftware.com.au>
