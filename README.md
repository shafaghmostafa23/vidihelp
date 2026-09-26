# VidiForm WordPress Theme

An installable WordPress theme (`vidiform/`) with **two fully separate content systems**:

| System | Public URL | Data | Admin menu |
|---|---|---|---|
| Help Center (مرکز راهنما) | `/help/` | `help_article` CPT, `help_category` (hierarchical), `help_feature` (private library) | **مرکز راهنما** |
| Blog (وبلاگ) | `/blog/` | core `post`, `category`, `post_tag`, users | **وبلاگ** (replaces core "Posts") |

Build the ZIP with `./scripts/build-theme-zip.sh` → `dist/vidiform-wordpress-theme.zip`.

## Sources used

* **Help Center**: `VidiForm Content Architecture.html` (Help Center portion only — user help center + admin «راهنماها», «دسته‌بندی راهنما», guide editor, popups). Colors, spacing, radii, copy and interactions are taken from it.
* **Design system**: tokens mapped to CSS variables in `assets/css/base.css`; dark values come from the dark canvas palette in the same HTML.
* **Blog**: the `vidiform content hub` file was not available in the repositories; the Blog follows the feature list in the brief, built with the same VidiForm components (see *Assumptions*).

## Routes

Help Center
- `/help/` home (landing title/description, live search, category cards)
- `/help/search/?q=…` search results (noindex)
- `/help/{category}/` category · `/help/{category}/{sub-category}/` sub-category · `/help/{category}/page/{n}/`
- `/help/{category}/{guide}/` guide (wrong category paths 301 to the canonical URL)

Blog
- `/blog/` home (featured + latest, `/blog/page/{n}/`)
- `/blog/{post}/` post · `/blog/category/{slug}/` · `/blog/tag/{slug}/` · `/blog/author/{name}/` · `/blog/{yyyy}/…` date archives
- `/?s=…` search (blog posts only; noindex)

## Admin sections

- **مرکز راهنما**: راهنماها (grouped by category, drag & drop reorder/move, search, status filter, copy, delete) · ویرایش راهنما (category/sub-category, info, reading time, slug, status, sections editor with media/hint/feature/VidiForm/template/inline guide, SEO) · دسته‌بندی راهنما (landing texts, drag-reorder, rename, description, sub-categories, image, copy, delete) · رسانه · تنظیمات (landing, counts, default structure import).
- **وبلاگ**: نمای کلی · نوشته‌ها (search, status/category/author filters, sort, featured star, trash) · نوشته‌ی جدید (block editor + «ویدی‌فرم — تنظیمات نوشته» box: featured, related posts, reading time, SEO) · دسته‌بندی‌ها · برچسب‌ها · نویسندگان · رسانه · تنظیمات.
- **Settings → ویدی‌فرم** (shared): panel URL, which section `/` opens, default light/dark, Jalali dates, default OG image.
- Role **ویراستار مرکز راهنما** can manage only the Help Center.

## Assumptions

1. `vidiform content hub` was not supplied, so the Blog structure uses standard WordPress blog architecture and the VidiForm visual language.
2. Templates (قالب‌ها) are outside the Help Center scope; a guide section's template reference is stored as title + URL + description + free/premium, and opens the source's preview sheet with the live URL.
3. The source's upload buttons use the WordPress Media Library.
4. The site root redirects to the Help Center (or the Blog, per the setting).
