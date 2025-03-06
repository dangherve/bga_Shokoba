#/bin/bash

#100 x 100
convert saphir/1_r2.png saphir/2_r2.png saphir/3_r2.png saphir/4_r2.png saphir/5_r2.png saphir/6_r2.png dos_r2.png saphir/8_r2.png saphir/9_r2.png saphir/10_r2.png +append saphir_r2.png
convert rubis/1_r2.png rubis/2_r2.png rubis/3_r2.png rubis/4_r2.png rubis/5_r2.png rubis/6_r2.png dos_r2.png rubis/8_r2.png rubis/9_r2.png rubis/10_r2.png +append rubis_r2.png
convert dos_r2.png dos_r2.png dos_r2.png dos_r2.png dos_r2.png dos_r2.png e_7_r2.png dos_r2.png dos_r2.png dos_r2.png  +append emeraude_r2.png
convert dos_r2.png dos_r2.png dos_r2.png dos_r2.png dos_r2.png dos_r2.png d_7_r2.png dos_r2.png dos_r2.png dos_r2.png  +append diamond_r2.png

convert saphir_r2.png rubis_r2.png emeraude_r2.png diamond_r2.png -append ../shokoba_3.png


#250 x 250
convert saphir/1_r.png saphir/2_r.png saphir/3_r.png saphir/4_r.png saphir/5_r.png saphir/6_r.png dos_r.png saphir/8_r.png saphir/9_r.png saphir/10_r.png +append saphir_r.png
convert rubis/1_r.png rubis/2_r.png rubis/3_r.png rubis/4_r.png rubis/5_r.png rubis/6_r.png dos_r.png rubis/8_r.png rubis/9_r.png rubis/10_r.png +append rubis_r.png
convert dos_r.png dos_r.png dos_r.png dos_r.png dos_r.png dos_r.png e_7_r.png dos_r.png dos_r.png dos_r.png  +append emeraude_r.png
convert dos_r.png dos_r.png dos_r.png dos_r.png dos_r.png dos_r.png d_7_r.png dos_r.png dos_r.png dos_r.png  +append diamond_r.png

convert saphir_r.png rubis_r.png emeraude_r.png diamond_r.png -append ../shokoba_4.png
