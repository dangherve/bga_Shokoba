#/bin/bash

rm saphir/*_r.png rubis/*_r.png

list="saphir/*.png
rubis/*.png
d_7.png
e_7.png
dos.png"

for file in $list; do
    convert $file -resize 250x250\! ${file:0:-4}_r.png
done
