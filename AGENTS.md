# AGENTS.md — em (bookmark manager)

Workspace guidance for agent sessions. Plain-PHP 8 app (no framework), Bootstrap 5.3,
served by Apache/XAMPP. Domain: personal bookmarks ("signets") organised in topics
("rubriques"). UI, comments and docblocks are **French**; identifiers are English.

`config/host.json` is gitignored and usually absent in a fresh checkout. Without it the
app cannot boot, so full runtime checks require a configured instance; otherwise rely on
`php -l`.

---

## 1. Ground rules

- Match the style of the file you are editing. Do not reformat or "modernise" untouched code.
- Keep diffs focused. This is a live personal app, not a library.
- No framework, no namespaces, no PSR-4, no JS bundler, no test suite, no CI.
- Write user-facing copy, comments and docblocks in French.
- Verify PHP with `php -l`; verify JS extracted from inline `<script>` with `node --check`
  when practical.

## 2. Page bootstrap (repeat this skeleton)

Every root page starts with the same guard sequence — see `bookmark_edit.php`, `login.php`,
`admin/index.php`:

```php
require_once './classes/System.class.php';
$system = new System ( './config/host.json' );

if (! $system->configFileExists ()) {
	header ( 'Location:' . $system->getConfigUrl () );
	exit ();
}

include_once './inc/boot.php';
session_start ();

if (! $system->isUserAuthenticated ()) {
	header ( 'Location:' . $system->getLoginUrl () );
	exit ();
}

$maintopic = $system->getMainTopic ();
```

Then: optional POST processing, `$doc_title = …`, `header ( 'charset=utf-8' );`, `?>`, HTML.
Pages include `./inc/menu.inc.php` for the public nav (edit screens like `bookmark_edit.php`
deliberately do not).

## 3. PHP style

| Concern | Convention |
|---|---|
| Indentation | tabs (spaces only inside copied JS snippets) |
| Declaration | `public function getId($x) {` — no space before `(` |
| Call | `$system->getBookmarkById ( $id )` — space before `(` and padded args |
| Array access | `$_REQUEST ['bookmark_id']`, `$this->elements [$id]` — space before `[` |
| Arrays | long syntax `array ( … )` with trailing element on its own line; avoid `[]` |
| Control flow | `if (! $foo) {`, `} else {`, `case 'x' :`, `default :` |
| Strings | single quotes, escaped apostrophes: `'C\'est oublié.'` |
| Echo | `<?php echo … ?>` (no `<?=` short echo) |
| Modern PHP | no `namespace`, no `declare(strict_types)`, no return types, no `??`, no `fn()` |

Newer classes (`classes/Feedback.class.php`) use typed properties/params; do not retrofit
that to old files.

Docblocks are French and carry `@param`, `@return`, `@since MM/YYYY`, `@version MM/YYYY`
(older files vary the date format). Comment out nothing permanently; legacy dead code is
left in place — don't copy the habit.

## 4. Naming

- **Classes** PascalCase in `classes/Foo.class.php`; interfaces `*.interface.php`;
  includes `*.inc.php`; pages lowercase (often concatenated: `lastaddedbookmarks.php`).
- **Methods** camelCase with verb prefixes: `get…`, `set…`, `is…`, `has…`, `toDB`, `toJson`,
  `hydrate`, `addTo`, `getHtmlLinkTo…`.
- **Variables** camelCase (`$bookmarkBeforeProcessing`); historic all-lowercase exceptions
  (`$maintopic`).
- **Request fields** snake_case with domain prefix: `bookmark_url`, `bookmark_title`,
  `newtopic_title`, `topic_type`; hydrated by prefix: `$b->hydrate ( $_POST, 'bookmark_' )`.
- **DB columns** snake_case with table prefix (`bookmark_lastedit_date`, `topic_id`).
- **HTML ids** suffix codes: `_i` input, `_f` form, `_a` link, `_o1`/`_o2` radio options,
  `_fs` fieldset, `_s` section, `_zone` wrapper (`b_url_i`, `b_edit_f`, `newT_fs`,
  `b_privacy_i_o1`). Prefix `b_` bookmark, `newT_` new topic, `relatedT_` related topic.
- **CSS classes** camelCase for custom ones (`buttonBar`, `radioSubSet`, `topicPath`,
  `unlockedBookmark`); Bootstrap utilities otherwise.
