# Inkbound

[github.com/doodersrage/inkbound](https://github.com/doodersrage/inkbound)

WordPress can publish posts. It cannot, natively, run a **serialized fiction / web-novel** site — the chapter desk, follow list, “new chapter” mail, and continue-reading state that people expect from Royal Road or Substack.

Inkbound is a plugin that adds that layer.

## What you get

- **Chapter management** — stories with numbered chapters, labels (prologue / interlude), author’s notes, a table of contents, and a one-click “add next chapter” from the story editor. WordPress scheduling still works for future chapters.
- **Reader subscriptions** — logged-in follows (library) and email subscribe without an account.
- **Update notifications** — publishing a chapter writes an on-site inbox item for followers and queues a chapter email (full text or excerpt). Delivery is logged in **Inkbound → Update mail**, so you can verify it locally without SMTP.
- **Reading progress** — last chapter and percent, for guests (this browser) and accounts. Catalog and library show Continue reading.

The public site is a catalog, story pages, and a reader with paper / sepia / night surfaces.

## Install on an existing WordPress site

```bash
git clone https://github.com/doodersrage/inkbound.git
cp -R inkbound/inkbound /path/to/wordpress/wp-content/plugins/inkbound
```

Then activate **Inkbound**. Create a **Story**, then **Chapters** (each chapter must belong to a story). Optional: **Inkbound → Settings** to use the catalog as the homepage, and to choose full-chapter vs excerpt emails.

Pretty permalinks must be enabled (`Settings → Permalinks`).

## Local preview

Needs PHP 8.1+ with PDO SQLite and [WP-CLI](https://wp-cli.org/).

```bash
chmod +x bin/setup-wp.sh bin/dev-server.sh
./bin/dev-server.sh
```

Open http://127.0.0.1:38471

| Role | User | Password |
| --- | --- | --- |
| Author / admin | `admin` | `inkbound-demo` |
| Reader | `reader` | `reader-demo` |

Demo serials (*The Gilded Deep*, *Signal Hollow*, *Salt & Cipher*) load automatically. The reader account already follows a story, has progress, and has a sample chapter update.

Reload demo content:

```bash
wp inkbound seed --path=.wp-dev --force
```

## How publishing notifies people

When a chapter changes to **Published** and “notify subscribers” is checked:

1. Followers with WordPress accounts get an **Updates** inbox item.
2. Subscribers with mail enabled are queued for a chapter email.
3. The queue is processed immediately and again on cron.

Turn confirmation on in settings before using guest email subscribe in production.

## License

GPL-2.0-or-later.
