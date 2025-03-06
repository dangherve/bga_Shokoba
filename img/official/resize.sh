#/bin/bash

rm saphir/*_r.png rubis/*_r.png

list="saphir/*.jpg
rubis/*.jpg
d_7.png
e_7.png
dos.jpg"

for file in $list; do
    convert $file -resize 250x250\! ${file:0:-4}_r.png
    convert $file -resize 100x100\! ${file:0:-4}_r2.png

done
