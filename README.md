# neoan3-db

neoan3 app for mysqli connectivity

## Installation
`composer require neoan3-apps/db --dev-master`

See test/test.php for a quick guide.

### Expects the following defines

| Define | |
|--------|--------|
|db_host | usually "localhost" |
|db_name | Name of your database |
|db_user | Name of db-user |
|db_password | Password for user db_user |
| db_assumes_uuid | (optional) If true, the app auto-generates uuids |

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
It returns an array of associative arrays.

| example | SQL |
| --- | --- |
|`Db::easy('user.first_name user.last_name',['gender'=>'female']);`| `SELECT user.first_name, user.last_name FROM user WHERE gender = ?` 's' 'female' |
|`Db::easy('user.* user_email.email');` | `SELECT user.*, user_email.email FROM user JOIN user_email on user_email.user_id = user.id` |

See operandi & selectandi for added complexity

### Db::ask($param1 [, $param1, $param1])
The ask-function can execute queries based on how it is used. 
It is best to start understanding the examples below.
It returns an array of associative arrays.

| Action | example | SQL & explanation |
|---| --- | --- |
|INSERT|`Db::ask('user',['first_name'=>'Richard']);`| `INSERT INTO user(first_name) VALUES(?)` 's' 'Richard' |
|UPDATE|`Db::ask('user',['first_name'=>'Richard'],['last_name'=>'Hawk']);` | `UPDATE user SET first_name = ? WHERE last_name = ?` 'ss' 'Richard' 'Hawk' |
|ANY| `Db::ask('/user',['name'=>'Richard Hawk'])` | `SELECT * FROM user WHERE CONCAT_WS(' ',first_name,last_name) LIKE ?` 's' 'Richard Hawk'|

/component/user/user.sql:

```SQL
SELECT * FROM user WHERE CONCAT_WS(' ',first_name,last_name) LIKE {{name}}
```

With the ANY-action, SQL can be managed in editor-friendly formats. The locator expects the neoan3 folder component and understands to following format:

/[file] = '/component/[file]/[file].sql' (like above)

or deeper variations like:

/[folder]/[file] = '/component/[folder]/[file].sql'

There are no considerations to be made regarding order of parameters. Naming of the target using curly brackets will ensure that only matching array keys are used.

This makes directly handling user-input save.

### As-declaration
Handling field-name modifications.

| Example | simplified SQL logic |
|---|---|
|`db::easy('user.name:username')`|`SELECT user.name as username FROM user`|

### Conditional modifiers (operandi)
Common condition modifications can be applied by string-manipulation of the condition-array.

| Example | simplified SQL logic |
|---|---|
|`db::easy('user.*',['delete_date'=>'!'])` or `db::easy('user.*',['^delete_date'])` | `SELECT * FROM user WHERE delete_date IS NOT NULL`|
|`db::easy('user.*',['delete_date'=>''])` | `SELECT * FROM user WHERE delete_date IS NULL`|
|`db::easy('user.*',['age'=>'>30'])` | `SELECT * FROM user WHERE age > 30`|
|`db::easy('user.*',['id'=>'$123s..'])` | `SELECT * FROM user WHERE id = UNHEX(123s..)` (convert hex to binary)|
|`db::easy('user.*',['delete_date'=>'.'])` | `SELECT * FROM user WHERE delete_date = NOW()`|

### Value modifiers (selectandi)
Common value-modifications can be applied by string-manipulation of the select-statement.
These modifiers should be used with the "as-declaration"

| Example | simplified SQL logic |
|---|---|
|`db::easy('#user.insert_date:inserted')`| `SELECT UNIX_TIMESTAMP(user.insert_date)*1000 as inserted FROM user`|
|`db::easy('user.* $user.id:id')` | `SELECT *, HEX(id) as id FROM user`|

## Heads up
The general approach of the db-app has been applied for years. While the difference to common wrappers for mysqli of pdo seems rather big,
developers are usually surprised of the low learning-curve and possibilities for faster development it offers.