- **`<body id>`** page-scoped camelCase (`bookmarkEdit`, `loginDoc`, `topic`, `admin`).
- **Constants** UPPER_SNAKE_CASE, defined in `inc/boot.php`.

## 5. Models and database

- Global `$system` service object (`classes/System.class.php`): config, PDO, URLs, auth,
  paths. Models declare `global $system;` instead of receiving it.
- Models are Active-Record-ish: `hydrate()`, `toDB()`, `toJson()`, `getTableName()`,
  `setAttribute()` normalising strings (trim / strip_tags / html_entity_decode), plus
  `getHtmlLink…()` / `getHtmlInfoIcon()` view helpers.
- PDO prepared statements with named placeholders and `bindValue(…, PDO::PARAM_INT|STR)`.
  SQL keywords uppercase; `NOW()` for timestamps; `lastInsertId()` after insert.
- Collections implement `Countable, IteratorAggregate` and return `CollectionIterator`.

## 6. Requests, validation, escaping

- Dispatch form actions with a hidden `task_id` + `switch` (older `newbookmark.php` uses
  `cmd`). Use `$_REQUEST` for ids, `$_POST` for forms, `$_GET` for the `bookmark_url` hint.
- Always run `ToolBox::formatUserPost ( $_POST )` before processing; `strip_tags` GET URLs.
- Escape output with `ToolBox::toHtml()` (`htmlentities`, `ENT_QUOTES | ENT_HTML5`).
  URLs are often echoed raw in legacy code; prefer `htmlspecialchars()` for new URLs.
- Mutations end with `header ( 'Location: …' ); exit ();` (redirect after POST).
- Errors: `try { … } catch (Exception $e) { $system->reportException ( __METHOD__, $e ); return false; }`.
- User feedback: `Feedback` / `UserFeedBack` → `toJson()` (API) or `messageToHtml()` (page).
- JSON endpoints live in `api/`, send `Content-type: text/plain`, and switch on
  `$_SERVER['REQUEST_METHOD']`.

## 7. HTML / templates

PHP is the template engine. Use Bootstrap 5.3 markup (`row`, `col-lg-4`, `mb-3`,
`form-control`, `btn btn-primary`, `form-check`) and the page skeleton
`<body id="…"><main><header><h1>…</h1></header><section><h2>…</h2>…</section></main>`.
Head tags come from `$system->writeHeadCommonMetaTags()` / `writeHeadCommonLinkTags()`;
Bootstrap JS from `BOOTSTRAP_JS_URI`; icons are Font Awesome 5 (`fas fa-edit`).

Canonical examples: `bookmark_edit.php` (full form), `topic.php` (listing), `login.php`.

## 8. JavaScript

- Vanilla ES6+, no jQuery (removed on purpose — see the comment in `bookmark_edit.php`),
  no modules/bundler, globals such as `apiUrl`, scripts at the end of `<body>`.
- `const`/`let`, arrow functions, template literals, `fetch`, `async/await`.
- Rich inputs are **customized built-in elements**: `class … extends HTMLInputElement`,
  loaded from `js/*.js`, registered with
  `customElements.define("bookmark-url-input", BookmarkUrlInputElement, { extends: "input" })`
  and used as `<input is="bookmark-url-input">`.
- French UI and console strings. Wrap setup in `DOMContentLoaded`.

## 9. CSS / theme

- `skin/theme.css` is generated from `skin/theme.scss` + `skin/swatch.scss` (scssphp is a
  Composer dependency; the compile call in `admin/config.php` is currently commented out).
  Do not hand-edit `theme.css` casually.
- Theme colour `#AFD954`, background `#796d7e`; Oswald headings, Open Sans body.
- Custom CSS uses uppercase element selectors (`BODY`, `MAIN`, `H2`) and camelCase classes;
  media queries are written with spaces: `@media ( min-width : 1200px)`.
- Page-local `<style>` blocks are **not** the norm; only `bookmark_edit_stepper.php` has one.

## 10. Known legacy traps (do not propagate)

- `classes/User.class.php` compares passwords in clear in `authenticate()`;
- No CSRF tokens anywhere. Do not advertise security guarantees based on existing patterns.
- Some model getters trigger lazy `hydrate()` (DB hit) — cache values when looping.
