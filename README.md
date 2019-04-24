# neoan3-db

neoan3 app for mysqli connectivity

## Installation
`composer require neoan3-apps/db`

```PHP

require dirname(__FILE__).'/vendor/autoload.php'

use Neoan3\Apps\Db;

Db::setEnvironment([
    'name' => 'your_db',
    'user' => 'root',
    'password' => 'Som3S3cur3Pa55word'
])

/*
*    OR per defines:
*    define('db_host','localhost');
*    define('db_name','yourDB');
*    define('db_user','root');
*    define('db_password','Som3S3cur3Pa55word');
*/

try {
    $test = Db::ask('>NOW() as now'); 
} catch(DbExeption $e){
    die($e->getMessage());
}


/*
*    $test: [0=>['now'=>'2019-01-01 12:12:12']]
*/

```

## Quick Start

```PHP
...

// SELECT name FROM user WHERE id = 1
$user = Db::easy('user.name',['id'=>1]);

/*
*    $user: [0=>['name'=>'Adam']]
*/

// INSERT INTO user(name,email) VALUES('Sam','sam@sam.example')
$insert = ['name'=>'Sam','email'=>'sam@sam.example'];
$newId = Db::ask('user',$insert);

// UPDATE user SET name = 'Sam', email = 'sam@sam.example' WHERE id = 1
$insert = ['name'=>'Sam','email'=>'sam@sam.example'];
Db::ask('user',$insert,['id'=>1]);

```

See test/test.php for some more quick start examples.

### Environment variable

| Define | | Default |
|--------|--------|--------|
| db_host | usually "localhost" | 'localhost' |
| db_name | Name of your database | not set / required |
| db_user | Name of db-user | 'root' |
| db_password | Password for user db_user | '' (empty) |
| db_assumes_uuid | If true, the app auto-generates uuids | false |
| db_app_root | Will read the define 'path' if neoan3 is used  | /parent/of/vendor/folder/ |
| db_file_location | folder of SQL-files relative to app_root  | 'component' |
| db_filter_characters | filters table-names & array-keys  | '/[^a-zA-Z\_\\^\\.\s]/' |

Environment variables can either be set as global constants or using Db::setEnvironment()

```PHP
/*
* When using Db::setEnvironment() the prepended 'db_' is ommitted.
*/

// set single variable:
Db::setEnvironment('name','test_db');

// set multiple variables:
Db::setEnvironment(['name'=>'test_db','password'=>'FooBar']);
```

 We recommend defining these values in your frame when using neoan3.

### Conventions

This tool was created with the recommended neoan3 database structure in mind.
As such, the following assumptions are made for best usability (auto-joins etc.):

- tables use snake_case naming
- fields use snake_case naming
- primary keys are either int(11) auto_incremented or binary(16)
- primary keys are called "id", foreign relations are referred to as [table_name]_id
- when handling rows marked as deleted, the field name must be "delete_date" (type can be DATE or DATETIME)

## Getting started
### Db::easy($selectorString [, $conditionArray, $callFunctions, $debug])
The easy-function converts a string into a prepared statement and executes it.
It returns an array of associative arrays. The easy-markup is a simplified selector-string representing your database-structure.

| example | SQL |
| --- | --- |
|`Db::easy('user.first_name user.last_name',['gender'=>'female']);`| `SELECT user.first_name, user.last_name FROM user WHERE gender = ?` 's' 'female' |
|`Db::easy('user.* user_email.email');` | `SELECT user.*, user_email.email FROM user JOIN user_email on user_email.user_id = user.id` |

See operandi & selectandi for added complexity

### Db::ask($param1 [, $param1, $param1])
The ask-function can execute queries based on how it is used. 
It is best to start understanding the examples below.
It returns an array of associative arrays.

| Action | example | SQL & binding |
|---| --- | --- |
|INSERT|`Db::ask('user',['first_name'=>'Richard']);`| `INSERT INTO user(first_name) VALUES(?)` 's' 'Richard' |
|UPDATE|`Db::ask('user',['first_name'=>'Richard'],['last_name'=>'Hawk']);` | `UPDATE user SET first_name = ? WHERE last_name = ?` 'ss' 'Richard' 'Hawk' |
|ANY(inline)| `Db::ask('>SELECT * FROM user WHERE first_name = {{first_name}}',['first_name'=>'Richard'])` | `SELECT * FROM user WHERE first_name LIKE ?` 's' 'Richard'|
|ANY(file)| `Db::ask('/user',['name'=>'Richard Hawk'])` | `SELECT * FROM user WHERE CONCAT_WS(' ',first_name,last_name) LIKE ?` 's' 'Richard Hawk'|

/component/user/user.sql:

```SQL
SELECT * FROM user WHERE CONCAT_WS(' ',first_name,last_name) LIKE {{name}}
```

With the ANY-action, SQL can be managed in editor-friendly formats. 
By default, the locator expects the neoan3 folder "component" (see Environment variables) and understands to following format:

/[file] = '/[db_file_location]/[file]/[file].sql' (like above)

or deeper variations like:

/[folder]/[file] = '/[db_file_location]/[folder]/[file].sql'

There are no considerations to be made regarding order of parameters. Naming of the target using curly brackets will ensure that only matching array keys are used.

This makes directly handling user-input save.

### As-declaration
Handling field-name modifications.

| Example | simplified SQL logic |
|---|---|
|`Db::easy('user.name:username')`|`SELECT user.name as username FROM user`|

### Conditional modifiers (operandi)
Common condition modifications can be applied by string-manipulation of the condition-array.

| Example | simplified SQL logic |
|---|---|
|`Db::easy('user.*',['delete_date'=>'!'])` or `db::easy('user.*',['^delete_date'])` | `SELECT * FROM user WHERE delete_date IS NOT NULL`|
|`Db::easy('user.*',['delete_date'=>''])` | `SELECT * FROM user WHERE delete_date IS NULL`|
|`Db::easy('user.*',['age'=>'>30'])` | `SELECT * FROM user WHERE age > 30`|
|`Db::easy('user.*',['id'=>'$123s..'])` | `SELECT * FROM user WHERE id = UNHEX(123s..)` (convert hex to binary)|
|`Db::easy('user.*',['delete_date'=>'.'])` | `SELECT * FROM user WHERE delete_date = NOW()`|

### Value modifiers (selectandi)
Common value-modifications can be applied by string-manipulation of the select-statement.
These modifiers should be used with the "as-declaration"

| Example | simplified SQL logic |
|---|---|
|`Db::easy('#user.insert_date:inserted')`| `SELECT UNIX_TIMESTAMP(user.insert_date)*1000 as inserted FROM user`|
|`Db::easy('user.* $user.id:id')` | `SELECT *, HEX(id) as id FROM user`|

## Db-easy markup

Let's face it: most of the time our queries are rather simple.
Whenever we need more complex queries, there will always come the point of realizing that pure SQL would be easier to read 
than endlessly encapsulated arrays. This is where using Db::ask's "any"-actions come into play.

Db::easy does not target these scenarios, but rather specializes in the ever-day retrieval of a particular set of data.
The selected columns are written in one single string.

### Selecting multiple columns from a table
Multiple columns of one table are separated by a space:

```PHP
// SELECT first_name, last_name FROM user

Db::easy('user.first_name user.last_name');
```

### Simple joins
Joins are generated based on the order of the occurrence in the string from left to right, respecting the first used table as the "master".
Easy can only perform JOINS if the recommended db structure is used.
This means that foreign keys must be in the format "master"_id.

```PHP
// SELECT 
//   user.first_name, 
//   user.last_name, 
//   user_email.email, 
//   user_password.confirm_date
// FROM user 
// JOIN user_email ON user_email.user_id = user.id
// JOIN user_password ON user_password.user_id = user.id

Db::easy('user.first_name user.last_name user_email.email user_password.confirm_date');
```


## Heads up
The general approach of the db-app has been applied for years. While the difference to common wrappers for mysqli of pdo seems rather big,
developers are usually surprised of the low learning-curve and possibilities for faster development it offers.

### Deprecation
The Db::data-function is considered unsafe without proper escaping and throws a deprecation-notice since 0.0.3
