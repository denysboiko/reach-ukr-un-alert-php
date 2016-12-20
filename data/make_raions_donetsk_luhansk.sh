#!/bin/sh

echo 'window.raionsDonetskLuhansk = ' > ./raions_donetsk_luhansk.js


# simple redirection like "mapshaper ... >> ./file.js" does not works
# because mapshaper, when usin "-o -", opens /dev/sdout for write, not to append
# (/dev/stdout in our case is ./file.js)

GEOJSON=`mapshaper -i "$1" -simplify 20% -filter-fields KOATUU -o - format=geojson`

echo $GEOJSON >> ./raions_donetsk_luhansk.js