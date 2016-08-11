#!/bin/bash

# Cleanup dirs
rm -rf tmp/public.building tmp/public.old
mkdir -p tmp/public.building

# Recursively copy files build final web dir
cp -R vendor/WordPress/WordPress/* tmp/public.building
cp -R public/* tmp/public.building

# Move built web dir into place
mkdir -p public.built
mv public.built tmp/public.old && mv tmp/public.building public.built

# Remove files to slim down slug
rm -rf tmp/public.old
rm -rf vendor/WordPress/WordPress
