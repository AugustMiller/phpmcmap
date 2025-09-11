#!/usr/bin/bash

# Notify players:
ddev php artisan app:rcon "say Backing up world files..."

# Freeze writes, then flush world to disk:
ddev php artisan app:rcon "save-off"
ddev php artisan app:rcon "save-all"

# Sync world files to app directory:
rsync -rvd ../minecraft/data/world/region storage/

# Sync player data:
rsync -rvd ../minecraft/data/world/playerdata storage/misc/
# Re-enable writes:
ddev php artisan app:rcon "save-on"

# Report backup success:
ddev php artisan app:rcon "say Backup completed in ~$SECONDS second(s)."

# Start render:
ddev php artisan app:import

# ... the app will also report some messages to players via RCON!
