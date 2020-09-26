# neoan3-db

neoan3 app for mysqli connectivity

- opinionated (yet configurable)
- rapid development
- secure (prepared statements & additional security)
- "plug & play" in any (if any) framework

Designed for [neoan3](https://github.com/sroehrl/neoan3), but works as standalone

## Prepared Statements

All queries are performed as prepared statements, running through additional security to sanitize columns.
 
> Throughout this file you will find representation of SQL-functionality that uses a simplified SQL-logic.
These examples are **NOT** the queries actually performed by the wrapper, but meant to provide a solid understanding of
the capabilities of this wrapper.

## Quick Start

### Simple SELECT
```PHP
// SELECT name FROM user WHERE id = 1
// returns e.g.: [0=>['name'=>'Adam']]

$user = Db::easy('user.name',['id'=>1]);
 ```
### Simple INSERT
```PHP
// INSERT INTO user(name,email) VALUES('Sam','sam@sam.example')

$insert = ['name'=>'Sam','email'=>'sam@sam.example'];
$newId = Db::user($insert);
```
### Simple UPDATE
```PHP
// UPDATE user SET name = 'Sam', email = 'sam@sam.example' WHERE id = 1

$update = ['name'=>'Sam','email'=>'sam@sam.example'];
Db::user($update, ['id'=>1]);
```

See test/test.php for some more quick start examples and/or dive into learning:


[![Watch the video](https://img.youtube.com/vi/2kCGESctStg/hqdefault.jpg)](https://youtu.be/2kCGESctStg)


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



### Environment variables

| Define | | Default |
|--------|--------|--------|
| db_host | usually "localhost" | 'localhost' |
| db_name | Name of your database | not set / required |
| db_user | Name of db-user | 'root' |
| db_password | Password for user db_user | '' (empty) |
| db_assumes_uuid | If true, the app auto-generates uuids | false |
| db_app_root | Will read the define 'path' if neoan3 is used  | /parent/of/vendor/folder/ |
| db_file_location | folder of SQL-files relative to app_root  | 'component' |
| db_filter_characters | filters table-names & array-keys  | '/[^a-zA-Z\_\\^\\.\s\*]/' |
| db_casing | *camel* or *snake* for column names | 'snake' |
| db_port | (int) port number | 3306 |
| db_debug | When set to true, SQL is not executed | false |
| db_dev_errors | When set to true, error reporting exposes handled values | false |

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
As such, the following assumptions are made for best usability (auto-joins etc.).
( See [environment variables](#environment-variables) ):

- tables use snake_case naming
- columns use snake_case naming OR cameCase naming 
- primary keys are either int(11) auto_incremented OR binary(16)
- primary keys are called "id", foreign relations are referred to as [table_name]_id OR [table_nameId] 
- when handling rows marked as deleted, the field name must be "delete_date" OR "deleteDate"  (type can be DATE or DATETIME)

## Getting started
### Db::easy($selectorString [, $conditionArray, $callFunctions, $debug])
The easy-function converts a string into a prepared statement and executes it.
It returns an array of associative arrays. The easy-markup is a simplified selector-string representing your database-structure.

| example | SQL |
| --- | --- |
|`Db::easy('user.first_name user.last_name',['gender'=>'female']);`| `SELECT user.first_name, user.last_name FROM user WHERE gender = ?` 's' 'female' |
|`Db::easy('user.* user_email.email');` | `SELECT user.*, user_email.email FROM user JOIN user_email on user_email.user_id = user.id` |

See [operandi](#conditional-modifiers-operandi) & [selectandi](#value-modifiers-selectandi) for added complexity

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

### Magic Method Call

So why does the Db::ask have such a strange name? Because the ask-function is something that you don't **have to** worry about.
It is what happens under the hood and can be replaced by calling it using magic methods.

| Ask | Using magic method call | simplified SQL logic |
|---|---|---|
|`Db::ask('user',['id'=>'1'])`| `Db::user(['id'=>'1'])` |`INSERT INTO user (id) VALUES(1)`|
|`Db::ask('user',['name'=>'Sam'],['id'=>1])`| `Db::user(['name'=>'Sam'],['id'=>'1'])` |`UPDATE user SET name = "Sam" WHERE id = 1`|

### As-declaration
Handling field-name modifications.

| Example | simplified SQL logic |
|---|---|
|`Db::easy('user.name:username')`|`SELECT user.name as username FROM user`|

### Conditional modifiers (operandi)
Common condition modifications can be applied by string-manipulation of the condition-array.

| Example | simplified SQL logic |
|---|---|
|`Db::easy('user.*',['delete_date'=>'!'])` | `SELECT * FROM user WHERE delete_date IS NOT NULL`|
|`Db::easy('user.*',['delete_date'=>''])` or `db::easy('user.*',['^delete_date'])` | `SELECT * FROM user WHERE delete_date IS NULL`|
|`Db::easy('user.*',['age'=>'>30'])` | `SELECT * FROM user WHERE age > 30`|
|`Db::easy('user.*',['id'=>'$123s..'])` | `SELECT * FROM user WHERE id = UNHEX(123s..)` (convert hex to binary)|
|`Db::easy('user.*',['delete_date'=>'.'])` | `SELECT * FROM user WHERE delete_date = NOW()`|

> But what if the value passed to an operandi starts with a modifier?
You can prepend the modifier '=' (equal) to prevent operandi from triggering! e.g.: 

>`Db::ask('password',['password'=>'='.password_hash('123456', PASSWORD_DEFAULT)])`

### Value modifiers (selectandi)
Common value-modifications can be applied by string-manipulation of the select-statement.
These modifiers should be used with the "as-declaration"

| Example | simplified SQL logic |
|---|---|
|`Db::easy('#user.insert_date:inserted')`| `SELECT UNIX_TIMESTAMP(user.insert_date)*1000 as inserted FROM user`|
|`Db::easy('user.* $user.id:id')` | `SELECT *, HEX(id) as id FROM user`|

> But what if the value passed to a selectandi starts with a modifier?
You can prepend the modifier '=' (equal) to prevent operandi from triggering! 
We do not provide an example as this can only happen if your column names would start with a 
character in violation with a secure [_filter_character_](#environment-variable) setup. 

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

## OOP & Testing
Although you want to write your own wrapper depending on the Interface your framework uses,
this library comes with a simple wrapper out of the box to encourage dependency injection usage.

```PHP
$environment = ['name' => 'my_db'];
$database = new \Neoan3\Apps\DbOOP($environment);
$database->easy('user.*', ['^delete_date']); //executes & returns Db::easy 
$database->smart('user', ['name'=>'sam', 'user_type'=>'admin']); //executes & returns Db::ask

```

## Heads up
The general approach of the db-app has been applied for years. While the difference to common wrappers for mysqli of pdo seems rather big,
developers are usually surprised of the low learning-curve and possibilities for faster development it offers.

### Deprecation
The Db::data-function is considered unsafe without proper escaping and throws a deprecation-notice since 0.0.3
