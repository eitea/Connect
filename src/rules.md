# Connect Code of Conduct

> A user interface is like a joke. If you have to explain it, it’s not that good.

</cite>

## Git/GitHub rules

* You have to work on your own branch. Before requesting a merge with `master`, you have to merge `master` into your own branch. It is a good idea to test the features you've been working on one more time after the merge.
* Commits to `master` will be authored in the morning or late evening.

## General

* Test your code extensively.
* If you create new pages, add them to `src\ajaxQuery\AJAX_getSearch.php`

## Task tracking

Tasks that are ready for review need their ID to be appended to `src/project/task_changelog.txt`. This enables easier tracking of completed tasks.

## URL rules

* Use links with a relative url (no `<a href="/user/home">Home</a>`)
* Links may only contain a group and a name (no `/user/home/time`)
* Links may also contain query parameters
  * `n` or `cmp` is the company id
  * `v` can be used for any id

## PHP rules

* Do not separate `require`, `src`, `href` with \\ (BACKSLASH).
* Join `require` paths using the `DIRECTORY_SEPARATOR` constant.
* Do not use `$conn->multiquery("...")`.
* Do not call `$conn->query("...")` within a loop. Consider using prepared statements instead or optimize your query.
* Never use `$_POST`, `$_GET`, `$_COOKIE`, `$_REQUEST` and the like without sanitizing the input.
  * `intval`/`floatval` for numbers.
  * `test_input` for text.
  * `real_escape_string` is not enough. (Trivial)
  * Prepared statments are not enough. (Trivial)
* Do not use Prepared statements and $conn->query without calling `$result->free()`.
* Always test results from `$result = $conn->query("...")`.
  * E.g. `if ($result) {...}`
  * Optionally `if ($result && $result->num_rows !== 0) {...}` or `if ($result && ($row = $result->fetch_assoc())) {...}`
* Use one of 4 predefined functions for showing status to the user.
  * If the input is `NULL` or `""`, no message will be shown (so you can always call `showError($conn->error)` after a query).
    * `showError(string $message)`
    * `showSuccess(string $message)`
    * `showInfo(string $message)`
    * `showWarning(string $message)`
* AJAX files must be placed in `src\ajaxQuery`.
* Use `src/validate.php` if a feature requires permissions.
* `convToUTF8($text)` is recommended for handling file input.
* Filenames should be lowercase.
* Follow PHP naming conventions (rules defined in this document take precedence though)
* Look at [List of Big-O for PHP functions](https://stackoverflow.com/questions/2473989/list-of-big-o-for-php-functions)

## SQL rules

* Only use `SELECT * FROM ...` when you need all rows.
* Try to avoid redundancy.
* Always define foeign keys with `ON DELETE ...` and `ON UPDATE ...`.
* Table names should be lowercase.
* Follow SQL naming conventions (rules defined in this document take precedence though)

## HTML rules

* Use the [Bootstrap grid system](http://getbootstrap.com/docs/3.3/css/#grid).
* The website should work on smartphones and tablets.
* Follow HTML naming conventions (rules defined in this document take precedence though)

## CSS rules

* Follow CSS naming conventions (rules defined in this document take precedence though)

## JavaScript rules

* Follow JS naming conventions (rules defined in this document take precedence though)
* Use one of 4 predefined functions for showing status to the user.
  * If the input is `null`, `undefined` or `""`, no message will be shown.
  * `showError(message: String)`
  * `showSuccess(message: String)`
  * `showInfo(message: String)`
  * `showWarning(message: String)`
