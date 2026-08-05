#!/bin/sh
set -eu

# The public disk is served by Nginx through this link.  It is ignored by Git
# and a bind mount can hide the link created while the image is built, so make
# sure it exists every time the application container starts.
if [ ! -e public/storage ] && [ ! -L public/storage ]; then
    ln -s ../storage/app/public public/storage
fi

exec "$@"
