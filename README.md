# igk/io/GraphQl
 


### The simple graphQL base QL implementation to get and retrieve data. 


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
        locale
    }
}
```


### support array['query'=>'...','variables'=>'...'] 

2023 @ C.A.D. BONDJE DOUE