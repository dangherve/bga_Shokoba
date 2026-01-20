#/bin/bash

#100 x 100
convert saphir/1.png saphir/2.png saphir/3.png saphir/4.png saphir/5.png saphir/6.png dos.png saphir/8.png saphir/9.png saphir/10.png +append saphir.png
convert rubis/1.png rubis/2.png rubis/3.png rubis/4.png rubis/5.png rubis/6.png dos.png rubis/8.png rubis/9.png rubis/10.png +append rubis.png
convert dos.png dos.png dos.png dos.png dos.png dos.png e_7.png dos.png dos.png dos.png  +append emeraude.png
convert dos.png dos.png dos.png dos.png dos.png dos.png d_7.png dos.png dos.png dos.png  +append diamond.png

convert saphir.png rubis.png emeraude.png diamond.png -append ../shokoba_1.png


#250 x 250
convert saphir/1_r.png saphir/2_r.png saphir/3_r.png saphir/4_r.png saphir/5_r.png saphir/6_r.png dos_r.png saphir/8_r.png saphir/9_r.png saphir/10_r.png +append saphir_r.png
convert rubis/1_r.png rubis/2_r.png rubis/3_r.png rubis/4_r.png rubis/5_r.png rubis/6_r.png dos_r.png rubis/8_r.png rubis/9_r.png rubis/10_r.png +append rubis_r.png
convert dos_r.png dos_r.png dos_r.png dos_r.png dos_r.png dos_r.png e_7_r.png dos_r.png dos_r.png dos_r.png  +append emeraude_r.png
convert dos_r.png dos_r.png dos_r.png dos_r.png dos_r.png dos_r.png d_7_r.png dos_r.png dos_r.png dos_r.png  +append diamond_r.png

convert saphir_r.png rubis_r.png emeraude_r.png diamond_r.png -append ../shokoba_2.png


convert saphir/1_r.png saphir/2_r.png saphir/3_r.png saphir/4_r.png saphir/5_r.png saphir/6_r.png e_7.png saphir/8_r.png saphir/9_r.png saphir/10_r.png +append /tmp/m1.png
convert rubis/1_r.png rubis/2_r.png rubis/3_r.png rubis/4_r.png rubis/5_r.png rubis/6_r.png d_7.png rubis/8_r.png rubis/9_r.png rubis/10_r.png +append  /tmp/m2.png
convert /tmp/m1.png /tmp/m2.png dos_r.png  -append ../shokoba_2.png ../../../bga/memory/img
