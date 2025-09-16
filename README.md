# Laravel Minecraft Mapper

This is a barebones [Laravel](https://laravel.com/) application with two primary components:

- A command-line tool for ingesting [region files](https://minecraft.wiki/w/Region_file_format) from a Minecraft world;
- An HTTP API that renders tiles compatible with an opinionated [Leaflet](https://leafletjs.com/) map;

## Development

> [!WARNING]  
> There is currently no provided mechanism for running this in production. It is only intended for use alongside a hosted _Java Edition_ Minecraft server.

Configuration files for [DDEV](https://ddev.com) are provided. Install Docker and DDEV, then run:

```bash
# Boot up the containers:
ddev start

# Establish database schema:
ddev php artisan migrate
```

Without data, the app won’t do much!

## Configuration

The app supports a few environment variables:

- `MC_RCON_SERVER` — Hostname for your Minecraft server.
- `MC_RCON_PASSWORD` — RCON password for the target server. _This should be different than the password players use to connect!_
- `MC_RCON_PORT` — RCON port. _This will be different from the port players use to connect!_

## Importing

Copy your world’s `.mca` files (typically found in `world/region/`) into the `storage/region/` directory. The number of files is proportional to your world’s size; each region contains 1024 chunks (512x512 blocks), but only chunks that have been visited by players will contain data.

To begin an import, run…

```bash
ddev php artisan app:import
```

World files from the `storage/region/` folder are scanned, and those without rows in the database (or with more recent modification times) are imported. The same diffing optimization is present on a chunk-by-chunk basis, so only chunks that have changed since the last import are unpacked.

The app saves [block entity](https://minecraft.wiki/w/Block_entity) data to the `poi` table, and chunk-by-chunk heightmaps in the `chunks` table.

### Synchronization Script

This bash script can be executed via `cron` to automate copying world and player data files.

```bash
#!/usr/bin/bash

# Notify players:
ddev php artisan app:rcon "say Backing up world files..."

# Freeze writes, then flush world to disk:
ddev php artisan app:rcon "save-off"
ddev php artisan app:rcon "save-all"

# Sync world files to app directory:
rsync -rvd ../minecraft/world/region storage/

# Sync player and world data:
rsync -rvd ../minecraft/world/playerdata storage/misc/
rsync ../minecraft/world/level.dat storage/misc/

# Re-enable writes:
ddev php artisan app:rcon "save-on"

# Report backup success:
ddev php artisan app:rcon "say Backup completed in ~$SECONDS second(s)."

# Start render:
ddev php artisan app:import

# ... the app will also report some messages to players via RCON!
```

> [!DANGER]  
> This script temporarily pauses world saves via RCON, then flushes changes to disk _before attempting to copy world files_! This helps avoid corruption in the NBT data. If the script fails before saves are turned back on, you may need to reenable saves manually—either via RCON, or by logging in to the server as an `op` and running `/save-on`.

## RCON

To make the rendering more transparent for players, the app attempts to communicate with the configured server via [RCON](https://minecraft.wiki/w/RCON).

You can send arbitrary commands via the CLI:

```bash
ddev php artisan app:rcon "say Hello, world!"
```

RCON is a one-way protocol, and does not receive server-sent data/events; you will not see any output (except when there is an error establishing the connection).

## Rendering + Front-end

The app does not pre-render any tiles—they are dynamically sampled and rendered in response to requests from the client-side Leaflet map. You may elect to run a reverse-proxy or cache in front of the app to avoid taxing the origin for every tile request… as long as you have a purging strategy in mind!

You can view the map’s configuration in `resources/js/app.js`. A variety of Leaflet extensions and special features are present in the main script and nearby modules.

### Miscellany

The back-end also attempts to read some general world and player info from the back-end. This is not stored in the database; instead, it’s unpacked directly from files in `storage/misc/`. The synchronization script above attempts to copy both of these sources into the application directory.

> [!WARNING]  
> You may encounter errors unless you’ve copied _at least_ your world’s `level.dat` file to `storage/misc/level.dat`.

Player `*.dat` files should be copied into `storage/misc/playerdata/`.

## Roadmap

Features I'd like to implement…

- [ ] Multi-dimension/multi-server;
- [ ] Broader block entity (“POI”) support (currently, only spawners, beds, chests, signs, bells, and campfires are displayed);
- [ ] Handling for multi-block entities (beds, in particular);
- [ ] Player spawn points;
- [ ] Basic block categorization + colorization;
- [ ] Tile fidelity options for more powerful servers;

:deciduous_tree:
