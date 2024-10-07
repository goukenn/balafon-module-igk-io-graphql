# igk/io/GraphQl
 
# igk\io\GraphQL module 

version : 1.0

### Simple graphQL base implementation to get or mutate data from balafon Framework


```graphql
{
    basic
    first
    name
}
```
or 
```graphql
{basic, first, name}
```

### calling listener 
```graphql
{
    userinfo(uid: 1){
        firstname
        lastname
    }    
}
```
### setting alias on caller function 
```graphql
{
    admin: userinfo(uid: 1){
        firtname
        lastname
    }
    operator: userinfo(usrOpType: 'operator'){
        firtname
        lastname
    }
}
```

#use in code 

```PHP
<?php
use igk\io\GraphQl\GraphQlParser;

igk_require_module(\igk\io\GraphQl::class);
$data = []; /* SelectData */
$listener = []; /* mutation listener */

$parse = GraphQlParser::Parse(@"query Book{
    title
    ref
}", $data, $listener);

```


### mutation 

```graphql
mutation{
    changeLang(id: 4, locale: 'en'){
        # response 
        locale
    }
}
```


### support array['query'=>'...','variables'=>'...'] 

speading on fragment - always in the current context - 


by default reseverd fields arguments
- limit : to limit export result
- orderBy : to orderBy a spécific column list






- switch to model detection of DataModel Or Listener 

```php

GraphQlParser::Parse($query_or_query_data, $data_or_source_listener);

``` 


```php
// with option -  options are variables
GraphQlParser::ParseWithOption($options, $query_or_query_data, $data_or_source_listener);

```

- by default if the query have a single entry result the name will be skipped as a shortant result.
- modify options on query command 
    - %noSkipFirstNamedQueryEntry% disable name entry skipping

@ C.A.D. BONDJE DOUE