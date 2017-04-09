#!/bin/bash
vagrant ssh -c "rsync -av /app/public.built/wp-content/themes/{my-theme}/acf-json/ /app/public/wp-content/themes/{my-theme}/acf-json/"
